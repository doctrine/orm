<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory as PersistenceClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\MappingException as PersistenceMappingException;
use Doctrine\Common\Persistence\Mapping\MappingException as CommonMappingException;
use Doctrine\DBAL\Platforms;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\Exception\TableGeneratorNotImplementedYet;
use Doctrine\ORM\Mapping\Exception\UnknownGeneratorType;
use Doctrine\ORM\Reflection\ReflectionService;
use Doctrine\ORM\Reflection\RuntimeReflectionService;
use Doctrine\ORM\Sequencing\Planning\CompositeValueGenerationPlan;
use Doctrine\ORM\Sequencing\Planning\NoopValueGenerationPlan;
use Doctrine\ORM\Sequencing\Planning\SingleValueGenerationPlan;
use Doctrine\ORM\Sequencing\Planning\ValueGenerationExecutor;
use Doctrine\ORM\Utility\StaticClassNameConverter;
use InvalidArgumentException;
use ReflectionException;
use function array_map;
use function array_reverse;
use function count;
use function in_array;
use function reset;
use function sprintf;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping information of a class which describes how a class should be mapped
 * to a relational database.
 */
class ClassMetadataFactory implements PersistenceClassMetadataFactory
{
    /**
     * Salt used by specific Object Manager implementation.
     *
     * @var string
     */
    protected $cacheSalt = '$CLASSMETADATA';

    /** @var bool */
    protected $initialized = false;

    /** @var ReflectionService|null */
    protected $reflectionService;

    /** @var EntityManagerInterface|null */
    private $em;

    /** @var AbstractPlatform */
    private $targetPlatform;

    /** @var Driver\MappingDriver */
    private $driver;

    /** @var EventManager */
    private $evm;

    /** @var Cache|null */
    private $cacheDriver;

    /** @var ClassMetadata[] */
    private $loadedMetadata = [];

    /**
     * Sets the entity manager used to build ClassMetadata instances.
     */
    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Sets the cache driver used by the factory to cache ClassMetadata instances.
     */
    public function setCacheDriver(?Cache $cacheDriver = null) : void
    {
        $this->cacheDriver = $cacheDriver;
    }

    /**
     * Gets the cache driver used by the factory to cache ClassMetadata instances.
     */
    public function getCacheDriver() : ?Cache
    {
        return $this->cacheDriver;
    }

    /**
     * Returns an array of all the loaded metadata currently in memory.
     *
     * @return ClassMetadata[]
     */
    public function getLoadedMetadata() : array
    {
        return $this->loadedMetadata;
    }

    /**
     * Sets the reflectionService.
     */
    public function setReflectionService(ReflectionService $reflectionService) : void
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Gets the reflection service associated with this metadata factory.
     */
    public function getReflectionService() : ReflectionService
    {
        if ($this->reflectionService === null) {
            $this->reflectionService = new RuntimeReflectionService();
        }

        return $this->reflectionService;
    }

    /**
     * {@inheritDoc}
     */
    public function isTransient($className) : bool
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        $entityClassName = StaticClassNameConverter::getRealClass($className);

