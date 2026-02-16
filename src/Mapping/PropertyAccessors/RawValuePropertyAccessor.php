<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\PropertyAccessors;

use Doctrine\ORM\Proxy\InternalProxy;
use LogicException;
use ReflectionProperty;

use const PHP_VERSION_ID;

/**
 * This is a PHP 8.4 and up only class and replaces ObjectCastPropertyAccessor.
 *
 * It works based on the raw values of a property, which for a case of property hooks
 * is the backed value. If we kept using setValue/getValue, this would go through the hooks,
 * which potentially change the data.
 */
class RawValuePropertyAccessor implements PropertyAccessor
{
    public static function fromReflectionProperty(ReflectionProperty $reflectionProperty): self
    {
        return new self($reflectionProperty);
    }

    private function __construct(private ReflectionProperty $reflectionProperty)
    {
        if (PHP_VERSION_ID < 80400) {
            throw new LogicException('This class requires PHP 8.4 or higher.');
        }
    }

    public function setValue(object $object, mixed $value): void
    {
        if (! ($object instanceof InternalProxy && ! $object->__isInitialized())) {
            $this->reflectionProperty->setRawValueWithoutLazyInitialization($object, $value);

            return;
        }

        $object->__setInitialized(true);

        $this->reflectionProperty->setRawValue($object, $value);

        $object->__setInitialized(false);
    }

    public function getValue(object $object): mixed
    {
        if ($this->reflectionProperty->isInitialized($object)) {
            return $this->reflectionProperty->getRawValue($object);
        }

        return null;
    }

    public function getUnderlyingReflector(): ReflectionProperty
    {
        return $this->reflectionProperty;
    }
}
