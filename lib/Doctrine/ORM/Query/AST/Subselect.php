<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * Subselect ::= SimpleSelectClause SubselectFromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]
 *
 * @link    www.doctrine-project.org
 */
class Subselect extends Node
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
     * @param SimpleSelectClause  $simpleSelectClause
     * @param SubselectFromClause $subselectFromClause
     */
    public function __construct(public $simpleSelectClause, public $subselectFromClause)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkSubselect($this);
    }
}
