<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * The built-in value comparators.
 */
enum Comparison implements ValueComparator
{
    /** Reference comparison (===). This is the default. */
    case Same;

    /** Value comparison (==) for immutable objects. */
    case Equal;

    /**
     * Value comparison (==) for mutable objects.
     *
     * The original value is cloned when the snapshot is taken, so that in-place
     * mutations are detected.
     */
    case EqualMutable;

    public function equals(mixed $first, mixed $second): bool
    {
        return match ($this) {
            self::Same => $first === $second,
            // The loose comparison is the whole point of these cases: they
            // compare objects by value instead of by reference.
            // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
            self::Equal, self::EqualMutable => $first == $second,
        };
    }

    public function isMutable(): bool
    {
        return $this === self::EqualMutable;
    }
}
