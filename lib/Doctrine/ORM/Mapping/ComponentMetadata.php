<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use ArrayIterator;
use Doctrine\ORM\Reflection\ReflectionService;
use ReflectionClass;
use ReflectionException;

/**
 * A <tt>ComponentMetadata</tt> instance holds object-relational property mapping.
 */
abstract class ComponentMetadata
{
    /** @var string */
    protected $className;

    /** @var ComponentMetadata|null */
    protected $parent;

    /**
     * The ReflectionClass instance of the component class.
     *
     * @var ReflectionClass|null
     */
    protected $reflectionClass;

    /** @var CacheMetadata|null */
    protected $cache;

    /** @var Property[] */
    protected $properties = [];

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function getClassName() : string
    {
        return $this->className;
    }

    public function setParent(ComponentMetadata $parent) : void
    {
        $this->parent = $parent;
    }

    public function getParent() : ?ComponentMetadata
    {
        return $this->parent;
    }

    public function wakeupReflection(ReflectionService $reflectionService) : void
    {
        // Restore ReflectionClass and properties
        $this->reflectionClass = $reflectionService->getClass($this->className);

        if (! $this->reflectionClass) {
            return;
        }

        $this->className = $this->reflectionClass->getName();

        foreach ($this->properties as $property) {
            /** @var Property $property */
            $property->wakeupReflection($reflectionService);
        }
    }

    public function getReflectionClass() : ?ReflectionClass
    {
        return $this->reflectionClass;
    }

    public function setCache(?CacheMetadata $cache = null) : void
    {
        $this->cache = $cache;
    }

    public function getCache() : ?CacheMetadata
    {
        return $this->cache;
    }

    /**
     * @return iterable|Property[]
     */
    public function getPropertiesIterator() : iterable
    {
        foreach ($this->properties as $name => $property) {
            yield $name => $property;
        }
    }

    /**
     * @throws ReflectionException
     * @throws MappingException
     */
    public function addProperty(Property $property) : void
    {
        $className    = $this->getClassName();
        $propertyName = $property->getName();

        if ($this->hasProperty($propertyName)) {
            throw MappingException::duplicateProperty($className, $this->getProperty($propertyName));
        }

        $property->setDeclaringClass($this);

        $this->properties[$propertyName] = $property;
    }

    public function hasProperty(string $propertyName) : bool
    {
        return isset($this->properties[$propertyName]);
    }

    public function getProperty(string $propertyName) : ?Property
    {
        return $this->properties[$propertyName] ?? null;
    }
}
