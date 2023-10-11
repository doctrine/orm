<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * SelectStatement = SelectClause FromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]
 *
 * @link    www.doctrine-project.org
 */
class SelectStatement extends Node
{
    /** @var WhereClause|null */
    public $whereClause;

    /** @var GroupByClause|null */
    public $groupByClause;

    /** @var HavingClause|null */
    public $havingClause;

    /** @var OrderByClause|null */
    public $orderByClause;

    /**
     * @param SelectClause $selectClause
     * @param FromClause   $fromClause
     */
    public function __construct(public $selectClause, public $fromClause)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkSelectStatement($this);
    }
}
