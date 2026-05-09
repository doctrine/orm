<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\TreeWalker;

use function array_key_exists;
use function array_map;
use function assert;
use function is_string;

/**
 * Provides the implementation shared by {@see Paginator} and {@see CursorPaginator}.
 *
 * @internal
 */
trait PaginatorQuery
{
    private int|null $count             = null;
    private bool|null $useOutputWalkers = null;

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

    /**
     * Appends a custom tree walker to the tree walkers hint.
     *
     * @param class-string<TreeWalker> $walkerClass
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

    private function unbindUnusedQueryParams(Query $query): void
    {
        $parser            = new Parser($query);
        $parameterMappings = $parser->parse()->getParameterMappings();
        /** @var ArrayCollection<int, Parameter> $parameters */
        $parameters = $query->getParameters();

        foreach ($parameters as $key => $parameter) {
            $parameterName = $parameter->getName();

            if (! (isset($parameterMappings[$parameterName]) || array_key_exists($parameterName, $parameterMappings))) {
                unset($parameters[$key]);
            }
        }

        $query->setParameters($parameters);
    }
}
