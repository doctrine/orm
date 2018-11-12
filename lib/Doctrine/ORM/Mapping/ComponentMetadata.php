<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use ArrayIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

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

    public function __construct(string $className, ClassMetadataBuildingContext $metadataBuildingContext)
    {
        $reflectionService = $metadataBuildingContext->getReflectionService();

        $this->reflectionClass = $reflectionService->getClass($className);
        $this->className       = $this->reflectionClass ? $this->reflectionClass->getName() : $className;
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

        if ($this->reflectionClass) {
            $reflectionProperty = new ReflectionProperty($className, $propertyName);

            $reflectionProperty->setAccessible(true);

            $property->setReflectionProperty($reflectionProperty);
        }

        $this->properties[$propertyName] = $property;
    }

    public function getProperty(string $propertyName) : ?Property
    {
        if (isset($this->properties[$propertyName])) {
            return $this->properties[$propertyName];
        }

        return null;
    }

    public function hasProperty(string $propertyName) : bool
    {
        return isset($this->properties[$propertyName]);
    }

    /**
     * @return ArrayIterator|ColumnMetadata[]
     */
    public function getColumnsIterator() : ArrayIterator
    {
        $iterator = new ArrayIterator();

        foreach ($this->getPropertiesIterator() as $property) {
            switch (true) {
                case $property instanceof FieldMetadata:
                    $iterator->offsetSet($property->getColumnName(), $property);
                    break;

                case $property instanceof ToOneAssociationMetadata && $property->isOwningSide():
                    foreach ($property->getJoinColumns() as $joinColumn) {
                        /** @var JoinColumnMetadata $joinColumn */
                        $iterator->offsetSet($joinColumn->getColumnName(), $joinColumn);
                    }

                    break;
            }
        }

        return $iterator;
    }
}
