<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * IndexBy ::= "INDEX" "BY" SimpleStateFieldPathExpression
 */
class IndexBy extends Node
{
    /** @var PathExpression */
    public $simpleStateFieldPathExpression;

    /**
     * @param PathExpression $simpleStateFieldPathExpression
     */
    public function __construct($simpleStateFieldPathExpression)
    {
        $this->simpleStateFieldPathExpression = $simpleStateFieldPathExpression;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkIndexBy($this);
    }
}
