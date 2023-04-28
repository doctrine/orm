<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Platforms;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Id\BigIntegerIdentityGenerator;
use Doctrine\ORM\Id\IdentityGenerator;
use Doctrine\ORM\Id\SequenceGenerator;
use Doctrine\ORM\Id\UuidGenerator;
use Doctrine\ORM\Mapping\Exception\CannotGenerateIds;
use Doctrine\ORM\Mapping\Exception\InvalidCustomGenerator;
use Doctrine\ORM\Mapping\Exception\UnknownGeneratorType;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\ReflectionService;
use ReflectionClass;
use ReflectionException;

use function assert;
use function class_exists;
use function count;
use function end;
use function explode;
use function get_class;
use function in_array;
use function is_subclass_of;
use function str_contains;
use function strlen;
use function strtolower;
use function substr;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping information of a class which describes how a class should be mapped
 * to a relational database.
 *
 * @extends AbstractClassMetadataFactory<ClassMetadata>
 * @psalm-import-type AssociationMapping from ClassMetadata
 * @psalm-import-type EmbeddedClassMapping from ClassMetadata
 * @psalm-import-type FieldMapping from ClassMetadata
 */
class ClassMetadataFactory extends AbstractClassMetadataFactory
{
    /** @var EntityManagerInterface|null */
    private $em;

    /** @var AbstractPlatform|null */
    private $targetPlatform;

    /** @var MappingDriver */
    private $driver;

    /** @var EventManager */
    private $evm;

    /** @var mixed[] */
    private $embeddablesActiveNesting = [];

    /** @return void */
    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize()
    {
        $this->driver      = $this->em->getConfiguration()->getMetadataDriverImpl();
        $this->evm         = $this->em->getEventManager();
        $this->initialized = true;
    }

    /**
     * {@inheritDoc}
     */
    protected function onNotFoundMetadata($className)
    {
        if (! $this->evm->hasListeners(Events::onClassMetadataNotFound)) {
            return null;
        }

        $eventArgs = new OnClassMetadataNotFoundEventArgs($className, $this->em);

        $this->evm->dispatchEvent(Events::onClassMetadataNotFound, $eventArgs);
        $classMetadata = $eventArgs->getFoundMetadata();
        assert($classMetadata instanceof ClassMetadata || $classMetadata === null);

        return $classMetadata;
    }

