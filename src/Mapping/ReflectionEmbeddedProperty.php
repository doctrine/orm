<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use ReflectionClass;
use ReflectionProperty;

/**
 * Acts as a proxy to a nested Property structure, making it look like
 * just a single scalar property.
 *
 * This way value objects "just work" without UnitOfWork, Persisters or Hydrators
 * needing any changes.
 */
final class ReflectionEmbeddedProperty extends ReflectionProperty
{
    /**
     * @param ReflectionProperty $parentProperty reflection property of the class where the embedded object has to be put
     * @param ReflectionProperty $childProperty  reflection property of the embedded object
     * @param class-string       $embeddedClass
     */
    public function __construct(
        private readonly ReflectionProperty $parentProperty,
        private readonly ReflectionProperty $childProperty,
        private readonly string $embeddedClass,
    ) {
        parent::__construct($childProperty->getDeclaringClass()->name, $childProperty->getName());
    }

    public function getValue(object|null $object = null): mixed
    {
        $embeddedObject = $this->parentProperty->getValue($object);

        if ($embeddedObject === null) {
            return null;
        }

        return $this->childProperty->getValue($embeddedObject);
    }

    public function setValue(mixed $object, mixed $value = null): void
    {
        $embeddedObject = $this->parentProperty->getValue($object);

        if ($embeddedObject === null) {
            $embeddedObject = (new ReflectionClass($this->embeddedClass))->newInstanceWithoutConstructor();

            $this->parentProperty->setValue($object, $embeddedObject);
        }

        $this->childProperty->setValue($embeddedObject, $value);
    }
}
