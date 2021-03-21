<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Reflection;

/**
 * A test asset used to check that embeddables support properties defined in abstract classes
 */
abstract class AbstractEmbeddable
{
    /** @var string */
    private $propertyInAbstractClass;
}
