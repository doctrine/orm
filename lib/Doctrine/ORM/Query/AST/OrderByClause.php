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
    /** @var OrderByItem[] */
    public $orderByItems = [];

    /** @param OrderByItem[] $orderByItems */
    public function __construct(array $orderByItems)
    {
        $this->orderByItems = $orderByItems;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkOrderByClause($this);
    }
}
