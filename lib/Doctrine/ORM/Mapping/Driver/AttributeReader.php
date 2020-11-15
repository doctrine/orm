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

// TODO: Should we move this to doctrine/annotations?
class AttributeReader
{
    /** @var array<string,bool> */
    private array $isRepeatableAttribute = [];

    /** @return array<object> */
    public function getClassAnnotations(ReflectionClass $class): array
    {
        return $this->convertToAttributeInstances($class->getAttributes());
    }

    /** @return array<object>|?object */
    // phpcs:ignore
    public function getClassAnnotation(ReflectionClass $class, $annotationName)
    {
        return $this->getClassAnnotations($class)[$annotationName] ?? ($this->isRepeatable($annotationName) ? [] : null);
    }

    /** @return array<object> */
    public function getMethodAnnotations(ReflectionMethod $method): array
    {
        return $this->convertToAttributeInstances($method->getAttributes());
    }

    /** @return array<object>|?object */
    // phpcs:ignore
    public function getMethodAnnotation(ReflectionMethod $method, $annotationName)
    {
        return $this->getMethodAnnotations($method)[$annotationName] ?? ($this->isRepeatable($annotationName) ? [] : null);
    }

    /** @return array<object> */
    public function getPropertyAnnotations(ReflectionProperty $property): array
    {
        return $this->convertToAttributeInstances($property->getAttributes());
    }

    /** @return array<object>|?object */
    // phpcs:ignore
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

            $attributeClassName = $attribute->getName();
            $instance           = new $attributeClassName();
            $arguments          = $attribute->getArguments();

            // unnamed argument is automatically "value" in Doctrine Annotations
            if (count($arguments) >= 1 && isset($arguments[0])) {
                $arguments['value'] = $arguments[0];
                unset($arguments[0]);
            }

            // This works using the old Annotation, but will probably break Attribute IDE autocomplete support
            foreach ($arguments as $name => $value) {
                $instance->$name = $value;
            }

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
