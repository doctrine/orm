<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\UnitOfWork;

use function hash;
use function strlen;

/**
 * Snapshot of the database representation of an object value.
 *
 * The snapshot is taken when the entity is hydrated. It is used to detect
 * whether an object value was replaced with an equal value, without keeping a
 * deep copy of the object.
 *
 * Short database values are stored as is. Longer values are hashed to save
 * memory.
 *
 * @internal
 */
final class ObjectValueSnapshot
{
    /**
     * Database values longer than this many bytes are hashed.
     */
    public const HASH_THRESHOLD = 256;

    private function __construct(
        public readonly object $object,
        private readonly string $comparisonValue,
        private readonly bool $isHash,
    ) {
    }

    public static function fromDatabaseValue(object $object, string $databaseValue): self
    {
        if (strlen($databaseValue) > self::HASH_THRESHOLD) {
            return new self($object, hash('sha256', $databaseValue, true), true);
        }

        return new self($object, $databaseValue, false);
    }

    public function matchesDatabaseValue(object $actualValue, string $databaseValue): bool
    {
        if ($this->object === $actualValue) {
            return true;
        }

        if ($this->isHash) {
            return $this->comparisonValue === hash('sha256', $databaseValue, true);
        }

        return $this->comparisonValue === $databaseValue;
    }
}
