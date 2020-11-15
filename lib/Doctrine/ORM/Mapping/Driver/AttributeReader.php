<?php

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\Annotation;

// TODO: Should we move this to doctrine/annotations?
class AttributeReader implements Reader
{
    /** @var array<string,bool> */
    private $isRepeatableAttribute = [];

    function getClassAnnotations(\ReflectionClass $class)
    {
        return $this->convertToAttributeInstances($class->getAttributes());
    }

    function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        return $this->getClassAnnotations($class)[$annotationName] ?? ($this->isRepeatable($annotationName) ? [] : null);
    }

    function getMethodAnnotations(\ReflectionMethod $method)
    {
        return $this->convertToAttributeInstances($method->getAttributes());
    }

    function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        return $this->getMethodAnnotations($method)[$annotationName] ?? ($this->isRepeatable($annotationName) ? [] : null);
    }

    function getPropertyAnnotations(\ReflectionProperty $property)
    {
        return $this->convertToAttributeInstances($property->getAttributes());
    }

    function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        return $this->getPropertyAnnotations($property)[$annotationName] ?? ($this->isRepeatable($annotationName) ? [] : null);
    }

    private function convertToAttributeInstances(array $attributes)
    {
        $instances = [];

        foreach ($attributes as $attribute) {
            // Make sure we only get Doctrine Annotations
            if (is_subclass_of($attribute->getName(), Annotation::class)) {
                $attributeClassName = $attribute->getName();
                $instance = new $attributeClassName;
                $arguments = $attribute->getArguments();

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
        }

        return $instances;
    }

    private function isRepeatable(string $attributeClassName) : bool
    {
        if (isset($this->isRepeatableAttribute[$attributeClassName])) {
            return $this->isRepeatableAttribute[$attributeClassName];
        }

        $reflectionClass = new \ReflectionClass($attributeClassName);
        $attribute = $reflectionClass->getAttributes()[0]->newInstance();

        return $this->isRepeatableAttribute[$attributeClassName] = $attribute->flags & \Attribute::IS_REPEATABLE;
    }
}