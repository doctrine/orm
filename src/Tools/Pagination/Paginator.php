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
 * @template-covariant T
 * @implements IteratorAggregate<array-key, T>
 */
class Paginator implements Countable, IteratorAggregate
{
    use SQLResultCasing;
    /** @use PaginatorQuery<T> */
    use PaginatorQuery;

    public const HINT_ENABLE_DISTINCT = 'paginator.distinct.enable';

    private readonly Query $query;

    /**
     * @param bool $queryProducesDuplicates Whether the query could produce partially duplicated records. One case
     *  when it does is when it joins a collection.
     */
    public function __construct(
        Query|QueryBuilder $query,
        private readonly bool $queryProducesDuplicates = true,
    ) {
        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }

        $this->query = $query;
    }

    /** @return self<T> */
    protected static function doCreateNewWithAutoDetection(Query $query, bool $queryProducesDuplicates): self
    {
        return new self($query, $queryProducesDuplicates);
    }

    /**
     * Returns the query.
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * @deprecated Use ::getQueryProducesDuplicates() instead.
     *
     * Returns whether the query joins a collection.
     */
    public function getFetchJoinCollection(): bool
    {
        return $this->queryProducesDuplicates;
    }

    /**
     * Returns whether the query could produce partially duplicated records.
     */
    public function getQueryProducesDuplicates(): bool
    {
        return $this->queryProducesDuplicates;
    }

    public function count(): int
    {
        if ($this->count === null) {
            try {
                $this->count = (int) array_sum(array_map('current', $this->getCountQuery()->getScalarResult()));
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
        $offset = $this->query->getFirstResult();
        $length = $this->query->getMaxResults();

        if ($this->queryProducesDuplicates && $length !== null) {
            $subQuery = $this->cloneQuery($this->query);

            if ($this->useOutputWalker($subQuery)) {
                $subQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);
            } else {
                $this->appendTreeWalker($subQuery, LimitSubqueryWalker::class);
                $this->unbindUnusedQueryParams($subQuery);
            }

            $subQuery->setFirstResult($offset)->setMaxResults($length);

            $foundIdRows = $subQuery->getScalarResult();

            // don't do this for an empty id array
            if ($foundIdRows === []) {
                return new ArrayIterator([]);
            }

            $whereInQuery = $this->cloneQuery($this->query);
            $ids          = array_map('current', $foundIdRows);

            $this->appendTreeWalker($whereInQuery, WhereInWalker::class);
            $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_HAS_IDS, true);
            $whereInQuery->setFirstResult(0)->setMaxResults(null);
            $whereInQuery->setCacheable($this->query->isCacheable());

            $databaseIds = $this->convertWhereInIdentifiersToDatabaseValues($ids);
            $whereInQuery->setParameter(WhereInWalker::PAGINATOR_ID_ALIAS, $databaseIds);

            $result = $whereInQuery->getResult($this->query->getHydrationMode());
        } else {
            $result = $this->cloneQuery($this->query)
                ->setMaxResults($length)
                ->setFirstResult($offset)
                ->setCacheable($this->query->isCacheable())
                ->getResult($this->query->getHydrationMode());
        }

        return new ArrayIterator($result);
    }
}
