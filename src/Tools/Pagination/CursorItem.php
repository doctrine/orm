<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

/**
 * Represents a paginated item with its associated cursor.
 *
 * @template-covariant T
 */
final class CursorItem
{
    /** @param T $value */
    public function __construct(
        private readonly mixed $value,
        private readonly Cursor $cursor,
    ) {
    }

    /** @return T */
    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getCursor(): Cursor
    {
        return $this->cursor;
    }
}
