<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * WhereClause ::= "WHERE" ConditionalExpression
 */
class WhereClause extends Node
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

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkWhereClause($this);
    }
}
