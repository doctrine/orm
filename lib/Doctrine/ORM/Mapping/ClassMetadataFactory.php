<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Platforms;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\ORMException;
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
     * {@inheritdoc}
     */
    protected function loadMetadata(string $name, ClassMetadataBuildingContext $metadataBuildingContext) : array
    {
        $loaded = parent::loadMetadata($name, $metadataBuildingContext);

        array_map([$this, 'resolveDiscriminatorValue'], $loaded);

        return $loaded;
    }

    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ORMException
     */
    protected function initialize() : void
    {
        $this->driver      = $this->em->getConfiguration()->getMetadataDriverImpl();
        $this->evm         = $this->em->getEventManager();
        $this->initialized = true;
    }

    /**
     * {@inheritdoc}
     */
    protected function onNotFoundMetadata(
        string $className,
        ClassMetadataBuildingContext $metadataBuildingContext
    ) : ?ClassMetadata {
        if (! $this->evm->hasListeners(Events::onClassMetadataNotFound)) {
            return null;
        }

        $eventArgs = new OnClassMetadataNotFoundEventArgs($className, $metadataBuildingContext, $this->em);

        $this->evm->dispatchEvent(Events::onClassMetadataNotFound, $eventArgs);

        return $eventArgs->getFoundMetadata();
    }

    /**
     * {@inheritdoc}
     *
     * @throws MappingException
     * @throws ORMException
     */
    protected function doLoadMetadata(
        string $className,
        ?ClassMetadata $parent,
        ClassMetadataBuildingContext $metadataBuildingContext
    ) : ClassMetadata {
        $classMetadata = new ClassMetadata($className, $metadataBuildingContext);

        if ($parent) {
            $classMetadata->setParent($parent);

            $this->addInheritedProperties($classMetadata, $parent);

            $classMetadata->setInheritanceType($parent->inheritanceType);
            $classMetadata->setIdentifier($parent->identifier);

            if ($parent->discriminatorColumn) {
                $classMetadata->setDiscriminatorColumn($parent->discriminatorColumn);
                $classMetadata->setDiscriminatorMap($parent->discriminatorMap);
            }

            $classMetadata->setLifecycleCallbacks($parent->lifecycleCallbacks);
            $classMetadata->setChangeTrackingPolicy($parent->changeTrackingPolicy);

            if ($parent->isMappedSuperclass) {
                $classMetadata->setCustomRepositoryClassName($parent->getCustomRepositoryClassName());
            }
        }

        // Invoke driver
        try {
            $this->driver->loadMetadataForClass($classMetadata->getClassName(), $classMetadata, $metadataBuildingContext);
        } catch (ReflectionException $e) {
            throw MappingException::reflectionFailure($classMetadata->getClassName(), $e);
        }

        $this->completeIdentifierGeneratorMappings($classMetadata);

        if ($parent) {
            $this->addInheritedNamedQueries($classMetadata, $parent);

            if ($parent->getCache()) {
                $classMetadata->setCache(clone $parent->getCache());
            }

            if (! empty($parent->namedNativeQueries)) {
                $this->addInheritedNamedNativeQueries($classMetadata, $parent);
            }

            if (! empty($parent->sqlResultSetMappings)) {
                $this->addInheritedSqlResultSetMappings($classMetadata, $parent);
            }

            if (! empty($parent->entityListeners) && empty($classMetadata->entityListeners)) {
                $classMetadata->entityListeners = $parent->entityListeners;
            }
        }

        if (! $classMetadata->discriminatorMap && $classMetadata->inheritanceType !== InheritanceType::NONE && $classMetadata->isRootEntity()) {
            $this->addDefaultDiscriminatorMap($classMetadata);
        }

        $this->completeRuntimeMetadata($classMetadata, $parent);

        if ($this->evm->hasListeners(Events::loadClassMetadata)) {
            $eventArgs = new LoadClassMetadataEventArgs($classMetadata, $this->em);

            $this->evm->dispatchEvent(Events::loadClassMetadata, $eventArgs);
        }

        $this->buildValueGenerationPlan($classMetadata);
        $this->validateRuntimeMetadata($classMetadata, $parent);

        return $classMetadata;
    }

    protected function completeRuntimeMetadata(ClassMetadata $class, ?ClassMetadata $parent = null) : void
    {
        if (! $parent || ! $parent->isMappedSuperclass) {
            return;
        }

        if ($class->isMappedSuperclass) {
            return;
        }

        $tableName = $class->getTableName();

        // Resolve column table names
        foreach ($class->getDeclaredPropertiesIterator() as $property) {
            if ($property instanceof FieldMetadata) {
                $property->setTableName($property->getTableName() ?? $tableName);

                continue;
            }

            if (! ($property instanceof ToOneAssociationMetadata)) {
                continue;
            }

            // Resolve association join column table names
            foreach ($property->getJoinColumns() as $joinColumn) {
                /** @var JoinColumnMetadata $joinColumn */
                $joinColumn->setTableName($joinColumn->getTableName() ?? $tableName);
            }
        }
    }

    /**
     * Validate runtime metadata is correctly defined.
     *
     * @throws MappingException
     */
    protected function validateRuntimeMetadata(ClassMetadata $class, ?ClassMetadata $parent = null) : void
    {
        if (! $class->getReflectionClass()) {
            // only validate if there is a reflection class instance
            return;
        }

        $class->validateIdentifier();
        $class->validateAssociations();
        $class->validateLifecycleCallbacks($this->getReflectionService());

        // verify inheritance
        if (! $class->isMappedSuperclass && $class->inheritanceType !== InheritanceType::NONE) {
            if (! $parent) {
                if (! $class->discriminatorMap) {
                    throw MappingException::missingDiscriminatorMap($class->getClassName());
                }

                if (! $class->discriminatorColumn) {
                    throw MappingException::missingDiscriminatorColumn($class->getClassName());
                }
            }
        } elseif (($class->discriminatorMap || $class->discriminatorColumn) && $class->isMappedSuperclass && $class->isRootEntity()) {
            // second condition is necessary for mapped superclasses in the middle of an inheritance hierarchy
            throw MappingException::noInheritanceOnMappedSuperClass($class->getClassName());
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function newClassMetadataBuildingContext() : ClassMetadataBuildingContext
    {
        return new ClassMetadataBuildingContext(
            $this,
            $this->getReflectionService(),
            $this->em->getConfiguration()->getNamingStrategy()
        );
    }

    /**
     * Populates the discriminator value of the given metadata (if not set) by iterating over discriminator
     * map classes and looking for a fitting one.
     *
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     * @throws MappingException
     */
    private function resolveDiscriminatorValue(ClassMetadata $metadata) : void
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
     * @throws MappingException
     */
    private function addDefaultDiscriminatorMap(ClassMetadata $class) : void
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

                $map[$shortName] = $subClassCandidate;
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
     */
    private function getShortName($className) : string
    {
        if (strpos($className, '\\') === false) {
            return strtolower($className);
        }

        $parts = explode('\\', $className);

        return strtolower(end($parts));
    }

    /**
     * Adds inherited fields to the subclass mapping.
     *
     * @throws MappingException
     */
    private function addInheritedProperties(ClassMetadata $subClass, ClassMetadata $parentClass) : void
    {
        $isAbstract = $parentClass->isMappedSuperclass;

        foreach ($parentClass->getDeclaredPropertiesIterator() as $fieldName => $property) {
            if ($isAbstract && $property instanceof ToManyAssociationMetadata && ! $property->isOwningSide()) {
                throw MappingException::illegalToManyAssociationOnMappedSuperclass($parentClass->getClassName(), $fieldName);
            }

            $subClass->addInheritedProperty($property);
        }
    }

    /**
     * Adds inherited named queries to the subclass mapping.
     *
     * @throws MappingException
     */
    private function addInheritedNamedQueries(ClassMetadata $subClass, ClassMetadata $parentClass) : void
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
     * @throws MappingException
     */
    private function addInheritedNamedNativeQueries(ClassMetadata $subClass, ClassMetadata $parentClass) : void
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
     * @throws MappingException
     */
    private function addInheritedSqlResultSetMappings(ClassMetadata $subClass, ClassMetadata $parentClass) : void
    {
        foreach ($parentClass->sqlResultSetMappings as $name => $mapping) {
            if (isset($subClass->sqlResultSetMappings[$name])) {
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
     * @throws ORMException
     */
    private function completeIdentifierGeneratorMappings(ClassMetadata $class) : void
    {
        foreach ($class->getDeclaredPropertiesIterator() as $property) {
            if (! $property instanceof FieldMetadata /*&& ! $property instanceof AssocationMetadata*/) {
                continue;
            }

            $this->completeFieldIdentifierGeneratorMapping($property);
        }
    }

    private function completeFieldIdentifierGeneratorMapping(FieldMetadata $field)
    {
        if (! $field->hasValueGenerator()) {
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
                $sequencePrefix = $platform->getSequencePrefix($field->getTableName(), $field->getSchemaName());
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
                throw new ORMException('TableGenerator not yet implemented.');
                break;

            case GeneratorType::CUSTOM:
                $definition = $generator->getDefinition();
                if (! isset($definition['class'])) {
                    throw new ORMException(sprintf('Cannot instantiate custom generator, no class has been defined'));
                }
                if (! class_exists($definition['class'])) {
                    throw new ORMException(sprintf('Cannot instantiate custom generator : %s', var_export($definition, true))); //$definition['class']));
                }

                break;

            case GeneratorType::IDENTITY:
            case GeneratorType::NONE:
            case GeneratorType::UUID:
                break;

            default:
                throw new ORMException('Unknown generator type: ' . $generator->getType());
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName) : string
    {
        return $this->em->getConfiguration()->getEntityNamespace($namespaceAlias) . '\\' . $simpleClassName;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDriver() : Driver\MappingDriver
    {
        return $this->driver;
    }

    /**
     * {@inheritDoc}
     */
    protected function isEntity(ClassMetadata $class) : bool
    {
        return isset($class->isMappedSuperclass) && $class->isMappedSuperclass === false;
    }

    private function getTargetPlatform() : Platforms\AbstractPlatform
    {
        if (! $this->targetPlatform) {
            $this->targetPlatform = $this->em->getConnection()->getDatabasePlatform();
        }

        return $this->targetPlatform;
    }

    private function buildValueGenerationPlan(ClassMetadata $class) : void
    {
        /** @var LocalColumnMetadata[] $generatedProperties */
        $generatedProperties = [];

        foreach ($class->getDeclaredPropertiesIterator() as $property) {
            if (! ($property instanceof LocalColumnMetadata && $property->hasValueGenerator())) {
                continue;
            }

            $generatedProperties[] = $property;
        }

        switch (count($generatedProperties)) {
            case 0:
                $class->setValueGenerationPlan(new NoopValueGenerationPlan());
                break;

            case 1:
                $property = reset($generatedProperties);
                $executor = new ColumnValueGeneratorExecutor($property, $this->createPropertyValueGenerator($class, $property));

                $class->setValueGenerationPlan(new SingleValueGenerationPlan($class, $executor));
                break;

            default:
                $executors = [];

                foreach ($generatedProperties as $property) {
                    $executors[] = new ColumnValueGeneratorExecutor($property, $this->createPropertyValueGenerator($class, $property));
                }

                $class->setValueGenerationPlan(new CompositeValueGenerationPlan($class, $executors));
                break;
        }
    }

    private function createPropertyValueGenerator(
        ClassMetadata $class,
        LocalColumnMetadata $property
    ) : Sequencing\Generator {
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
