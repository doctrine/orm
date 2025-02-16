<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Reflection;

/**
 * A test asset used to check that embeddables support properties defined in abstract classes
 */
class ConcreteEmbeddable extends AbstractEmbeddable
{
    private string $propertyInConcreteClass;
}