        return $this->getDriver()->isTransient($entityClassName);
    }

    /**
     * Checks whether the factory has the metadata for a class loaded already.
     *
     * @param string $className
     *
     * @return bool TRUE if the metadata of the class in question is already loaded, FALSE otherwise.
     */
    public function hasMetadataFor($className) : bool
    {
        return isset($this->loadedMetadata[$className]);
    }

    /**
     * Sets the metadata descriptor for a specific class.
     *
     * NOTE: This is only useful in very special cases, like when generating proxy classes.
     *
     * @param string        $className
     * @param ClassMetadata $class
     */
    public function setMetadataFor($className, $class) : void
    {
        $this->loadedMetadata[$className] = $class;
    }

    /**
     * Forces the factory to load the metadata of all classes known to the underlying
     * mapping driver.
     *
     * @return ClassMetadata[] The ClassMetadata instances of all mapped classes.
     *
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MappingException
     * @throws PersistenceMappingException
     * @throws ORMException
     */
    public function getAllMetadata() : array
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        $driver   = $this->getDriver();
        $metadata = [];

        foreach ($driver->getAllClassNames() as $className) {
            $metadata[] = $this->getMetadataFor($className);
        }

        return $metadata;
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
    protected function getDriver() : Driver\MappingDriver
    {
        return $this->driver;
    }

    /**
     * {@inheritdoc}
     */
    protected function isEntity(ClassMetadata $class) : bool
    {
        return isset($class->isMappedSuperclass) && $class->isMappedSuperclass === false;
    }

    /**
     * Gets the class metadata descriptor for a class.
     *
     * @param string $className The name of the class.
     *
     * @throws InvalidArgumentException
     * @throws PersistenceMappingException
     * @throws ORMException
     */
    public function getMetadataFor($className) : ClassMetadata
    {
        if (isset($this->loadedMetadata[$className])) {
            return $this->loadedMetadata[$className];
        }

        $entityClassName = StaticClassNameConverter::getRealClass($className);

        if (isset($this->loadedMetadata[$entityClassName])) {
            // We do not have the alias name in the map, include it
            return $this->loadedMetadata[$className] = $this->loadedMetadata[$entityClassName];
        }

        $metadataBuildingContext = $this->newClassMetadataBuildingContext();
        $loadingException        = null;

        try {
            if ($this->cacheDriver) {
                $cached = $this->cacheDriver->fetch($entityClassName . $this->cacheSalt);

                if ($cached instanceof ClassMetadata) {
                    $this->loadedMetadata[$entityClassName] = $cached;

                    $cached->wakeupReflection($metadataBuildingContext->getReflectionService());
                } else {
                    foreach ($this->loadMetadata($entityClassName, $metadataBuildingContext) as $loadedClass) {
                        $loadedClassName = $loadedClass->getClassName();

                        $this->cacheDriver->save($loadedClassName . $this->cacheSalt, $loadedClass, null);
                    }
                }
            } else {
                $this->loadMetadata($entityClassName, $metadataBuildingContext);
            }
        } catch (PersistenceMappingException $loadingException) {
            $fallbackMetadataResponse = $this->onNotFoundMetadata($entityClassName, $metadataBuildingContext);

            if (! $fallbackMetadataResponse) {
                throw $loadingException;
            }

            $this->loadedMetadata[$entityClassName] = $fallbackMetadataResponse;
        }

        if ($className !== $entityClassName) {
            // We do not have the alias name in the map, include it
            $this->loadedMetadata[$className] = $this->loadedMetadata[$entityClassName];
        }

        $metadataBuildingContext->validate();

        return $this->loadedMetadata[$entityClassName];
    }

    /**
     * Loads the metadata of the class in question and all it's ancestors whose metadata
     * is still not loaded.
     *
     * Important: The class $name does not necessarily exist at this point here.
     * Scenarios in a code-generation setup might have access to XML
     * Mapping files without the actual PHP code existing here. That is why the
     * {@see Doctrine\ORM\Reflection\ReflectionService} interface
     * should be used for reflection.
     *
     * @param string $name The name of the class for which the metadata should get loaded.
     *
     * @return ClassMetadata[]
     *
     * @throws InvalidArgumentException
     * @throws ORMException
     */
    protected function loadMetadata(string $name, ClassMetadataBuildingContext $metadataBuildingContext) : array
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        $loaded          = [];
        $parentClasses   = $this->getParentClasses($name);
        $parentClasses[] = $name;

        // Move down the hierarchy of parent classes, starting from the topmost class
        $parent = null;

        foreach ($parentClasses as $className) {
            if (isset($this->loadedMetadata[$className])) {
                $parent = $this->loadedMetadata[$className];

                continue;
            }

            $class = $this->doLoadMetadata($className, $parent, $metadataBuildingContext);

            $this->loadedMetadata[$className] = $class;

            $parent   = $class;
            $loaded[] = $class;
        }

        array_map([$this, 'resolveDiscriminatorValue'], $loaded);

        return $loaded;
    }

    /**
     * Gets an array of parent classes for the given entity class.
     *
     * @param string $name
     *
     * @return string[]
     *
     * @throws InvalidArgumentException
     */
    protected function getParentClasses($name) : array
    {
        // Collect parent classes, ignoring transient (not-mapped) classes.
        $parentClasses = [];

        foreach (array_reverse($this->getReflectionService()->getParentClasses($name)) as $parentClass) {
            if (! $this->getDriver()->isTransient($parentClass)) {
                $parentClasses[] = $parentClass;
            }
        }

        return $parentClasses;
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
    ) : ?ComponentMetadata {
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->driver->loadMetadataForClass($className, $parent, $metadataBuildingContext);

        $this->completeIdentifierGeneratorMappings($classMetadata);

        if ($this->evm->hasListeners(Events::loadClassMetadata)) {
            $eventArgs = new LoadClassMetadataEventArgs($classMetadata, $this->em);

            $this->evm->dispatchEvent(Events::loadClassMetadata, $eventArgs);
        }

        $this->buildValueGenerationPlan($classMetadata);
        $this->validateRuntimeMetadata($classMetadata, $parent);

        return $classMetadata;
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
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MappingException
     * @throws PersistenceMappingException
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
     * Completes the ID generator mapping. If "auto" is specified we choose the generator
     * most appropriate for the targeted database platform.
     *
     * @throws ORMException
     */
    private function completeIdentifierGeneratorMappings(ClassMetadata $class) : void
    {
        foreach ($class->getPropertiesIterator() as $property) {
            if (! $property instanceof FieldMetadata /*&& ! $property instanceof AssociationMetadata*/) {
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

        if (in_array($generator->getType(), [GeneratorType::AUTO, GeneratorType::IDENTITY], true)) {
            $generatorType = $platform->prefersSequences() || $platform->usesSequenceEmulatedIdentityColumns()
                ? GeneratorType::SEQUENCE
                : ($platform->prefersIdentityColumns() ? GeneratorType::IDENTITY : GeneratorType::TABLE);

            $generator = new ValueGeneratorMetadata($generatorType, $field->getValueGenerator()->getDefinition());

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
                throw TableGeneratorNotImplementedYet::create();
                break;

            case GeneratorType::CUSTOM:
            case GeneratorType::IDENTITY:
            case GeneratorType::NONE:
                break;

            default:
                throw UnknownGeneratorType::create($generator->getType());
        }
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
        $valueGenerationExecutorList = $this->buildValueGenerationExecutorList($class);

        switch (count($valueGenerationExecutorList)) {
            case 0:
                $valueGenerationPlan = new NoopValueGenerationPlan();
                break;

            case 1:
                $valueGenerationPlan = new SingleValueGenerationPlan($class, reset($valueGenerationExecutorList));
                break;

            default:
                $valueGenerationPlan = new CompositeValueGenerationPlan($class, $valueGenerationExecutorList);
                break;
        }

        $class->setValueGenerationPlan($valueGenerationPlan);
    }

    /**
     * @return ValueGenerationExecutor[]
     */
    private function buildValueGenerationExecutorList(ClassMetadata $class) : array
    {
        $executors = [];

        foreach ($class->getPropertiesIterator() as $property) {
            $executor = $property->getValueGenerationExecutor($this->getTargetPlatform());

            if ($executor instanceof ValueGenerationExecutor) {
                $executors[$property->getName()] = $executor;
            }
        }

        return $executors;
    }
}
