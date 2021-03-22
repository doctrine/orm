<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Attribute;
use Doctrine\ORM\Mapping\Annotation;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function count;
use function is_subclass_of;

/**
 * @internal
 */
final class AttributeReader
{
    /** @var array<string,bool> */
    private array $isRepeatableAttribute = [];

    /** @return array<object> */
    public function getClassAnnotations(ReflectionClass $class): array
    {
        return $this->convertToAttributeInstances($class->getAttributes());
    }

    /** @return array<object>|object|null */
    public function getClassAnnotation(ReflectionClass $class, $annotationName)
    {
        return $this->getClassAnnotations($class)[$annotationName] ?? ($this->isRepeatable($annotationName) ? [] : null);
    }

    /** @return array<object> */
    public function getMethodAnnotations(ReflectionMethod $method): array
    {
        return $this->convertToAttributeInstances($method->getAttributes());
    }

    /** @return array<object>|object|null */
    public function getMethodAnnotation(ReflectionMethod $method, $annotationName)
    {
        return $this->getMethodAnnotations($method)[$annotationName] ?? ($this->isRepeatable($annotationName) ? [] : null);
    }

    /** @return array<object> */
    public function getPropertyAnnotations(ReflectionProperty $property): array
    {
        return $this->convertToAttributeInstances($property->getAttributes());
    }

    /** @return array<object>|object|null */
    public function getPropertyAnnotation(ReflectionProperty $property, $annotationName)
    {
        return $this->getPropertyAnnotations($property)[$annotationName] ?? ($this->isRepeatable($annotationName) ? [] : null);
    }

    /**
     * @param array<object> $attributes
     *
     * @return array<Annotation>
     */
    private function convertToAttributeInstances(array $attributes): array
    {
        $instances = [];

        foreach ($attributes as $attribute) {
            // Make sure we only get Doctrine Annotations
            if (! is_subclass_of($attribute->getName(), Annotation::class)) {
                continue;
            }

            $instance = $attribute->newInstance();

            if ($this->isRepeatable($attribute->getName())) {
                $instances[$attribute->getName()][] = $instance;
            } else {
                $instances[$attribute->getName()] = $instance;
            }
        }

        return $instances;
    }

    private function isRepeatable(string $attributeClassName): bool
    {
        if (isset($this->isRepeatableAttribute[$attributeClassName])) {
            return $this->isRepeatableAttribute[$attributeClassName];
        }

        $reflectionClass = new ReflectionClass($attributeClassName);
        $attribute       = $reflectionClass->getAttributes()[0]->newInstance();

        return $this->isRepeatableAttribute[$attributeClassName] = ($attribute->flags & Attribute::IS_REPEATABLE) > 0;
    }
}
