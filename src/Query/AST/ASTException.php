<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\QueryException;

use function get_debug_type;

/**
 * Base exception class for AST exceptions.
 */
class ASTException extends QueryException
{
    public static function noDispatchForNode(Node $node): self
    {
        return new self('Double-dispatch for node ' . get_debug_type($node) . ' is not supported.');
    }
}
