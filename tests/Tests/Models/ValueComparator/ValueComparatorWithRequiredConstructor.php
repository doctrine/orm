<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueComparator;

use Doctrine\ORM\Mapping\ValueComparator;

/**
 * A comparator that cannot be instantiated without an argument, so it is
 * invalid in an XML mapping where only a class name can be provided.
 */
class ValueComparatorWithRequiredConstructor implements ValueComparator
{
    public function __construct(private readonly string $format)
    {
    }

    public function equals(mixed $first, mixed $second): bool
    {
        return $first === $second;
    }

    public function isMutable(): bool
    {
        return false;
    }
}
