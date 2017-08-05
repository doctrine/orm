<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Platforms;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Reflection\ReflectionService;
use Doctrine\ORM\Sequencing;
use Doctrine\ORM\Sequencing\Planning\ColumnValueGeneratorExecutor;
use Doctrine\ORM\Sequencing\Planning\CompositeValueGenerationPlan;
use Doctrine\ORM\Sequencing\Planning\NoopValueGenerationPlan;
use Doctrine\ORM\Sequencing\Planning\SingleValueGenerationPlan;
use ReflectionException;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping information of a class which describes how a class should be mapped
 * to a relational database.
 *
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ClassMetadataFactory extends AbstractClassMetadataFactory
{
    /**
     * @var EntityManagerInterface|null
     */
    private $em;

    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $targetPlatform;

    /**
     * @var Driver\MappingDriver
     */
    private $driver;

    /**
     * @var \Doctrine\Common\EventManager
     */
    private $evm;

    /**
     * @var array
     */
    private $embeddablesActiveNesting = [];

    /**
     * {@inheritDoc}
     */
    protected function loadMetadata($name)
    {
        $loaded = parent::loadMetadata($name);

        array_map([$this, 'resolveDiscriminatorValue'], array_map([$this, 'getMetadataFor'], $loaded));

        return $loaded;
    }

    /**
     * @param EntityManagerInterface $em
     */
    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize()
    {
        $this->driver = $this->em->getConfiguration()->getMetadataDriverImpl();
        $this->evm = $this->em->getEventManager();
        $this->initialized = true;
    }

    /**
     * {@inheritDoc}
     */
    protected function onNotFoundMetadata($className)
    {
        if ( ! $this->evm->hasListeners(Events::onClassMetadataNotFound)) {
            return;
        }

        $eventArgs = new OnClassMetadataNotFoundEventArgs($className, $this->em);

        $this->evm->dispatchEvent(Events::onClassMetadataNotFound, $eventArgs);

        return $eventArgs->getFoundMetadata();
    }

    /**
     * {@inheritDoc}
     */
    protected function doLoadMetadata(
        ClassMetadata $class,
        ClassMetadata $parent = null,
        bool $rootEntityFound,
        array $nonSuperclassParents
    )
    {
        /* @var $class ClassMetadata */
        /* @var $parent ClassMetadata */
        if ($parent) {
            if ($parent->inheritanceType === InheritanceType::SINGLE_TABLE) {
                $class->setTable($parent->table);
            }

            $this->addInheritedProperties($class, $parent);
            $this->addInheritedEmbeddedClasses($class, $parent);

            $class->setInheritanceType($parent->inheritanceType);
            $class->setIdentifier($parent->identifier);

            if ($parent->discriminatorColumn) {
                $class->setDiscriminatorColumn($parent->discriminatorColumn);
                $class->setDiscriminatorMap($parent->discriminatorMap);
            }

            $class->setLifecycleCallbacks($parent->lifecycleCallbacks);
            $class->setChangeTrackingPolicy($parent->changeTrackingPolicy);

            if ($parent->isMappedSuperclass && ! $class->getCustomRepositoryClassName()) {
                $class->setCustomRepositoryClassName($parent->getCustomRepositoryClassName());
            }

        }

        // Invoke driver
        try {
            $this->driver->loadMetadataForClass($class->getClassName(), $class);
        } catch (ReflectionException $e) {
            throw MappingException::reflectionFailure($class->getClassName(), $e);
        }

        $this->completeIdentifierGeneratorMappings($class);

        /*if ( ! $class->isMappedSuperclass) {
            foreach ($class->embeddedClasses as $property => $embeddableClass) {
                if (isset($embeddableClass['inherited'])) {
                    continue;
                }

                if ( ! (isset($embeddableClass['class']) && $embeddableClass['class'])) {
                    throw MappingException::missingEmbeddedClass($property);
                }

                if (isset($this->embeddablesActiveNesting[$embeddableClass['class']])) {
                    throw MappingException::infiniteEmbeddableNesting($class->getClassName(), $property);
                }

                $this->embeddablesActiveNesting[$class->getClassName()] = true;

                $embeddableMetadata = $this->getMetadataFor($embeddableClass['class']);

                if ($embeddableMetadata->isEmbeddedClass) {
                    $this->addNestedEmbeddedClasses($embeddableMetadata, $class, $property);
                }

                $identifier = $embeddableMetadata->getIdentifier();

                if (! empty($identifier)) {
                    $this->inheritIdGeneratorMapping($class, $embeddableMetadata);
                }

                $class->inlineEmbeddable($property, $embeddableMetadata);

                unset($this->embeddablesActiveNesting[$class->getClassName()]);
            }
        }*/

        if ($parent) {
            if ($parent->inheritanceType === InheritanceType::SINGLE_TABLE) {
                $class->setTable($parent->table);
            }

            $this->addInheritedIndexes($class, $parent);
            $this->addInheritedNamedQueries($class, $parent);

            if ($parent->getCache()) {
                $class->setCache(clone $parent->getCache());
            }

            if ( ! empty($parent->namedNativeQueries)) {
                $this->addInheritedNamedNativeQueries($class, $parent);
            }

            if ( ! empty($parent->sqlResultSetMappings)) {
                $this->addInheritedSqlResultSetMappings($class, $parent);
            }

            if ( ! empty($parent->entityListeners) && empty($class->entityListeners)) {
                $class->entityListeners = $parent->entityListeners;
            }
        }

        $class->setParentClasses($nonSuperclassParents);

        if ($class->isRootEntity() && $class->inheritanceType !== InheritanceType::NONE && ! $class->discriminatorMap) {
            $this->addDefaultDiscriminatorMap($class);
        }

        $this->completeRuntimeMetadata($class, $parent);

        if ($this->evm->hasListeners(Events::loadClassMetadata)) {
            $eventArgs = new LoadClassMetadataEventArgs($class, $this->em);

            $this->evm->dispatchEvent(Events::loadClassMetadata, $eventArgs);
        }

        $this->buildValueGenerationPlan($class);
        $this->validateRuntimeMetadata($class, $parent);
    }

    /**
     * @param ClassMetadata      $class
     * @param ClassMetadata|null $parent
     *
     * @return void
     */
    protected function completeRuntimeMetadata(ClassMetadata $class, ClassMetadata $parent = null)
    {
        if ( ! $parent) {
            return;
        }

        if ( ! $parent->isMappedSuperclass) {
            return;
        }

        if ($class->isMappedSuperclass) {
            return;
        }

        $tableName = $class->getTableName();

        // Resolve column table names
        foreach ($class->getProperties() as $property) {
            if ($property instanceof FieldMetadata) {
                $property->setTableName($property->getTableName() ?? $tableName);

                continue;
            }

            if (! ($property instanceof ToOneAssociationMetadata)) {
                continue;
            }

            // Resolve association join column table names
            foreach ($property->getJoinColumns() as $joinColumn) {
                $joinColumn->setTableName($joinColumn->getTableName() ?? $tableName);
            }
        }

        // Resolve embedded table names
        /*foreach ($class->embeddedClasses as &$mapping) {
            if ( ! isset($mapping['tableName'])) {
                $mapping['tableName'] = $mapping['tableName'] ?? $tableName;
            }
        }*/
    }

    /**
     * Validate runtime metadata is correctly defined.
     *
     * @param ClassMetadata      $class
     * @param ClassMetadata|null $parent
     *
     * @return void
     *
     * @throws MappingException
     */
    protected function validateRuntimeMetadata(ClassMetadata $class, ClassMetadata $parent = null)
    {
        if (! $class->getReflectionClass()) {
            // only validate if there is a reflection class instance
            return;
        }

        $class->validateIdentifier();
        $class->validateAssociations();
        $class->validateLifecycleCallbacks($this->getReflectionService());

        // verify inheritance
        if ( ! $class->isMappedSuperclass && $class->inheritanceType !== InheritanceType::NONE) {
            if ( ! $parent) {
                if (count($class->discriminatorMap) === 0) {
                    throw MappingException::missingDiscriminatorMap($class->getClassName());
                }

                if ( ! $class->discriminatorColumn) {
                    throw MappingException::missingDiscriminatorColumn($class->getClassName());
                }
            }
        } else if ($class->isMappedSuperclass && $class->isRootEntity() && (count($class->discriminatorMap) || $class->discriminatorColumn)) {
            // second condition is necessary for mapped superclasses in the middle of an inheritance hierarchy
            throw MappingException::noInheritanceOnMappedSuperClass($class->getClassName());
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadata($className, $this->em->getConfiguration()->getNamingStrategy());
    }

    /**
     * Populates the discriminator value of the given metadata (if not set) by iterating over discriminator
     * map classes and looking for a fitting one.
     *
     * @param ClassMetadata $metadata
     *
     * @return void
     *
     * @throws MappingException
     */
    private function resolveDiscriminatorValue(ClassMetadata $metadata)
    {
        if ($metadata->discriminatorValue || ! $metadata->discriminatorMap || $metadata->isMappedSuperclass ||
            ! $metadata->getReflectionClass() || $metadata->getReflectionClass()->isAbstract()) {
            return;
        }

        // minor optimization: avoid loading related metadata when not needed
        foreach ($metadata->discriminatorMap as $discriminatorValue => $discriminatorClass) {
            if ($discriminatorClass === $metadata->getClassName()) {
                $metadata->discriminatorValue = $discriminatorValue;

                return;
            }
        }

        // iterate over discriminator mappings and resolve actual referenced classes according to existing metadata
        foreach ($metadata->discriminatorMap as $discriminatorValue => $discriminatorClass) {
            if ($metadata->getClassName() === $this->getMetadataFor($discriminatorClass)->getClassName()) {
                $metadata->discriminatorValue = $discriminatorValue;

                return;
            }
        }

        throw MappingException::mappedClassNotPartOfDiscriminatorMap($metadata->getClassName(), $metadata->getRootClassName());
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
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     *
     * @throws MappingException
     */
    private function addDefaultDiscriminatorMap(ClassMetadata $class)
    {
        $allClasses = $this->driver->getAllClassNames();
        $fqcn       = $class->getClassName();
        $map        = [$this->getShortName($fqcn) => $fqcn];
        $duplicates = [];

        foreach ($allClasses as $subClassCandidate) {
            if (is_subclass_of($subClassCandidate, $fqcn)) {
                $shortName = $this->getShortName($subClassCandidate);

                if (isset($map[$shortName])) {
                    $duplicates[] = $shortName;
                }

                $map[$shortName] = $class->fullyQualifiedClassName($subClassCandidate);
            }
        }

        if ($duplicates) {
            throw MappingException::duplicateDiscriminatorEntry($class->getClassName(), $duplicates, $map);
        }

        $class->setDiscriminatorMap($map);
    }

    /**
     * Gets the lower-case short name of a class.
     *
     * @param string $className
     *
     * @return string
     */
    private function getShortName($className)
    {
        if (strpos($className, "\\") === false) {
            return strtolower($className);
        }

        $parts = explode("\\", $className);

        return strtolower(end($parts));
    }

    /**
     * Adds inherited fields to the subclass mapping.
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $subClass
     * @param \Doctrine\ORM\Mapping\ClassMetadata $parentClass
     *
     * @return void
     */
    private function addInheritedProperties(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        $isAbstract = $parentClass->isMappedSuperclass;

        foreach ($parentClass->getProperties() as $fieldName => $property) {
            if ($isAbstract && $property instanceof ToManyAssociationMetadata && ! $property->isOwningSide()) {
                throw MappingException::illegalToManyAssociationOnMappedSuperclass($parentClass->getClassName(), $fieldName);
            }

            $subClass->addInheritedProperty($property);
        }
    }

    /**
     * Adds inherited embedded mappings to the subclass mapping.
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $subClass
     * @param \Doctrine\ORM\Mapping\ClassMetadata $parentClass
     *
     * @return void
     */
    private function addInheritedEmbeddedClasses(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        /*foreach ($parentClass->embeddedClasses as $field => $embeddedClass) {
            if ( ! isset($embeddedClass['tableName'])) {
                $embeddedClass['tableName'] = ! $parentClass->isMappedSuperclass ? $parentClass->getTableName() : null;
            }

            if ( ! isset($embeddedClass['inherited']) && ! $parentClass->isMappedSuperclass) {
                $embeddedClass['inherited'] = $parentClass->getClassName();
            }

            $subClass->embeddedClasses[$field] = $embeddedClass;
        }*/
    }

    /**
     * Adds nested embedded classes metadata to a parent class.
     *
     * @param ClassMetadata $subClass    Sub embedded class metadata to add nested embedded classes metadata from.
     * @param ClassMetadata $parentClass Parent class to add nested embedded classes metadata to.
     * @param string        $prefix      Embedded classes' prefix to use for nested embedded classes field names.
     */
    private function addNestedEmbeddedClasses(ClassMetadata $subClass, ClassMetadata $parentClass, $prefix)
    {
        /*foreach ($subClass->embeddedClasses as $property => $embeddableClass) {
            if (isset($embeddableClass['inherited'])) {
                continue;
            }

            $embeddableMetadata = $this->getMetadataFor($embeddableClass['class']);

            $parentClass->mapEmbedded(
                [
                    'fieldName' => $prefix . '.' . $property,
                    'class' => $embeddableMetadata->getClassName(),
                    'columnPrefix' => $embeddableClass['columnPrefix'],
                    'declaredField' => $embeddableClass['declaredField']
                            ? $prefix . '.' . $embeddableClass['declaredField']
                            : $prefix,
                    'originalField' => $embeddableClass['originalField'] ?: $property,
                ]
            );
        }*/
    }

    /**
     * Copy the table indices from the parent class superclass to the child class
     *
     * @param ClassMetadata $subClass
     * @param ClassMetadata $parentClass
     *
     * @return void
     */
    private function addInheritedIndexes(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        if ( ! $parentClass->isMappedSuperclass) {
            return;
        }

        foreach ($parentClass->table->getIndexes() as $indexName => $index) {
            if ($subClass->table->hasIndex($indexName)) {
                continue;
            }

            $subClass->table->addIndex($index);
        }

        foreach ($parentClass->table->getUniqueConstraints() as $constraintName => $constraint) {
            if ($subClass->table->hasUniqueConstraint($constraintName)) {
                continue;
            }

            $subClass->table->addUniqueConstraint($constraint);
        }
    }

    /**
     * Adds inherited named queries to the subclass mapping.
     *
     * @since 2.2
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $subClass
     * @param \Doctrine\ORM\Mapping\ClassMetadata $parentClass
     *
     * @return void
     */
    private function addInheritedNamedQueries(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->getNamedQueries() as $name => $query) {
            if ($subClass->hasNamedQuery($name)) {
                continue;
            }

            $subClass->addNamedQuery($name, $query);
        }
    }

    /**
     * Adds inherited named native queries to the subclass mapping.
     *
     * @since 2.3
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $subClass
     * @param \Doctrine\ORM\Mapping\ClassMetadata $parentClass
     *
     * @return void
     */
    private function addInheritedNamedNativeQueries(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->namedNativeQueries as $name => $query) {
            if (isset($subClass->namedNativeQueries[$name])) {
                continue;
            }

            $subClass->addNamedNativeQuery(
                $name,
                $query['query'],
                [
                    'resultSetMapping' => $query['resultSetMapping'],
                    'resultClass'      => $query['resultClass'],
                ]
            );
        }
    }

    /**
     * Adds inherited sql result set mappings to the subclass mapping.
     *
     * @since 2.3
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $subClass
     * @param \Doctrine\ORM\Mapping\ClassMetadata $parentClass
     *
     * @return void
     */
    private function addInheritedSqlResultSetMappings(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->sqlResultSetMappings as $name => $mapping) {
            if (isset ($subClass->sqlResultSetMappings[$name])) {
                continue;
            }

            $entities = [];

            foreach ($mapping['entities'] as $entity) {
                $entities[] = [
                    'fields'              => $entity['fields'],
                    'discriminatorColumn' => $entity['discriminatorColumn'],
                    'entityClass'         => $entity['entityClass'],
                ];
            }

            $subClass->addSqlResultSetMapping(
                [
                    'name'     => $mapping['name'],
                    'columns'  => $mapping['columns'],
                    'entities' => $entities,
                ]
            );
        }
    }

    /**
     * Completes the ID generator mapping. If "auto" is specified we choose the generator
     * most appropriate for the targeted database platform.
     *
     * @param ClassMetadata $class
     *
     * @return void
     *
     * @throws ORMException
     */
    private function completeIdentifierGeneratorMappings(ClassMetadata $class)
    {
        foreach ($class->getProperties() as $property) {
            if ( ! $property instanceof FieldMetadata /*&& ! $property instanceof AssocationMetadata*/) {
                continue;
            }

            $this->completeFieldIdentifierGeneratorMapping($property);
        }
    }

    private function completeFieldIdentifierGeneratorMapping(FieldMetadata $field)
    {
        if (!$field->hasValueGenerator()) {
            return;
        }

        $platform  = $this->getTargetPlatform();
        $class     = $field->getDeclaringClass();
        $generator = $field->getValueGenerator();

        if ($generator->getType() === GeneratorType::AUTO) {
            $generator = new ValueGeneratorMetadata(
                $platform->prefersSequences()
                    ? GeneratorType::SEQUENCE
                    : ($platform->prefersIdentityColumns()
                        ? GeneratorType::IDENTITY
                        : GeneratorType::TABLE
                ),
                $field->getValueGenerator()->getDefinition()
            );
            $field->setValueGenerator($generator);
        }

        // Validate generator definition and set defaults where needed
        switch ($generator->getType()) {
            case GeneratorType::SEQUENCE:
                // If there is no sequence definition yet, create a default definition
                if ($generator->getDefinition()) {
                    break;
                }

                // @todo guilhermeblanco Move sequence generation to DBAL
                $sequencePrefix = $platform->getSequencePrefix($class->getTableName(), $class->getSchemaName());
                $idSequenceName = sprintf('%s_%s_seq', $sequencePrefix, $field->getColumnName());
                $sequenceName   = $platform->fixSchemaElementName($idSequenceName);

                $field->setValueGenerator(
                    new ValueGeneratorMetadata(
                        $generator->getType(),
                        [
                            'sequenceName'   => $sequenceName,
                            'allocationSize' => 1,
                        ]
                    )
                );

                break;

            case GeneratorType::TABLE:
                throw new ORMException("TableGenerator not yet implemented.");
                break;

            case GeneratorType::CUSTOM:
                $definition = $generator->getDefinition();
                if ( ! isset($definition['class'])) {
                    throw new ORMException(sprintf('Cannot instantiate custom generator, no class has been defined'));
                }
                if ( ! class_exists($definition['class'])) {
                    throw new ORMException(sprintf('Cannot instantiate custom generator : %s', var_export($definition, true))); //$definition['class']));
                }

                break;

            case GeneratorType::IDENTITY:
            case GeneratorType::NONE:
            case GeneratorType::UUID:
                break;

            default:
                throw new ORMException("Unknown generator type: " . $generator->getType());
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function wakeupReflection(ClassMetadata $class, ReflectionService $reflService)
    {
        $class->wakeupReflection($reflService);
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeReflection(ClassMetadata $class, ReflectionService $reflService)
    {
        $class->initializeReflection($reflService);
    }

    /**
     * {@inheritDoc}
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName)
    {
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
    protected function isEntity(ClassMetadata $class)
    {
        return isset($class->isMappedSuperclass) && $class->isMappedSuperclass === false;
    }

    /**
     * @return Platforms\AbstractPlatform
     */
    private function getTargetPlatform()
    {
        if (!$this->targetPlatform) {
            $this->targetPlatform = $this->em->getConnection()->getDatabasePlatform();
        }

        return $this->targetPlatform;
    }

    private function buildValueGenerationPlan(ClassMetadata $class): void
    {
        /** @var LocalColumnMetadata[] $generatedProperties */
        $generatedProperties = array_filter($class->getProperties(), function (Property $property): bool {
            return $property instanceof LocalColumnMetadata && $property->hasValueGenerator();
        });
        $propertiesCount = count($generatedProperties);

        if ($propertiesCount === 0) {
            $class->setValueGenerationPlan(new NoopValueGenerationPlan());
            return;
        }

        if ($propertiesCount === 1) {
            $property = reset($generatedProperties);

            $class->setValueGenerationPlan(new SingleValueGenerationPlan(
                $class,
                new ColumnValueGeneratorExecutor($property, $this->createPropertyValueGenerator($class, $property))
            ));

            return;
        }

        $executors = [];
        foreach ($generatedProperties as $property) {
            $executors[] = new ColumnValueGeneratorExecutor($property, $this->createPropertyValueGenerator($class, $property));
        }

        $class->setValueGenerationPlan(new CompositeValueGenerationPlan($class, $executors));
    }

    private function createPropertyValueGenerator(ClassMetadata $class, LocalColumnMetadata $property): Sequencing\Generator
    {
        $platform = $this->getTargetPlatform();

        switch ($property->getValueGenerator()->getType()) {
            case GeneratorType::IDENTITY:
                $sequenceName = null;

                // Platforms that do not have native IDENTITY support need a sequence to emulate this behaviour.
                if ($platform->usesSequenceEmulatedIdentityColumns()) {
                    $sequencePrefix = $platform->getSequencePrefix($class->getTableName(), $class->getSchemaName());
                    $idSequenceName = $platform->getIdentitySequenceName($sequencePrefix, $property->getColumnName());
                    $sequenceName   = $platform->quoteIdentifier($platform->fixSchemaElementName($idSequenceName));
                }

                return $property->getTypeName() === 'bigint'
                    ? new Sequencing\BigIntegerIdentityGenerator($sequenceName)
                    : new Sequencing\IdentityGenerator($sequenceName);

            case GeneratorType::SEQUENCE:
                $definition = $property->getValueGenerator()->getDefinition();
                return new Sequencing\SequenceGenerator(
                    $platform->quoteIdentifier($definition['sequenceName']),
                    $definition['allocationSize']
                );
                break;

            case GeneratorType::UUID:
                return new Sequencing\UuidGenerator();
                break;

            case GeneratorType::CUSTOM:
                $class = $property->getValueGenerator()->getDefinition()['class'];
                return new $class();
                break;
        }
    }
}
