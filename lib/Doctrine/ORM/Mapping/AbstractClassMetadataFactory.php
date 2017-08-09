<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\MappingException as CommonMappingException;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Reflection\ReflectionService;
use Doctrine\ORM\Reflection\RuntimeReflectionService;
use ReflectionException;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping information of a class which describes how a class should be mapped
 * to a relational database.
 *
 * This class was abstracted from the ORM ClassMetadataFactory.
 *
 * @since  2.2
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 */
abstract class AbstractClassMetadataFactory implements ClassMetadataFactory
{
    /**
     * Salt used by specific Object Manager implementation.
     *
     * @var string
     */
    protected $cacheSalt = '$CLASSMETADATA';

    /**
     * @var \Doctrine\Common\Cache\Cache|null
     */
    private $cacheDriver;

    /**
     * @var ClassMetadata[]
     */
    private $loadedMetadata = [];

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * @var ReflectionService|null
     */
    private $reflectionService;

    /**
     * Sets the cache driver used by the factory to cache ClassMetadata instances.
     *
     * @param \Doctrine\Common\Cache\Cache $cacheDriver
     *
     * @return void
     */
    public function setCacheDriver(?Cache $cacheDriver = null) : void
    {
        $this->cacheDriver = $cacheDriver;
    }

    /**
     * Gets the cache driver used by the factory to cache ClassMetadata instances.
     *
     * @return Cache|null
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
     * Forces the factory to load the metadata of all classes known to the underlying
     * mapping driver.
     *
     * @return array The ClassMetadata instances of all mapped classes.
     *
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     * @throws MappingException
     */
    public function getAllMetadata() : array
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        $driver = $this->getDriver();
        $metadata = [];

        foreach ($driver->getAllClassNames() as $className) {
            $metadata[] = $this->getMetadataFor($className);
        }

