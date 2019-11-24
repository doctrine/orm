<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * IndexBy ::= "INDEX" "BY" SingleValuedPathExpression
 */
class IndexBy extends Node
{
    /** @var PathExpression */
    public $singleValuedPathExpression;

    /**
     * @param PathExpression $singleValuedPathExpression
     */
    public function __construct($singleValuedPathExpression)
    {
        $this->singleValuedPathExpression = $singleValuedPathExpression;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkIndexBy($this);
    }
}
