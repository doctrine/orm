<?php

namespace Doctrine\ORM\Mapping\PropertyAccessors;

use ReflectionProperty;

use function ltrim;

class ObjectCastPropertyAccessor implements PropertyAccessor
{
    public function fromNames(string $class, string $name)
    {
        $reflectionProperty = new ReflectionProperty($class, $name);

        $key = $reflectionProperty->isPrivate() ? "\0" . ltrim($class, '\\') . "\0" . $name : ($reflectionProperty->isProtected() ? "\0*\0" . $name : $name);

        return new self($reflectionProperty, $key);
    }

    public function fromReflectionProperty(ReflectionProperty $reflectionProperty): self
    {
        $name = $reflectionProperty->getName();
        $key  = $reflectionProperty->isPrivate() ? "\0" . ltrim($reflectionProperty->getDeclaringClass()->getName(), '\\') . "\0" . $name : ($reflectionProperty->isProtected() ? "\0*\0" . $name : $name);

        return new self($reflectionProperty, $key);
    }

    private function __construct(private ReflectionProperty $reflectionProperty, private string $key)
    {
    }

    public function setValue(object $object, mixed $value): void
    {

    }

    public function getValue(object $object): mixed
    {
        return ((array) $object)[$this->key] ?? null;
    }
}
