<?php

declare(strict_types=1);

namespace Doctrine\ORM\StrictLoading;

use function array_values;
use function count;
use function get_debug_type;
use function implode;
use function is_scalar;
use function sprintf;

/**
 * Describes a lazy load that the ORM is about to perform.
 *
 * Instances are handed to {@see StrictLoadingViolationHandler} when strict
 * loading rejects the load.
 */
final class LazyLoad
{
    /**
     * @param class-string        $className  The entity class that is being loaded, or the class
     *                                        owning the collection that is being loaded.
     * @param array<string,mixed> $identifier The identifier of the entity being loaded, if known.
     */
    private function __construct(
        public readonly LazyLoadKind $kind,
        public readonly string $className,
        public readonly string|null $fieldName = null,
        public readonly array $identifier = [],
        public readonly string|null $operation = null,
    ) {
    }

    /**
     * @param class-string        $className
     * @param array<string,mixed> $identifier
     */
    public static function entity(string $className, array $identifier): self
    {
        return new self(LazyLoadKind::Entity, $className, identifier: $identifier);
    }

    /** @param class-string $ownerClassName */
    public static function collection(string $ownerClassName, string $fieldName): self
    {
        return new self(LazyLoadKind::Collection, $ownerClassName, $fieldName);
    }

    /** @param class-string $ownerClassName */
    public static function collectionQuery(string $ownerClassName, string $fieldName, string $operation): self
    {
        return new self(LazyLoadKind::CollectionQuery, $ownerClassName, $fieldName, operation: $operation);
    }

    /**
     * A key identifying the *shape* of this lazy load, ignoring the concrete
     * identifier values. Two lazy loads sharing a signature within the same
     * strict loading scope form an N+1 query.
     */
    public function signature(): string
    {
        return $this->kind->value . ' ' . $this->className
            . ($this->fieldName === null ? '' : '#' . $this->fieldName)
            . ($this->operation === null ? '' : '::' . $this->operation . '()');
    }

    public function describe(): string
    {
        return match ($this->kind) {
            LazyLoadKind::Entity => sprintf(
                'entity %s%s',
                $this->className,
                $this->identifier === [] ? '' : '(' . $this->identifierAsString() . ')',
            ),
            LazyLoadKind::Collection => sprintf('collection %s#%s', $this->className, $this->fieldName),
            LazyLoadKind::CollectionQuery => sprintf(
                '%s() on collection %s#%s',
                (string) $this->operation,
                $this->className,
                (string) $this->fieldName,
            ),
        };
    }

    private function identifierAsString(): string
    {
        if (count($this->identifier) === 1) {
            return self::stringify(array_values($this->identifier)[0]);
        }

        $parts = [];

        foreach ($this->identifier as $field => $value) {
            $parts[] = $field . ': ' . self::stringify($value);
        }

        return implode(', ', $parts);
    }

    private static function stringify(mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            $value === true => 'true',
            $value === false => 'false',
            is_scalar($value) => (string) $value,
            default => get_debug_type($value),
        };
    }
}
