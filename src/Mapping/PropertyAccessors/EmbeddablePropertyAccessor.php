<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\PropertyAccessors;

use Doctrine\Instantiator\Instantiator;
use Override;
use ReflectionProperty;

/** @internal */
class EmbeddablePropertyAccessor implements PropertyAccessor
{
    private static Instantiator|null $instantiator = null;

    public function __construct(
        private PropertyAccessor $parent,
        private PropertyAccessor $child,
        /** @var class-string */
        private string $embeddedClass,
    ) {
    }

    #[Override]
    public function setValue(object $object, mixed $value): void
    {
        $embeddedObject = $this->parent->getValue($object);

        if ($embeddedObject === null) {
            self::$instantiator ??= new Instantiator();

            $embeddedObject = self::$instantiator->instantiate($this->embeddedClass);

            $this->parent->setValue($object, $embeddedObject);
        }

        $this->child->setValue($embeddedObject, $value);
    }

    #[Override]
    public function getValue(object $object): mixed
    {
        $embeddedObject = $this->parent->getValue($object);

        if ($embeddedObject === null) {
            return null;
        }

        return $this->child->getValue($embeddedObject);
    }

    #[Override]
    public function getUnderlyingReflector(): ReflectionProperty
    {
        return $this->child->getUnderlyingReflector();
    }
}
