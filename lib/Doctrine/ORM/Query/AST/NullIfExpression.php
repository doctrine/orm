<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * NullIfExpression ::= "NULLIF" "(" ScalarExpression "," ScalarExpression ")"
 *
 * @link    www.doctrine-project.org
 */
class NullIfExpression extends Node
{
    /**
     * @param mixed $firstExpression
     * @param mixed $secondExpression
     */
    public function __construct(public $firstExpression, public $secondExpression)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkNullIfExpression($this);
    }
}
