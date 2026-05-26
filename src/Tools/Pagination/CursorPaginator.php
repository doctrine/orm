<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Internal\SQLResultCasing;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Exception\InvalidCursor;
use Doctrine\ORM\Utility\PersisterHelper;
use IteratorAggregate;
use LogicException;
use Traversable;

use function array_map;
use function array_reverse;
use function array_slice;
use function array_sum;
use function count;
use function is_string;

/**
 * The cursor paginator handles cursor-based pagination for DQL queries.
 *
 * @template T
 * @implements IteratorAggregate<mixed, T>
 */
final class CursorPaginator implements IteratorAggregate
{
    use SQLResultCasing;
    use PaginatorQuery;

    private readonly Query $query;
    /** @var Collection<int, T>|null */
    private Collection|null $items = null;

    /** @var list<CursorOrderByItem>|null */
    private array|null $orderByItems = null;

    private bool $hasMore       = false;
    private Cursor|null $cursor = null;

    /** @param bool $queryProducesDuplicates Whether the query joins a collection (true by default). */
    public function __construct(
        Query|QueryBuilder $query,
        private readonly bool $queryProducesDuplicates = true,
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
    public function getQueryProducesDuplicates(): bool
    {
        return $this->queryProducesDuplicates;
    }

    /**
     * Paginates the query with the given limit and optional cursor.
     *
     * @param mixed $cursor The cursor instance, encoded cursor string, null or empty string for the first page.
     *                      Any other type (e.g. array) throws InvalidCursor.
     * @param int   $limit  The maximum number of results to return.
     *
     * @return $this
     *
     * @throws InvalidCursor If $cursor is not a Cursor instance, a string, or null.
     */
    public function paginate(mixed $cursor, int $limit): self
    {
        if ($cursor instanceof Cursor) {
            $this->cursor = $cursor;
        } elseif ($cursor === null || (is_string($cursor) && $cursor === '')) {
            $this->cursor = null;
        } elseif (is_string($cursor)) {
            $this->cursor = Cursor::fromEncodedString($cursor);
        } else {
            throw new InvalidCursor('(non-string value)');
        }

        $shouldReverse = $this->cursor?->isPrevious() ?? false;

        $subQuery = $this->cloneQuery($this->query);
        $subQuery->expireQueryCache();

        $this->appendTreeWalker($subQuery, CursorWalker::class);
        $subQuery->setHint(CursorWalker::HINT_QUERY_PRODUCES_DUPLICATES, $this->queryProducesDuplicates);

        if ($this->queryProducesDuplicates) {
            if ($this->useOutputWalker($subQuery)) {
                $subQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);
            } else {
                $this->appendTreeWalker($subQuery, LimitSubqueryWalker::class);
            }
        }

        $subQuery->setHint(CursorWalker::HINT_CURSOR_REVERSE, $shouldReverse);
        $subQuery->setHint(CursorWalker::HINT_CURSOR_PARAMETERS, $this->cursor?->getParameters() ?? []);
        $subQuery->setFirstResult(0)->setMaxResults($limit + 1);

        if ($this->queryProducesDuplicates) {
            $foundIdRows        = $subQuery->getScalarResult();
            $this->orderByItems = $subQuery->getHint(CursorWalker::HINT_CURSOR_ORDER_BY_ITEMS) ?: [];
            $this->hasMore      = count($foundIdRows) > $limit;
            $foundIdRows        = array_slice($foundIdRows, 0, $limit);

            // don't do this for an empty id array
            if ($foundIdRows === []) {
                $this->items = new ArrayCollection([]);

                return $this;
            }

            $whereInQuery = $this->cloneQuery($this->query);
            $ids          = array_map('current', $foundIdRows);

            $this->appendTreeWalker($whereInQuery, WhereInWalker::class);
            $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_HAS_IDS, true);
            $whereInQuery->setFirstResult(0)->setMaxResults(null);
            $whereInQuery->setCacheable($this->query->isCacheable());

            $databaseIds = $this->convertWhereInIdentifiersToDatabaseValues($ids);
            $whereInQuery->setParameter(WhereInWalker::PAGINATOR_ID_ALIAS, $databaseIds);

            $this->items = new ArrayCollection($whereInQuery->getResult());
        } else {
            $result        = $subQuery->getResult();
            $this->hasMore = count($result) > $limit;
            $result        = array_slice($result, 0, $limit);

            $this->orderByItems = $subQuery->getHint(CursorWalker::HINT_CURSOR_ORDER_BY_ITEMS) ?: [];

            if ($this->cursor !== null && $this->cursor->isPrevious()) {
                $result = array_reverse($result);
            }

            $this->items = new ArrayCollection($result);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return Traversable<mixed, T>
     */
    public function getIterator(): Traversable
    {
        return $this->items->getIterator();
    }

    public function countPageItems(): int
    {
        if ($this->items === null) {
            throw new LogicException('The paginator has not been initialized. Call paginate() first.');
        }

        return $this->items->count();
    }

    public function getTotalCount(): int
    {
        if ($this->count === null) {
            $this->count = (int) array_sum(array_map('current', $this->getCountQuery()->getScalarResult()));
        }

        return $this->count;
    }

    /**
     * Returns whether there is a previous page.
     */
    public function hasPreviousPage(): bool
    {
        return $this->cursor !== null && ($this->cursor->isNext() || $this->hasMore);
    }

    /**
     * Returns whether there is a next page.
     */
    public function hasNextPage(): bool
    {
        return $this->hasMore || ($this->cursor !== null && $this->cursor->isPrevious());
    }

    /**
     * Returns the cursor object for the next page.
     *
     * @throws LogicException If there is no next page. Check {@see hasNextPage()} first.
     */
    public function getNextCursor(): Cursor
    {
        if ($this->items->isEmpty() || ! $this->hasNextPage()) {
            throw new LogicException('There is no next page. Call hasNextPage() before getNextCursor().');
        }

        return $this->getCursorForItem($this->items->last());
    }

    /**
     * Returns the cursor object for the previous page.
     *
     * @throws LogicException If there is no previous page. Check {@see hasPreviousPage()} first.
     */
    public function getPreviousCursor(): Cursor
    {
        if ($this->items->isEmpty() || ! $this->hasPreviousPage()) {
            throw new LogicException('There is no previous page. Call hasPreviousPage() before getPreviousCursor().');
        }

        return $this->getCursorForItem($this->items->first(), false);
    }

    /**
     * Returns the encoded cursor string for the next page.
     *
     * @throws LogicException If there is no next page. Check {@see hasNextPage()} first.
     */
    public function getNextCursorAsString(): string
    {
        return $this->getNextCursor()->encodeToString();
    }

    /**
     * Returns the encoded cursor string for the previous page.
     *
     * @throws LogicException If there is no previous page. Check {@see hasPreviousPage()} first.
     */
    public function getPreviousCursorAsString(): string
    {
        return $this->getPreviousCursor()->encodeToString();
    }

    /**
     * Returns the cursor for a given item.
     *
     * @param mixed $item   The item to create a cursor for.
     * @param bool  $isNext Whether the cursor is for the next page.
     *
     * @throws Exception
     * @throws QueryException
     */
    public function getCursorForItem(mixed $item, bool $isNext = true): Cursor
    {
        return new Cursor($this->getParametersForItem($item), $isNext);
    }

    /**
     * Returns items wrapped with their associated cursors.
     *
     * @return array<int, CursorItem<T>>
     *
     * @throws Exception
     * @throws QueryException
     */
    public function getItems(): array
    {
        return array_map(
            fn (mixed $item) => new CursorItem($item, $this->getCursorForItem($item)),
            $this->items->toArray(),
        );
    }

    /**
     * Returns the raw entity values.
     *
     * @return list<T>
     */
    public function getValues(): array
    {
        return $this->items->getValues();
    }

    /**
     * Returns whether pagination is needed.
     */
    public function hasToPaginate(): bool
    {
        return $this->hasPreviousPage() || $this->hasNextPage();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Query\QueryException
     * @throws Exception
     */
    private function getParametersForItem(mixed $item): array
    {
        $em         = $this->query->getEntityManager();
        $connection = $em->getConnection();
        $metadata   = $em->getMetadataFactory()->hasMetadataFor($item::class)
            ? $em->getClassMetadata($item::class)
            : null;

        $result = [];

        foreach ($this->orderByItems as $orderByItem) {
            if (! $orderByItem->expression instanceof PathExpression) {
                continue;
            }

            $fieldName     = $orderByItem->expression->field;
            $orderMetadata = $orderByItem->metadata ?? $metadata;
            $value         = $metadata?->getFieldValue($item, $fieldName) ?? $item->$fieldName;

            if ($value !== null && $orderMetadata !== null && isset($orderMetadata->associationMappings[$fieldName])) {
                $targetMetadata = $em->getClassMetadata($orderMetadata->associationMappings[$fieldName]->targetEntity);
                $value          = $targetMetadata->getIdentifierValues($value)[$targetMetadata->getSingleIdentifierFieldName()] ?? null;
            }

            $type = PersisterHelper::getTypeOfField($fieldName, $orderMetadata, $em)[0];

            $result[$orderByItem->paramKey] = $connection->convertToDatabaseValue($value, $type);
        }

        return $result;
    }
}
