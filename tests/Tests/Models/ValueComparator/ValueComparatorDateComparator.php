<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueComparator;

use DateTime;
use Doctrine\ORM\Mapping\ValueComparator;

/**
 * Compares dates by day, ignoring the time of day.
 */
class ValueComparatorDateComparator implements ValueComparator
{
    public function equals(mixed $first, mixed $second): bool
    {
        if (! $first instanceof DateTime || ! $second instanceof DateTime) {
            return $first === $second;
        }

        return $first->format('Y-m-d') === $second->format('Y-m-d');
    }

    public function isMutable(): bool
    {
        return true;
    }
}
