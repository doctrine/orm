<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Attribute;
use Doctrine\ORM\Mapping\Annotation;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function assert;
use function is_string;
use function is_subclass_of;

/**
 * @internal
 */
final class AttributeReader
{
    /** @var array<string,bool> */
    private array $isRepeatableAttribute = [];

    /** @return array<Annotation|RepeatableAttributeCollection> */
    public function getClassAnnotations(ReflectionClass $class): array
    {
        return $this->convertToAttributeInstances($class->getAttributes());
    }

    /** @return Annotation|RepeatableAttributeCollection|null */
    public function getClassAnnotation(ReflectionClass $class, $annotationName)
    {
        return $this->getClassAnnotations($class)[$annotationName]
            ?? ($this->isRepeatable($annotationName) ? new RepeatableAttributeCollection() : null);
    }

    /** @return array<Annotation|RepeatableAttributeCollection> */
    public function getMethodAnnotations(ReflectionMethod $method): array
    {
        return $this->convertToAttributeInstances($method->getAttributes());
    }

    /** @return Annotation|RepeatableAttributeCollection|null */
    public function getMethodAnnotation(ReflectionMethod $method, $annotationName)
    {
        return $this->getMethodAnnotations($method)[$annotationName]
            ?? ($this->isRepeatable($annotationName) ? new RepeatableAttributeCollection() : null);
    }

    /** @return array<Annotation|RepeatableAttributeCollection> */
    public function getPropertyAnnotations(ReflectionProperty $property): array
    {
        return $this->convertToAttributeInstances($property->getAttributes());
    }

    /** @return Annotation|RepeatableAttributeCollection|null */
    public function getPropertyAnnotation(ReflectionProperty $property, $annotationName)
    {
        return $this->getPropertyAnnotations($property)[$annotationName]
            ?? ($this->isRepeatable($annotationName) ? new RepeatableAttributeCollection() : null);
    }

    /**
     * @param array<ReflectionAttribute> $attributes
     *
     * @return array<Annotation|RepeatableAttributeCollection>
     */
    private function convertToAttributeInstances(array $attributes): array
    {
        $instances = [];

        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            assert(is_string($attributeName));
            // Make sure we only get Doctrine Annotations
            if (! is_subclass_of($attributeName, Annotation::class)) {
                continue;
            }

            $instance = $attribute->newInstance();
            assert($instance instanceof Annotation);

            if ($this->isRepeatable($attributeName)) {
                if (! isset($instances[$attributeName])) {
                    $instances[$attributeName] = new RepeatableAttributeCollection();
                }

                $collection = $instances[$attributeName];
                assert($collection instanceof RepeatableAttributeCollection);
                $collection[] = $instance;
            } else {
                $instances[$attributeName] = $instance;
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
