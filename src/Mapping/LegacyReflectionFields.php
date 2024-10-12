<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use ArrayAccess;
use Doctrine\Deprecations\Deprecation;
use Doctrine\Persistence\Mapping\ReflectionService;
use Doctrine\Persistence\Reflection\EnumReflectionProperty;
use Generator;
use IteratorAggregate;
use OutOfBoundsException;
use ReflectionProperty;
use Traversable;

use function array_keys;
use function str_contains;
use function str_replace;

class LegacyReflectionFields implements ArrayAccess, IteratorAggregate
{
    /** @var array<string, ReflectionProperty> */
    private array $reflFields = [];

    public function __construct(private ClassMetadata $classMetadata, private ReflectionService $reflectionService)
    {
    }

    /** @param string $offset */
    public function offsetExists($offset): bool
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/11659',
            'Access to ClassMetadata::$reflFields is deprecated and will be removed in Doctrine ORM 4.0.',
        );

        return isset($this->classMetadata->propertyAccessors[$offset]);
    }

    /** @param string $field */
    public function offsetGet($field): mixed
    {
        if (isset($this->reflFields[$field])) {
            return $this->reflFields[$field];
        }

        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/11659',
            'Access to ClassMetadata::$reflFields is deprecated and will be removed in Doctrine ORM 4.0.',
        );

        if (isset($this->classMetadata->propertyAccessors[$field])) {
            $fieldName = str_contains($field, '.') ? $this->classMetadata->fieldMappings[$field]->originalField : $field;
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
                    $parentField = str_replace('.' . $fieldName, '', $field);

                    if (! str_contains($parentField, '.')) {
                        $parentClass = $this->classMetadata->name;
                    } else {
                        $parentClass = $this->classMetadata->fieldMappings[$parentField]->originalClass;
                    }

                    $this->reflFields[$field] = new ReflectionEmbeddedProperty(
                        $this->getAccessibleProperty($parentClass, $parentField),
                        $this->reflFields[$field],
                        $this->classMetadata->fieldMappings[$field]->originalClass,
                    );
                }
            }

            return $this->reflFields[$field];
        }

        throw new OutOfBoundsException('Unknown field: ' . $this->classMetadata->name . ' ::$' . $field);
    }

    /**
     * @param string $offset
     * @param ReflectionProperty $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->reflFields[$offset] = $value;
    }

    /** @param string $offset */
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

    /** @return Generator<string, ReflectionProperty> */
    public function getIterator(): Traversable
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/11659',
            'Access to ClassMetadata::$reflFields is deprecated and will be removed in Doctrine ORM 4.0.',
        );

        $keys = array_keys($this->classMetadata->propertyAccessors);

        foreach ($keys as $key) {
            yield $key => $this->offsetGet($key);
        }
    }
}
