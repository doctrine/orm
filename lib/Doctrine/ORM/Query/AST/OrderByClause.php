<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * OrderByClause ::= "ORDER" "BY" OrderByItem {"," OrderByItem}*
 *
 * @link    www.doctrine-project.org
 */
class OrderByClause extends Node
{
    /** @param OrderByItem[] $orderByItems */
    public function __construct(public array $orderByItems)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkOrderByClause($this);
    }
}
