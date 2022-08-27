<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

class GroupByClause extends Node
{
    /** @var mixed[] */
    public $groupByItems = [];

    /** @param mixed[] $groupByItems */
    public function __construct(array $groupByItems)
    {
        $this->groupByItems = $groupByItems;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkGroupByClause($this);
    }
}
