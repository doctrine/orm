<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use InvalidArgumentException;

use function intdiv;
use function max;

/**
 * Represents an offset-based pagination position: a window over the result set.
 *
 * Counterpart to {@see Cursor}, but deliberately not its mirror image. A cursor
 * is a pure position, so the page size belongs to the paginator; a window is a
 * position *and* a size, because offset pagination has to know how far to skip,
 * which is a function of the page size. It carries no opaque state and needs no
 * encoding: navigation between pages is plain arithmetic.
 *
 * The window is passed explicitly to {@see OffsetPaginator::paginate()}, instead
 * of being derived implicitly from {@see \Doctrine\ORM\Query::setFirstResult()}
 * and {@see \Doctrine\ORM\Query::setMaxResults()}.
 */
final class Window
{
    public function __construct(
        private readonly int $firstResult,
        private readonly int $maxResults,
    ) {
        if ($firstResult < 0) {
            throw new InvalidArgumentException('firstResult must be greater than or equal to 0.');
        }

        if ($maxResults < 1) {
            throw new InvalidArgumentException('maxResults must be greater than or equal to 1.');
        }
    }

    /**
     * Builds a window from a 1-based page number and a page size.
     */
    public static function fromPageNumberAndSize(int $pageNumber, int $pageSize): self
    {
        if ($pageNumber < 1) {
            throw new InvalidArgumentException('pageNumber must be greater than or equal to 1.');
        }

        return new self(($pageNumber - 1) * $pageSize, $pageSize);
    }

    public function getFirstResult(): int
    {
        return $this->firstResult;
    }

    public function getMaxResults(): int
    {
        return $this->maxResults;
    }

    /**
     * Returns the 1-based page number this window points at.
     */
    public function getPageNumber(): int
    {
        return intdiv($this->firstResult, $this->maxResults) + 1;
    }

    /**
     * Returns the window for the next page, keeping the same page size.
     */
    public function next(): self
    {
        return new self($this->firstResult + $this->maxResults, $this->maxResults);
    }

    /**
     * Returns the window for the previous page, keeping the same page size and
     * clamping the first result at 0.
     */
    public function previous(): self
    {
        return new self(max(0, $this->firstResult - $this->maxResults), $this->maxResults);
    }
}
