<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use ArrayIterator;
use Countable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Internal\SQLResultCasing;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use IteratorAggregate;
use Traversable;

use function array_key_exists;
use function array_map;
use function array_sum;
use function assert;
use function is_string;

/**
 * The paginator can handle various complex scenarios with DQL.
 *
 * @template-covariant T
 * @implements IteratorAggregate<array-key, T>
 */
class Paginator implements Countable, IteratorAggregate
{
    use SQLResultCasing;

    public const HINT_ENABLE_DISTINCT = 'paginator.distinct.enable';

    private readonly Query $query;
    private bool|null $useOutputWalkers = null;
    private int|null $count             = null;

    /** @param bool $fetchJoinCollection Whether the query joins a collection (true by default). */
    public function __construct(
        Query|QueryBuilder $query,
        private readonly bool $fetchJoinCollection = true,
    ) {
        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }

        $this->query = $query;
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

        if ($this->fetchJoinCollection && $length !== null) {
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

    private function cloneQuery(Query $query): Query
    {
        $cloneQuery = clone $query;

        $cloneQuery->setParameters(clone $query->getParameters());
        $cloneQuery->setCacheable(false);

        foreach ($query->getHints() as $name => $value) {
            $cloneQuery->setHint($name, $value);
        }

        return $cloneQuery;
    }

    /**
     * Determines whether to use an output walker for the query.
     */
    private function useOutputWalker(Query $query): bool
    {
        if ($this->useOutputWalkers === null) {
            return (bool) $query->getHint(Query::HINT_CUSTOM_OUTPUT_WALKER) === false;
        }

        return $this->useOutputWalkers;
    }

    /**
     * Appends a custom tree walker to the tree walkers hint.
     *
     * @param class-string $walkerClass
     */
    private function appendTreeWalker(Query $query, string $walkerClass): void
    {
        $hints = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS);

        if ($hints === false) {
            $hints = [];
        }

        $hints[] = $walkerClass;
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $hints);
    }

    /**
     * Returns Query prepared to count.
     */
    private function getCountQuery(): Query
    {
        /*
            As opposed to using self::cloneQuery, the following code does not transfer
            a potentially existing result set mapping (either set directly by the user,
            or taken from the parser result from a previous invocation of Query::parse())
            to the new query object. This is fine, since we are going to completely change the
            select clause, so a previously existing result set mapping (RSM) is probably wrong anyway.
            In the case of using output walkers, we are even creating a new RSM down below.
            In the case of using a tree walker, we want to have a new RSM created by the parser.
        */
        $countQuery = new Query($this->query->getEntityManager());
        $countQuery->setDQL($this->query->getDQL());
        $countQuery->setParameters(clone $this->query->getParameters());
        $countQuery->setCacheable(false);
        foreach ($this->query->getHints() as $name => $value) {
            $countQuery->setHint($name, $value);
        }

        if (! $countQuery->hasHint(CountWalker::HINT_DISTINCT)) {
            $countQuery->setHint(CountWalker::HINT_DISTINCT, true);
        }

        if ($this->useOutputWalker($countQuery)) {
            $platform = $countQuery->getEntityManager()->getConnection()->getDatabasePlatform(); // law of demeter win

            $rsm = new ResultSetMapping();
            $rsm->addScalarResult($this->getSQLResultCasing($platform, 'dctrn_count'), 'count');

            $countQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, CountOutputWalker::class);
            $countQuery->setResultSetMapping($rsm);
        } else {
            $this->appendTreeWalker($countQuery, CountWalker::class);
            $this->unbindUnusedQueryParams($countQuery);
        }

        $countQuery->setFirstResult(0)->setMaxResults(null);

        return $countQuery;
    }

    private function unbindUnusedQueryParams(Query $query): void
    {
        $parser            = new Parser($query);
        $parameterMappings = $parser->parse()->getParameterMappings();
        /** @var Collection|Parameter[] $parameters */
        $parameters = $query->getParameters();

        foreach ($parameters as $key => $parameter) {
            $parameterName = $parameter->getName();

            if (! (isset($parameterMappings[$parameterName]) || array_key_exists($parameterName, $parameterMappings))) {
                unset($parameters[$key]);
            }
        }

        $query->setParameters($parameters);
    }

    /**
     * @param mixed[] $identifiers
     *
     * @return mixed[]
     */
    private function convertWhereInIdentifiersToDatabaseValues(array $identifiers): array
    {
        $query = $this->cloneQuery($this->query);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, RootTypeWalker::class);

        $connection = $this->query->getEntityManager()->getConnection();
        $type       = $query->getSQL();
        assert(is_string($type));

        return array_map(static fn ($id): mixed => $connection->convertToDatabaseValue($id, $type), $identifiers);
    }
}
