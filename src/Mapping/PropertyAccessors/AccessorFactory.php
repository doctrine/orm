<?php

namespace Doctrine\ORM\Mapping\PropertyAccessors;

use ReflectionClass;

class AccessorFactory
{
    public function createPropertyAccessor(ReflectionClass $reflectionClass, string $propertyName): PropertyAccessor
    {
        $reflectionProperty = $reflectionClass->getProperty($propertyName);
        $accessor           = ObjectCastPropertyAccessor::fromReflectionProperty($reflectionProperty);

        if ($reflectionProperty->hasType() && ! $reflectionProperty->getType()->allowsNull()) {
            $accessor = new TypedNoDefaultPropertyAccessor($accessor, $reflectionProperty);
        }

        if ($reflectionProperty->isReadOnly()) {
            $accessor = new ReadonlyAccessor($accessor, $reflectionProperty);
        }

        return $accessor;
    }
}
