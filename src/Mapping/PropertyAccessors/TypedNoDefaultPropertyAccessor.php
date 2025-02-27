<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\PropertyAccessors;

use Closure;
use InvalidArgumentException;
use ReflectionProperty;

use function assert;
use function sprintf;

/** @internal */
class TypedNoDefaultPropertyAccessor implements PropertyAccessor
{
    private Closure|null $unsetter = null;

    public function __construct(private PropertyAccessor $parent, private ReflectionProperty $reflectionProperty)
    {
        if (! $this->reflectionProperty->hasType()) {
            throw new InvalidArgumentException(sprintf(
                '%s::$%s must have a type when used with TypedNoDefaultPropertyAccessor',
                $this->reflectionProperty->getDeclaringClass()->getName(),
                $this->reflectionProperty->getName(),
            ));
        }

        if ($this->reflectionProperty->getType()->allowsNull()) {
            throw new InvalidArgumentException(sprintf(
                '%s::$%s must not be nullable when used with TypedNoDefaultPropertyAccessor',
                $this->reflectionProperty->getDeclaringClass()->getName(),
                $this->reflectionProperty->getName(),
            ));
        }
    }

    public function setValue(object $object, mixed $value): void
    {
        if ($value === null) {
            if ($this->unsetter === null) {
                $propertyName   = $this->reflectionProperty->getName();
                $this->unsetter = function () use ($propertyName): void {
                    unset($this->$propertyName);
                };
            }

            $unsetter = $this->unsetter->bindTo($object, $this->reflectionProperty->getDeclaringClass()->getName());

            assert($unsetter instanceof Closure);

            $unsetter();

            return;
        }

        $this->parent->setValue($object, $value);
    }

    public function getValue(object $object): mixed
    {
        return $this->reflectionProperty->isInitialized($object) ? $this->parent->getValue($object) : null;
    }

    public function getUnderlyingReflector(): ReflectionProperty
    {
        return $this->reflectionProperty;
    }
}