        return $metadata;
    }

    /**
     * Lazy initialization of this stuff, especially the metadata driver,
     * since these are not needed at all when a metadata cache is active.
     *
     * @return void
     */
    abstract protected function initialize() : void;

    /**
     * Gets the fully qualified class-name from the namespace alias.
     *
     * @param string $namespaceAlias
     * @param string $simpleClassName
     *
     * @return string
     */
    abstract protected function getFqcnFromAlias($namespaceAlias, $simpleClassName) : string;

    /**
     * Returns the mapping driver implementation.
     *
     * @return Driver\MappingDriver
     */
    abstract protected function getDriver() : Driver\MappingDriver;

    /**
     * Wakes up reflection after ClassMetadata gets unserialized from cache.
     *
     * @param ClassMetadata     $class
     * @param ReflectionService $reflService
     *
     * @return void
     */
    abstract protected function wakeupReflection(ClassMetadata $class, ReflectionService $reflService) : void;

    /**
     * Initializes Reflection after ClassMetadata was constructed.
     *
     * @param ClassMetadata     $class
     * @param ReflectionService $reflService
     *
     * @return void
     */
    abstract protected function initializeReflection(ClassMetadata $class, ReflectionService $reflService) : void;

    /**
     * Checks whether the class metadata is an entity.
     *
     * This method should return false for mapped superclasses or embedded classes.
     *
     * @param ClassMetadata $class
     *
     * @return bool
     */
    abstract protected function isEntity(ClassMetadata $class) : bool;

    /**
     * Gets the class metadata descriptor for a class.
     *
     * @param string $className The name of the class.
     *
     * @return ClassMetadata
     *
     * @throws \InvalidArgumentException
     * @throws ReflectionException
     * @throws MappingException
     */
    public function getMetadataFor($className) : ClassMetadata
    {
        if (isset($this->loadedMetadata[$className])) {
            return $this->loadedMetadata[$className];
        }

        // Check for namespace alias
        if (strpos($className, ':') !== false) {
            [$namespaceAlias, $simpleClassName] = explode(':', $className, 2);

            $realClassName = $this->getFqcnFromAlias($namespaceAlias, $simpleClassName);
        } else {
            $realClassName = ClassUtils::getRealClass($className);
        }

        if (isset($this->loadedMetadata[$realClassName])) {
            // We do not have the alias name in the map, include it
            return $this->loadedMetadata[$className] = $this->loadedMetadata[$realClassName];
        }

        $metadataBuildingContext = $this->newClassMetadataBuildingContext();
        $loadingException        = null;

        try {
            if ($this->cacheDriver) {
                $cached = $this->cacheDriver->fetch($realClassName . $this->cacheSalt);

                if ($cached instanceof ClassMetadata) {
                    $this->loadedMetadata[$realClassName] = $cached;

                    $this->wakeupReflection($cached, $this->getReflectionService());
                } else {
                    foreach ($this->loadMetadata($realClassName, $metadataBuildingContext) as $loadedClassName) {
                        $this->cacheDriver->save(
                            $loadedClassName . $this->cacheSalt,
                            $this->loadedMetadata[$loadedClassName],
                            null
                        );
                    }
                }
            } else {
                $this->loadMetadata($realClassName, $metadataBuildingContext);
            }
        } catch (CommonMappingException $loadingException) {
            if (! $fallbackMetadataResponse = $this->onNotFoundMetadata($realClassName, $metadataBuildingContext)) {
                throw $loadingException;
            }

            $this->loadedMetadata[$realClassName] = $fallbackMetadataResponse;
        }

        if ($className !== $realClassName) {
            // We do not have the alias name in the map, include it
            $this->loadedMetadata[$className] = $this->loadedMetadata[$realClassName];
        }

        $metadataBuildingContext->validate();

        return $this->loadedMetadata[$className];
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
     *
     * @return void
     */
    public function setMetadataFor($className, $class) : void
    {
        $this->loadedMetadata[$className] = $class;
    }

    /**
     * Gets an array of parent classes for the given entity class.
     *
     * @param string $name
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function getParentClasses($name) : array
    {
        // Collect parent classes, ignoring transient (not-mapped) classes.
        $parentClasses = [];

        foreach (array_reverse($this->getReflectionService()->getParentClasses($name)) as $parentClass) {
            if ( ! $this->getDriver()->isTransient($parentClass)) {
                $parentClasses[] = $parentClass;
            }
        }

        return $parentClasses;
    }

    /**
     * Loads the metadata of the class in question and all it's ancestors whose metadata
     * is still not loaded.
     *
     * Important: The class $name does not necessarily exist at this point here.
     * Scenarios in a code-generation setup might have access to XML/YAML
     * Mapping files without the actual PHP code existing here. That is why the
     * {@see Doctrine\Common\Persistence\Mapping\ReflectionService} interface
     * should be used for reflection.
     *
     * @param string $name The name of the class for which the metadata should
     *                                                              get loaded.
     * @param ClassMetadataBuildingContext $metadataBuildingContext
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function loadMetadata(string $name, ClassMetadataBuildingContext $metadataBuildingContext) : array
    {
        if ( ! $this->initialized) {
            $this->initialize();
        }

        $loaded = [];

        $parentClasses = $this->getParentClasses($name);
        $parentClasses[] = $name;

        // Move down the hierarchy of parent classes, starting from the topmost class
        $parent      = null;
        $reflService = $this->getReflectionService();

        foreach ($parentClasses as $className) {
            if (isset($this->loadedMetadata[$className])) {
                $parent = $this->loadedMetadata[$className];

                continue;
            }

            $class = $this->newClassMetadataInstance($className, $metadataBuildingContext);

            if ($parent) {
                $class->setParent($parent);
            }

            $this->initializeReflection($class, $reflService);
            $this->doLoadMetadata($class, $metadataBuildingContext);

            $this->loadedMetadata[$className] = $class;

            $parent = $class;

            $this->wakeupReflection($class, $reflService);

            $loaded[] = $className;
        }

        return $loaded;
    }

    /**
     * Provides a fallback hook for loading metadata when loading failed due to reflection/mapping exceptions
     *
     * Override this method to implement a fallback strategy for failed metadata loading
     *
     * @param string                       $className
     * @param ClassMetadataBuildingContext $metadataBuildingContext
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata|null
     */
    protected function onNotFoundMetadata(
        string $className,
        ClassMetadataBuildingContext $metadataBuildingContext
    ) : ?ClassMetadata
    {
        return null;
    }

    /**
     * Actually loads the metadata from the underlying metadata.
     *
     * @param ClassMetadata                $class
     * @param ClassMetadataBuildingContext $metadataBuildingContext
     *
     * @return void
     */
    abstract protected function doLoadMetadata(
        ClassMetadata $class,
        ClassMetadataBuildingContext $metadataBuildingContext
    ) : void;

    /**
     * Creates a new ClassMetadata instance for the given class name.
     *
     * @param string                       $className
     * @param ClassMetadataBuildingContext $metadataBuildingContext
     *
     * @return ClassMetadata
     */
    abstract protected function newClassMetadataInstance(
        string $className,
        ClassMetadataBuildingContext $metadataBuildingContext
    ) : ClassMetadata;

    /**
     * Creates a new ClassMetadataBuildingContext instance.
     *
     * @return ClassMetadataBuildingContext
     */
    abstract protected function newClassMetadataBuildingContext() : ClassMetadataBuildingContext;

    /**
     * {@inheritDoc}
     */
    public function isTransient($class) : bool
    {
        if ( ! $this->initialized) {
            $this->initialize();
        }

        // Check for namespace alias
        if (strpos($class, ':') !== false) {
            [$namespaceAlias, $simpleClassName] = explode(':', $class, 2);

            $class = $this->getFqcnFromAlias($namespaceAlias, $simpleClassName);
        }

        return $this->getDriver()->isTransient($class);
    }

    /**
     * Sets the reflectionService.
     *
     * @param ReflectionService $reflectionService
     *
     * @return void
     */
    public function setReflectionService(ReflectionService $reflectionService) : void
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Gets the reflection service associated with this metadata factory.
     *
     * @return ReflectionService
     */
    public function getReflectionService() : ReflectionService
    {
        if ($this->reflectionService === null) {
            $this->reflectionService = new RuntimeReflectionService();
        }

        return $this->reflectionService;
    }
}
