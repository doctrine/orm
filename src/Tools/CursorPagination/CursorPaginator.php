<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\CursorPagination;

use Countable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Utility\PersisterHelper;
use IteratorAggregate;
use LogicException;
use Override;
use Traversable;

use function array_map;
use function array_reverse;

/**
 * The cursor paginator handles cursor-based pagination for DQL queries.
 *
 * @template T
 * @implements IteratorAggregate<mixed, T>
 */
final class CursorPaginator implements IteratorAggregate, Countable
{
    private readonly Query $query;
    /** @var Collection<int, T>|null */
    private Collection|null $items = null;

    /** @var list<CursorOrderByItem>|null */
    private array|null $orderByItems = null;

    private bool $hasMore       = false;
    private Cursor|null $cursor = null;

    public function __construct(Query|QueryBuilder $query)
    {
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
     * Paginates the query with the given limit and optional cursor.
     *
     * @param string|null $cursor The encoded cursor string, null or empty string for the first page.
     * @param int         $limit  The maximum number of results to return.
     *
     * @return $this
     */
    public function paginate(string|null $cursor, int $limit): self
    {
        $this->cursor  = ! empty($cursor) ? Cursor::fromEncodedString($cursor) : null;
        $shouldReverse = $this->cursor?->isPrevious() ?? false;

        $query = $this->cloneQuery($this->query);

        $this->appendTreeWalker($query);

        $query->setHint(CursorWalker::HINT_CURSOR_REVERSE, $shouldReverse);
        $query->setHint(CursorWalker::HINT_CURSOR_PARAMETERS, $this->cursor?->getParameters() ?? []);

        $query->setMaxResults($limit + 1);

        $this->items   = new ArrayCollection($query->getResult());
        $this->hasMore = $this->items->count() > $limit;
        $this->items   = new ArrayCollection($this->items->slice(0, $limit));

        $this->orderByItems = $query->getHint(CursorWalker::HINT_CURSOR_ORDER_BY_ITEMS) ?: [];

        if ($this->cursor !== null && $this->cursor->isPrevious()) {
            $this->items = new ArrayCollection(array_reverse($this->items->toArray(), true));
        }

        return $this;
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
     * Appends a custom tree walker to the tree walkers hint.
     */
    private function appendTreeWalker(Query $query): void
    {
        $hints = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS);

        if ($hints === false) {
            $hints = [];
        }

        $hints[] = CursorWalker::class;
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $hints);
    }

    /**
     * {@inheritDoc}
     *
     * @return Traversable<mixed, T>
     */
    #[Override]
    public function getIterator(): Traversable
    {
        return $this->items->getIterator();
    }

    #[Override]
    public function count(): int
    {
        return $this->items->count();
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
            $type          = PersisterHelper::getTypeOfField($fieldName, $orderMetadata, $em)[0];

            $result[$orderByItem->paramKey] = $connection->convertToDatabaseValue($value, $type);
        }

        return $result;
    }
}
