<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\AST\PathExpression;

/** @internal */
final class CursorOrderByItem
{
    /** @param ClassMetadata<object>|null $metadata */
    public function __construct(
        public readonly PathExpression|string $expression,
        public readonly OrderDirection $direction,
        public readonly string $paramKey,
        public readonly ClassMetadata|null $metadata = null,
    ) {
    }
}
