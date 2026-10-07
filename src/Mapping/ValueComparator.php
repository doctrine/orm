<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * Compares two values of a mapped field to decide whether the field changed.
 *
 * This is useful when a field holds an object (a date, a value object, ...)
 * that the ORM would otherwise compare by reference.
 *
 * Implementations are part of the metadata, so they are serialized when the
 * metadata cache is used. They must therefore be serializable: no closure and
 * no non-serializable resource as property.
 */
interface ValueComparator
{
    /**
     * Tells whether the two values must be considered equal, that is whether
     * the field must be considered unchanged.
     */
    public function equals(mixed $first, mixed $second): bool;

    /**
     * Tells whether the compared value may be mutated in place.
     *
     * When true, the original value is cloned when the original data snapshot is
     * taken, so that in-place mutations are detected by {@see self::equals()}.
     */
    public function isMutable(): bool;
}
