<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

class HavingClause extends Node
{
    public function __construct(public ConditionalExpression $conditionalExpression)
    {
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkHavingClause($this);
    }
}
