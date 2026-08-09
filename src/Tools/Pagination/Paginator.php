<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use ArrayIterator;
use Countable;
use Doctrine\ORM\Internal\SQLResultCasing;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use IteratorAggregate;
use Traversable;

use function array_map;
use function array_sum;

/**
 * The paginator can handle various complex scenarios with DQL.
 *
 * @deprecated This class is no longer needed by the ORM and will be removed in 4.0.
 *             Use {@see OffsetPaginator} instead.
 *
 * @template-covariant T
 * @implements IteratorAggregate<array-key, T>
 */
class Paginator implements Countable, IteratorAggregate
{
    use SQLResultCasing;
    use PaginatorQuery;

    public const HINT_ENABLE_DISTINCT = 'paginator.distinct.enable';

    private readonly Query $query;
    private int|null $count = null;

    /** @param bool $fetchJoinCollection Whether the query joins a collection (true by default). */
    public function __construct(
        Query|QueryBuilder $query,
        private readonly bool $fetchJoinCollection = true,
    ) {
        $this->query = $this->resolveQuery($query);
    }

    /**
     * Returns the query.
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * Returns whether the query joins a collection.
     *
     * @return bool Whether the query joins a collection.
     */
    public function getFetchJoinCollection(): bool
    {
        return $this->fetchJoinCollection;
    }

    /**
     * Returns whether the paginator will use an output walker.
     */
    public function getUseOutputWalkers(): bool|null
    {
        return $this->useOutputWalkers;
    }

    /**
     * Sets whether the paginator will use an output walker.
     *
     * @return $this
     */
    public function setUseOutputWalkers(bool|null $useOutputWalkers): static
    {
        $this->useOutputWalkers = $useOutputWalkers;

        return $this;
    }

    public function count(): int
    {
        if ($this->count === null) {
            try {
                $this->count = (int) array_sum(array_map('current', $this->getCountQuery($this->query)->getScalarResult()));
            } catch (NoResultException) {
                $this->count = 0;
            }
        }

        return $this->count;
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-return Traversable<array-key, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getResultForOffset(
            $this->query,
            $this->query->getFirstResult(),
            $this->query->getMaxResults(),
            $this->fetchJoinCollection,
        ));
    }
}
