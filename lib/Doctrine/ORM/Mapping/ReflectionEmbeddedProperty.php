<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\Instantiator\Instantiator;
use ReflectionProperty;

/**
 * Acts as a proxy to a nested Property structure, making it look like
 * just a single scalar property.
 *
 * This way value objects "just work" without UnitOfWork, Persisters or Hydrators
 * needing any changes.
 *
 * TODO: Move this class into Common\Reflection
 */
final class ReflectionEmbeddedProperty extends ReflectionProperty
{
    private ?Instantiator $instantiator = null;

    /**
     * @param ReflectionProperty $parentProperty reflection property of the class where the embedded object has to be put
     * @param ReflectionProperty $childProperty  reflection property of the embedded object
     * @psalm-param class-string $embeddedClass
     */
    public function __construct(
        private readonly ReflectionProperty $parentProperty,
        private readonly ReflectionProperty $childProperty,
        private readonly string $embeddedClass
    ) {
        parent::__construct($childProperty->getDeclaringClass()->getName(), $childProperty->getName());
    }

    public function getValue(?object $object = null): mixed
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
            $this->instantiator ??= new Instantiator();

            $embeddedObject = $this->instantiator->instantiate($this->embeddedClass);

            $this->parentProperty->setValue($object, $embeddedObject);
        }

        $this->childProperty->setValue($embeddedObject, $value);
    }
}