    /**
     * {@inheritDoc}
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents)
    {
        if ($parent) {
            $class->setInheritanceType($parent->inheritanceType);
            $class->setDiscriminatorColumn($parent->discriminatorColumn);
            $class->setIdGeneratorType($parent->generatorType);
            $this->addInheritedFields($class, $parent);
            $this->addInheritedRelations($class, $parent);
            $this->addInheritedEmbeddedClasses($class, $parent);
            $class->setIdentifier($parent->identifier);
            $class->setVersioned($parent->isVersioned);
            $class->setVersionField($parent->versionField);
            $class->setDiscriminatorMap($parent->discriminatorMap);
            $class->addSubClasses($parent->subClasses);
            $class->setLifecycleCallbacks($parent->lifecycleCallbacks);
            $class->setChangeTrackingPolicy($parent->changeTrackingPolicy);

            if (! empty($parent->customGeneratorDefinition)) {
                $class->setCustomGeneratorDefinition($parent->customGeneratorDefinition);
            }

            if ($parent->isMappedSuperclass) {
                $class->setCustomRepositoryClass($parent->customRepositoryClassName);
            }
        }

        // Invoke driver
        try {
            $this->driver->loadMetadataForClass($class->getName(), $class);
        } catch (ReflectionException $e) {
            throw MappingException::reflectionFailure($class->getName(), $e);
        }

        // If this class has a parent the id generator strategy is inherited.
        // However this is only true if the hierarchy of parents contains the root entity,
        // if it consists of mapped superclasses these don't necessarily include the id field.
        if ($parent && $rootEntityFound) {
            $this->inheritIdGeneratorMapping($class, $parent);
        } else {
            $this->completeIdGeneratorMapping($class);
        }

        if (! $class->isMappedSuperclass) {
            if ($rootEntityFound && $class->isInheritanceTypeNone()) {
                Deprecation::trigger(
                    'doctrine/orm',
                    'https://github.com/doctrine/orm/pull/10431',
                    "Entity class '%s' is a subclass of the root entity class '%s', but no inheritance mapping type was declared. This is a misconfiguration and will be an error in Doctrine ORM 3.0.",
                    $class->name,
                    end($nonSuperclassParents)
                );
            }

            foreach ($class->embeddedClasses as $property => $embeddableClass) {
                if (isset($embeddableClass['inherited'])) {
                    continue;
                }

                if (isset($this->embeddablesActiveNesting[$embeddableClass['class']])) {
                    throw MappingException::infiniteEmbeddableNesting($class->name, $property);
                }

                $this->embeddablesActiveNesting[$class->name] = true;

                $embeddableMetadata = $this->getMetadataFor($embeddableClass['class']);

                if ($embeddableMetadata->isEmbeddedClass) {
                    $this->addNestedEmbeddedClasses($embeddableMetadata, $class, $property);
                }

                $identifier = $embeddableMetadata->getIdentifier();

                if (! empty($identifier)) {
                    $this->inheritIdGeneratorMapping($class, $embeddableMetadata);
                }

                $class->inlineEmbeddable($property, $embeddableMetadata);

                unset($this->embeddablesActiveNesting[$class->name]);
            }
        }

        if ($parent) {
            if ($parent->isInheritanceTypeSingleTable()) {
                $class->setPrimaryTable($parent->table);
            }

            $this->addInheritedIndexes($class, $parent);

            if ($parent->cache) {
                $class->cache = $parent->cache;
            }

            if ($parent->containsForeignIdentifier) {
                $class->containsForeignIdentifier = true;
            }

            if ($parent->containsEnumIdentifier) {
                $class->containsEnumIdentifier = true;
            }

            if (! empty($parent->namedQueries)) {
                $this->addInheritedNamedQueries($class, $parent);
            }

            if (! empty($parent->namedNativeQueries)) {
                $this->addInheritedNamedNativeQueries($class, $parent);
            }

            if (! empty($parent->sqlResultSetMappings)) {
                $this->addInheritedSqlResultSetMappings($class, $parent);
            }

            if (! empty($parent->entityListeners) && empty($class->entityListeners)) {
                $class->entityListeners = $parent->entityListeners;
            }
        }

        $class->setParentClasses($nonSuperclassParents);

        if ($class->isRootEntity() && ! $class->isInheritanceTypeNone() && ! $class->discriminatorMap) {
            $this->addDefaultDiscriminatorMap($class);
        }

        // During the following event, there may also be updates to the discriminator map as per GH-1257/GH-8402.
        // So, we must not discover the missing subclasses before that.

        if ($this->evm->hasListeners(Events::loadClassMetadata)) {
            $eventArgs = new LoadClassMetadataEventArgs($class, $this->em);
            $this->evm->dispatchEvent(Events::loadClassMetadata, $eventArgs);
        }

        $this->findAbstractEntityClassesNotListedInDiscriminatorMap($class);

        if ($class->changeTrackingPolicy === ClassMetadata::CHANGETRACKING_NOTIFY) {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/orm/issues/8383',
                'NOTIFY Change Tracking policy used in "%s" is deprecated, use deferred explicit instead.',
                $class->name
            );
        }

        $this->validateRuntimeMetadata($class, $parent);
    }

    /**
     * Validate runtime metadata is correctly defined.
     *
     * @param ClassMetadata               $class
     * @param ClassMetadataInterface|null $parent
     *
     * @return void
     *
     * @throws MappingException
     */
    protected function validateRuntimeMetadata($class, $parent)
    {
        if (! $class->reflClass) {
            // only validate if there is a reflection class instance
            return;
        }

        $class->validateIdentifier();
        $class->validateAssociations();
        $class->validateLifecycleCallbacks($this->getReflectionService());

        // verify inheritance
        if (! $class->isMappedSuperclass && ! $class->isInheritanceTypeNone()) {
            if (! $parent) {
                if (count($class->discriminatorMap) === 0) {
                    throw MappingException::missingDiscriminatorMap($class->name);
                }

                if (! $class->discriminatorColumn) {
                    throw MappingException::missingDiscriminatorColumn($class->name);
                }

                foreach ($class->subClasses as $subClass) {
                    if ((new ReflectionClass($subClass))->name !== $subClass) {
                        throw MappingException::invalidClassInDiscriminatorMap($subClass, $class->name);
                    }
                }
            } else {
                assert($parent instanceof ClassMetadataInfo); // https://github.com/doctrine/orm/issues/8746
                if (
                    ! $class->reflClass->isAbstract()
                    && ! in_array($class->name, $class->discriminatorMap, true)
                ) {
                    throw MappingException::mappedClassNotPartOfDiscriminatorMap($class->name, $class->rootEntityName);
                }
            }
        } elseif ($class->isMappedSuperclass && $class->name === $class->rootEntityName && (count($class->discriminatorMap) || $class->discriminatorColumn)) {
            // second condition is necessary for mapped superclasses in the middle of an inheritance hierarchy
            throw MappingException::noInheritanceOnMappedSuperClass($class->name);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadata(
            $className,
            $this->em->getConfiguration()->getNamingStrategy(),
            $this->em->getConfiguration()->getTypedFieldMapper()
        );
    }

    /**
     * Adds a default discriminator map if no one is given
     *
     * If an entity is of any inheritance type and does not contain a
     * discriminator map, then the map is generated automatically. This process
     * is expensive computation wise.
     *
     * The automatically generated discriminator map contains the lowercase short name of
     * each class as key.
     *
     * @throws MappingException
     */
    private function addDefaultDiscriminatorMap(ClassMetadata $class): void
    {
        $allClasses = $this->driver->getAllClassNames();
        $fqcn       = $class->getName();
        $map        = [$this->getShortName($class->name) => $fqcn];

        $duplicates = [];
        foreach ($allClasses as $subClassCandidate) {
            if (is_subclass_of($subClassCandidate, $fqcn)) {
                $shortName = $this->getShortName($subClassCandidate);

                if (isset($map[$shortName])) {
                    $duplicates[] = $shortName;
                }

                $map[$shortName] = $subClassCandidate;
            }
        }

        if ($duplicates) {
            throw MappingException::duplicateDiscriminatorEntry($class->name, $duplicates, $map);
        }

        $class->setDiscriminatorMap($map);
    }

    private function findAbstractEntityClassesNotListedInDiscriminatorMap(ClassMetadata $rootEntityClass): void
    {
        // Only root classes in inheritance hierarchies need contain a discriminator map,
        // so skip for other classes.
        if (! $rootEntityClass->isRootEntity() || $rootEntityClass->isInheritanceTypeNone()) {
            return;
        }

        $processedClasses = [$rootEntityClass->name => true];
        foreach ($rootEntityClass->subClasses as $knownSubClass) {
            $processedClasses[$knownSubClass] = true;
        }

        foreach ($rootEntityClass->discriminatorMap as $declaredClassName) {
            // This fetches non-transient parent classes only
            $parentClasses = $this->getParentClasses($declaredClassName);

            foreach ($parentClasses as $parentClass) {
                if (isset($processedClasses[$parentClass])) {
                    continue;
                }

                $processedClasses[$parentClass] = true;

                // All non-abstract entity classes must be listed in the discriminator map, and
                // this will be validated/enforced at runtime (possibly at a later time, when the
                // subclass is loaded, but anyways). Also, subclasses is about entity classes only.
                // That means we can ignore non-abstract classes here. The (expensive) driver
                // check for mapped superclasses need only be run for abstract candidate classes.
                if (! (new ReflectionClass($parentClass))->isAbstract() || $this->peekIfIsMappedSuperclass($parentClass)) {
                    continue;
                }

                // We have found a non-transient, non-mapped-superclass = an entity class (possibly abstract, but that does not matter)
                $rootEntityClass->addSubClass($parentClass);
            }
        }
    }

    /** @param class-string $className */
    private function peekIfIsMappedSuperclass(string $className): bool
    {
        $reflService = $this->getReflectionService();
        $class       = $this->newClassMetadataInstance($className);
        $this->initializeReflection($class, $reflService);

        $this->driver->loadMetadataForClass($className, $class);

        return $class->isMappedSuperclass;
    }

    /**
     * Gets the lower-case short name of a class.
     *
     * @psalm-param class-string $className
     */
    private function getShortName(string $className): string
    {
        if (! str_contains($className, '\\')) {
            return strtolower($className);
        }

        $parts = explode('\\', $className);

        return strtolower(end($parts));
    }

    /**
     * Puts the `inherited` and `declared` values into mapping information for fields, associations
     * and embedded classes.
     *
     * @param AssociationMapping|EmbeddedClassMapping|FieldMapping $mapping
     */
    private function addMappingInheritanceInformation(array &$mapping, ClassMetadata $parentClass): void
    {
        if (! isset($mapping['inherited']) && ! $parentClass->isMappedSuperclass) {
            $mapping['inherited'] = $parentClass->name;
        }

        if (! isset($mapping['declared'])) {
            $mapping['declared'] = $parentClass->name;
        }
    }

    /**
     * Adds inherited fields to the subclass mapping.
     */
    private function addInheritedFields(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        foreach ($parentClass->fieldMappings as $mapping) {
            $this->addMappingInheritanceInformation($mapping, $parentClass);
            $subClass->addInheritedFieldMapping($mapping);
        }

        foreach ($parentClass->reflFields as $name => $field) {
            $subClass->reflFields[$name] = $field;
        }
    }

    /**
     * Adds inherited association mappings to the subclass mapping.
     *
     * @throws MappingException
     */
    private function addInheritedRelations(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        foreach ($parentClass->associationMappings as $field => $mapping) {
            $this->addMappingInheritanceInformation($mapping, $parentClass);
            // When the class inheriting the relation ($subClass) is the first entity class since the
            // relation has been defined in a mapped superclass (or in a chain
            // of mapped superclasses) above, then declare this current entity class as the source of
            // the relationship.
            // According to the definitions given in https://github.com/doctrine/orm/pull/10396/,
            // this is the case <=> ! isset($mapping['inherited']).
            if (! isset($mapping['inherited'])) {
                $mapping['sourceEntity'] = $subClass->name;
            }

            $subClass->addInheritedAssociationMapping($mapping);
        }
    }

    private function addInheritedEmbeddedClasses(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        foreach ($parentClass->embeddedClasses as $field => $embeddedClass) {
            $this->addMappingInheritanceInformation($embeddedClass, $parentClass);
            $subClass->embeddedClasses[$field] = $embeddedClass;
        }
    }

    /**
     * Adds nested embedded classes metadata to a parent class.
     *
     * @param ClassMetadata $subClass    Sub embedded class metadata to add nested embedded classes metadata from.
     * @param ClassMetadata $parentClass Parent class to add nested embedded classes metadata to.
     * @param string        $prefix      Embedded classes' prefix to use for nested embedded classes field names.
     */
    private function addNestedEmbeddedClasses(
        ClassMetadata $subClass,
        ClassMetadata $parentClass,
        string $prefix
    ): void {
        foreach ($subClass->embeddedClasses as $property => $embeddableClass) {
            if (isset($embeddableClass['inherited'])) {
                continue;
            }

            $embeddableMetadata = $this->getMetadataFor($embeddableClass['class']);

            $parentClass->mapEmbedded(
                [
                    'fieldName' => $prefix . '.' . $property,
                    'class' => $embeddableMetadata->name,
                    'columnPrefix' => $embeddableClass['columnPrefix'],
                    'declaredField' => $embeddableClass['declaredField']
                            ? $prefix . '.' . $embeddableClass['declaredField']
                            : $prefix,
                    'originalField' => $embeddableClass['originalField'] ?: $property,
                ]
            );
        }
    }

    /**
     * Copy the table indices from the parent class superclass to the child class
     */
    private function addInheritedIndexes(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        if (! $parentClass->isMappedSuperclass) {
            return;
        }

        foreach (['uniqueConstraints', 'indexes'] as $indexType) {
            if (isset($parentClass->table[$indexType])) {
                foreach ($parentClass->table[$indexType] as $indexName => $index) {
                    if (isset($subClass->table[$indexType][$indexName])) {
                        continue; // Let the inheriting table override indices
                    }

                    $subClass->table[$indexType][$indexName] = $index;
                }
            }
        }
    }

    /**
     * Adds inherited named queries to the subclass mapping.
     */
    private function addInheritedNamedQueries(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        foreach ($parentClass->namedQueries as $name => $query) {
            if (! isset($subClass->namedQueries[$name])) {
                $subClass->addNamedQuery(
                    [
                        'name'  => $query['name'],
                        'query' => $query['query'],
                    ]
                );
            }
        }
    }

    /**
     * Adds inherited named native queries to the subclass mapping.
     */
    private function addInheritedNamedNativeQueries(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        foreach ($parentClass->namedNativeQueries as $name => $query) {
            if (! isset($subClass->namedNativeQueries[$name])) {
                $subClass->addNamedNativeQuery(
                    [
                        'name'              => $query['name'],
                        'query'             => $query['query'],
                        'isSelfClass'       => $query['isSelfClass'],
                        'resultSetMapping'  => $query['resultSetMapping'],
                        'resultClass'       => $query['isSelfClass'] ? $subClass->name : $query['resultClass'],
                    ]
                );
            }
        }
    }

    /**
     * Adds inherited sql result set mappings to the subclass mapping.
     */
    private function addInheritedSqlResultSetMappings(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        foreach ($parentClass->sqlResultSetMappings as $name => $mapping) {
            if (! isset($subClass->sqlResultSetMappings[$name])) {
                $entities = [];
                foreach ($mapping['entities'] as $entity) {
                    $entities[] = [
                        'fields'                => $entity['fields'],
                        'isSelfClass'           => $entity['isSelfClass'],
                        'discriminatorColumn'   => $entity['discriminatorColumn'],
                        'entityClass'           => $entity['isSelfClass'] ? $subClass->name : $entity['entityClass'],
                    ];
                }

                $subClass->addSqlResultSetMapping(
                    [
                        'name'          => $mapping['name'],
                        'columns'       => $mapping['columns'],
                        'entities'      => $entities,
                    ]
                );
            }
        }
    }

    /**
     * Completes the ID generator mapping. If "auto" is specified we choose the generator
     * most appropriate for the targeted database platform.
     *
     * @throws ORMException
     */
    private function completeIdGeneratorMapping(ClassMetadataInfo $class): void
    {
        $idGenType = $class->generatorType;
        if ($idGenType === ClassMetadata::GENERATOR_TYPE_AUTO) {
            $class->setIdGeneratorType($this->determineIdGeneratorStrategy($this->getTargetPlatform()));
        }

        // Create & assign an appropriate ID generator instance
        switch ($class->generatorType) {
            case ClassMetadata::GENERATOR_TYPE_IDENTITY:
                $sequenceName = null;
                $fieldName    = $class->identifier ? $class->getSingleIdentifierFieldName() : null;

                // Platforms that do not have native IDENTITY support need a sequence to emulate this behaviour.
                if ($this->getTargetPlatform()->usesSequenceEmulatedIdentityColumns()) {
                    Deprecation::trigger(
                        'doctrine/orm',
                        'https://github.com/doctrine/orm/issues/8850',
                        <<<'DEPRECATION'
Context: Loading metadata for class %s
Problem: Using the IDENTITY generator strategy with platform "%s" is deprecated and will not be possible in Doctrine ORM 3.0.
Solution: Use the SEQUENCE generator strategy instead.
DEPRECATION
                            ,
                        $class->name,
                        get_class($this->getTargetPlatform())
                    );
                    $columnName     = $class->getSingleIdentifierColumnName();
                    $quoted         = isset($class->fieldMappings[$fieldName]['quoted']) || isset($class->table['quoted']);
                    $sequencePrefix = $class->getSequencePrefix($this->getTargetPlatform());
                    $sequenceName   = $this->getTargetPlatform()->getIdentitySequenceName($sequencePrefix, $columnName);
                    $definition     = [
                        'sequenceName' => $this->truncateSequenceName($sequenceName),
                    ];

                    if ($quoted) {
                        $definition['quoted'] = true;
                    }

                    $sequenceName = $this
                        ->em
                        ->getConfiguration()
                        ->getQuoteStrategy()
                        ->getSequenceName($definition, $class, $this->getTargetPlatform());
                }

                $generator = $fieldName && $class->fieldMappings[$fieldName]['type'] === 'bigint'
                    ? new BigIntegerIdentityGenerator($sequenceName)
                    : new IdentityGenerator($sequenceName);

                $class->setIdGenerator($generator);

                break;

            case ClassMetadata::GENERATOR_TYPE_SEQUENCE:
                // If there is no sequence definition yet, create a default definition
                $definition = $class->sequenceGeneratorDefinition;

                if (! $definition) {
                    $fieldName    = $class->getSingleIdentifierFieldName();
                    $sequenceName = $class->getSequenceName($this->getTargetPlatform());
                    $quoted       = isset($class->fieldMappings[$fieldName]['quoted']) || isset($class->table['quoted']);

                    $definition = [
                        'sequenceName'      => $this->truncateSequenceName($sequenceName),
                        'allocationSize'    => 1,
                        'initialValue'      => 1,
                    ];

                    if ($quoted) {
                        $definition['quoted'] = true;
                    }

                    $class->setSequenceGeneratorDefinition($definition);
                }

                $sequenceGenerator = new SequenceGenerator(
                    $this->em->getConfiguration()->getQuoteStrategy()->getSequenceName($definition, $class, $this->getTargetPlatform()),
                    (int) $definition['allocationSize']
                );
                $class->setIdGenerator($sequenceGenerator);
                break;

            case ClassMetadata::GENERATOR_TYPE_NONE:
                $class->setIdGenerator(new AssignedGenerator());
                break;

            case ClassMetadata::GENERATOR_TYPE_UUID:
                Deprecation::trigger(
                    'doctrine/orm',
                    'https://github.com/doctrine/orm/issues/7312',
                    'Mapping for %s: the "UUID" id generator strategy is deprecated with no replacement',
                    $class->name
                );
                $class->setIdGenerator(new UuidGenerator());
                break;

            case ClassMetadata::GENERATOR_TYPE_CUSTOM:
                $definition = $class->customGeneratorDefinition;
                if ($definition === null) {
                    throw InvalidCustomGenerator::onClassNotConfigured();
                }

                if (! class_exists($definition['class'])) {
                    throw InvalidCustomGenerator::onMissingClass($definition);
                }

                $class->setIdGenerator(new $definition['class']());
                break;

            default:
                throw UnknownGeneratorType::create($class->generatorType);
        }
    }

    /** @psalm-return ClassMetadata::GENERATOR_TYPE_SEQUENCE|ClassMetadata::GENERATOR_TYPE_IDENTITY */
    private function determineIdGeneratorStrategy(AbstractPlatform $platform): int
    {
        if (
            $platform instanceof Platforms\OraclePlatform
            || $platform instanceof Platforms\PostgreSQLPlatform
        ) {
            return ClassMetadata::GENERATOR_TYPE_SEQUENCE;
        }

        if ($platform->supportsIdentityColumns()) {
            return ClassMetadata::GENERATOR_TYPE_IDENTITY;
        }

        if ($platform->supportsSequences()) {
            return ClassMetadata::GENERATOR_TYPE_SEQUENCE;
        }

        throw CannotGenerateIds::withPlatform($platform);
    }

    private function truncateSequenceName(string $schemaElementName): string
    {
        $platform = $this->getTargetPlatform();
        if (! $platform instanceof Platforms\OraclePlatform && ! $platform instanceof Platforms\SQLAnywherePlatform) {
            return $schemaElementName;
        }

        $maxIdentifierLength = $platform->getMaxIdentifierLength();

        if (strlen($schemaElementName) > $maxIdentifierLength) {
            return substr($schemaElementName, 0, $maxIdentifierLength);
        }

        return $schemaElementName;
    }

    /**
     * Inherits the ID generator mapping from a parent class.
     */
    private function inheritIdGeneratorMapping(ClassMetadataInfo $class, ClassMetadataInfo $parent): void
    {
        if ($parent->isIdGeneratorSequence()) {
            $class->setSequenceGeneratorDefinition($parent->sequenceGeneratorDefinition);
        }

        if ($parent->generatorType) {
            $class->setIdGeneratorType($parent->generatorType);
        }

        if ($parent->idGenerator) {
            $class->setIdGenerator($parent->idGenerator);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function wakeupReflection(ClassMetadataInterface $class, ReflectionService $reflService)
    {
        assert($class instanceof ClassMetadata);
        $class->wakeupReflection($reflService);
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeReflection(ClassMetadataInterface $class, ReflectionService $reflService)
    {
        assert($class instanceof ClassMetadata);
        $class->initializeReflection($reflService);
    }

    /**
     * @deprecated This method will be removed in ORM 3.0.
     *
     * @return class-string
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName)
    {
        /** @psalm-var class-string */
        return $this->em->getConfiguration()->getEntityNamespace($namespaceAlias) . '\\' . $simpleClassName;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDriver()
    {
        return $this->driver;
    }

    /**
     * {@inheritDoc}
     */
    protected function isEntity(ClassMetadataInterface $class)
    {
        return ! $class->isMappedSuperclass;
    }

    private function getTargetPlatform(): Platforms\AbstractPlatform
    {
        if (! $this->targetPlatform) {
            $this->targetPlatform = $this->em->getConnection()->getDatabasePlatform();
        }

        return $this->targetPlatform;
    }
}
