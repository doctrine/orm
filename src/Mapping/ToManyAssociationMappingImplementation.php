<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use LogicException;

use function sprintf;

/** @internal */
trait ToManyAssociationMappingImplementation
{
    /**
     * Specification of a field on target-entity that is used to index the
     * collection by. This field HAS to be either the primary key or a unique
     * column. Otherwise the collection does not contain all the entities that
     * are actually related.
     */
    public string|null $indexBy = null;

    /**
     * A map of field names (of the target entity) to sorting directions
     *
     * @var array<string, 'asc'|'desc'>
     */
    public array $orderBy = [];

    /** @return array<string, 'asc'|'desc'> */
    final public function orderBy(): array
    {
        return $this->orderBy;
    }

    /** @phpstan-assert-if-true !null $this->indexBy */
    final public function isIndexed(): bool
    {
        return $this->indexBy !== null;
    }

    final public function indexBy(): string
    {
        if (! $this->isIndexed()) {
            throw new LogicException(sprintf(
                'This mapping is not indexed. Use %s::isIndexed() to check that before calling %s.',
                self::class,
                __METHOD__,
            ));
        }

        return $this->indexBy;
    }

    /** @return list<string> */
    public function __sleep(): array
    {
        $serialized = parent::__sleep();

        if ($this->indexBy !== null) {
            $serialized[] = 'indexBy';
        }

        if ($this->orderBy !== []) {
            $serialized[] = 'orderBy';
        }

        return $serialized;
    }
}
