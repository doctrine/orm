<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

class HavingClause extends Node
{
    /** @var ConditionalExpression */
    public $conditionalExpression;

    /**
     * @param ConditionalExpression $conditionalExpression
     */
    public function __construct($conditionalExpression)
    {
        $this->conditionalExpression = $conditionalExpression;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkHavingClause($this);
    }
}
