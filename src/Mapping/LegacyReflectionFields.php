<?php

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\Mapping\PropertyAccessors\EmbeddablePropertyAccessor;
use Doctrine\Persistence\Mapping\ReflectionService;
use Doctrine\Persistence\Reflection\EnumReflectionProperty;
use ReflectionClass;
use ReflectionProperty;
use Traversable;

class LegacyReflectionFields implements \ArrayAccess, \IteratorAggregate
{
    private array $reflFields = [];

    public function __construct(private ClassMetadata $classMetadata, private ReflectionService $reflectionService)
    {
    }

    public function offsetExists($offset): bool
    {
        return isset($this->classMetadata->propertyAccessors[$offset]);
    }

    public function offsetGet($field): mixed
    {
        if (isset($this->reflFields[$field])) {
            return $this->reflFields[$field];
        }

        if (isset($this->classMetadata->propertyAccessors[$field])) {
            $fieldName = str_contains('.', $field) ? $this->classMetadata->fieldMappings[$field]->originalField : $field;
            $className = $this->classMetadata->name;

            if (isset($this->classMetadata->fieldMappings[$field]) && $this->classMetadata->fieldMappings[$field]->originalClass !== null) {
                $className = $this->classMetadata->fieldMappings[$field]->originalClass;
            } elseif (isset($this->classMetadata->fieldMappings[$field]) && $this->classMetadata->fieldMappings[$field]->declared !== null) {
                $className = $this->classMetadata->fieldMappings[$field]->declared;
            } elseif (isset($this->classMetadata->associationMappings[$field]) && $this->classMetadata->associationMappings[$field]->declared !== null) {
                $className = $this->classMetadata->associationMappings[$field]->declared;
            } elseif (isset($this->classMetadata->embeddedClasses[$field]) && $this->classMetadata->embeddedClasses[$field]->declared !== null) {
                $className = $this->classMetadata->embeddedClasses[$field]->declared;
            }

            $this->reflFields[$field] = $this->getAccessibleProperty($className, $fieldName);

            if (isset($this->classMetadata->fieldMappings[$field])) {
                if ($this->classMetadata->fieldMappings[$field]->enumType !== null) {
                    $this->reflFields[$field] = new EnumReflectionProperty(
                        $this->reflFields[$field],
                        $this->classMetadata->fieldMappings[$field]->enumType,
                    );
                }

                if ($this->classMetadata->fieldMappings[$field]->originalField !== null) {
                    $this->reflFields[$field] = new ReflectionEmbeddedProperty(
                        $this->reflFields[$field],
                        $this->getAccessibleProperty($this->classMetadata->fieldMappings[$field]->originalClass, $this->classMetadata->fieldMappings[$field]->originalField),
                        $this->classMetadata->embeddedClasses[$fieldName]->class,
                    );
                }
            }

            return $this->reflFields[$field];
        }

        throw new \OutOfBoundsException('Unknown field: ' . $this->classMetadata->name .' ::$' . $field);
    }

    public function offsetSet($offset, $value): void
    {
        $this->reflFields[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->reflFields[$offset]);
    }

    /** @psalm-param class-string $class */
    private function getAccessibleProperty(string $class, string $field): ReflectionProperty|null
    {
        $reflectionProperty = $this->reflectionService->getAccessibleProperty($class, $field);
        if ($reflectionProperty?->isReadOnly()) {
            $declaringClass = $reflectionProperty->class;
            if ($declaringClass !== $class) {
                $reflectionProperty = $this->reflectionService->getAccessibleProperty($declaringClass, $field);
            }

            if ($reflectionProperty !== null) {
                $reflectionProperty = new ReflectionReadonlyProperty($reflectionProperty);
            }
        }

        return $reflectionProperty;
    }

    public function getIterator(): Traversable
    {
        $keys = array_keys($this->classMetadata->propertyAccessors);

        foreach ($keys as $key) {
            yield $key => $this->offsetGet($key);
        }
    }
}
