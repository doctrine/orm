<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use ArrayIterator;
use Countable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Internal\SQLResultCasing;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
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
use function count;
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
    /**
     * @var bool The auto-detection of queries style was added a lot later to this class, and this
     *  class historically was by default using the more complex queries style, which means that
     *  the simple queries style is potentially very under-tested in production systems. The purpose
     *  of this variable is to not introduce breaking changes until an impression is developed that
     *  the simple queries style has been battle-tested enough.
     */
    private bool $queryStyleAutoDetectionEnabled = false;
    /** @var bool|null Null means "undetermined". */
    private bool|null $queryHasHavingClause = null;

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
     * Create an instance of Paginator with auto-detection of whether the provided
     * query is suitable for simple (and fast) pagination queries, or whether a complex
     * set of pagination queries has to be used.
     */
    public static function newWithAutoDetection(QueryBuilder $queryBuilder): self
    {
        /** @var array<string, Join[]> $joinsPerRootAlias */
        $joinsPerRootAlias = $queryBuilder->getDQLPart('join');

        // For now, only check whether there are any sql joins present in the query. Note,
        // however, that it is doable to detect a presence of only *ToOne joins via the access to
        // joined entity classes' metadata (see: QueryBuilder::getEntityManager()->getClassMetadata(className)).
        $sqlJoinsPresent = count($joinsPerRootAlias) > 0;

        $paginator = new self($queryBuilder, $sqlJoinsPresent);

        $paginator->queryStyleAutoDetectionEnabled = true;
        $paginator->queryHasHavingClause           = $queryBuilder->getDQLPart('having') !== null;

        return $paginator;
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
     * @psalm-return Traversable<array-key, T>
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
    private function useOutputWalker(Query $query, bool $forCountQuery = false): bool
    {
        if ($this->useOutputWalkers !== null) {
            return $this->useOutputWalkers;
        }

        // When a custom output walker already present, then do not use the Paginator's.
        if ($query->getHint(Query::HINT_CUSTOM_OUTPUT_WALKER) !== false) {
            return false;
        }

        // When not joining onto *ToMany relations, then do not use the more complex CountOutputWalker.
        return ! (
            $forCountQuery
            && $this->queryStyleAutoDetectionEnabled
            && ! $this->fetchJoinCollection
            // CountWalker doesn't support the "having" clause, while CountOutputWalker does.
            && $this->queryHasHavingClause === false
        );
    }

    /**
     * Appends a custom tree walker to the tree walkers hint.
     *
     * @psalm-param class-string $walkerClass
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
        $countQuery = $this->cloneQuery($this->query);

        if (! $countQuery->hasHint(CountWalker::HINT_DISTINCT)) {
            $hintDistinctDefaultTrue = true;

            // When not joining onto *ToMany relations, then use a simpler COUNT query in the CountWalker.
            if (
                $this->queryStyleAutoDetectionEnabled
                && ! $this->fetchJoinCollection
            ) {
                $hintDistinctDefaultTrue = false;
            }

            $countQuery->setHint(CountWalker::HINT_DISTINCT, $hintDistinctDefaultTrue);
        }

        if ($this->useOutputWalker($countQuery, forCountQuery: true)) {
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
