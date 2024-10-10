<?php

namespace Doctrine\ORM\Mapping\PropertyAccessors;

use Doctrine\Instantiator\Instantiator;

class EmbeddablePropertyAccessor implements PropertyAccessor
{
    private static Instantiator|null $instantiator = null;

    public function __construct(
        private PropertyAccessor $parent,
        private PropertyAccessor $child,
        private string $embeddedClass,
    ) {
    }

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

    public function getValue(object $object): mixed
    {
        $embeddedObject = $this->parent->getValue($object);

        if ($embeddedObject === null) {
            return null;
        }

        return $this->child->getValue($embeddedObject);
    }
}
