<?php

namespace Doctrine\ORM\Query\AST;

/**
 * EmptyCollectionComparisonExpression ::= CollectionValuedPathExpression "IS" ["NOT"] "EMPTY"
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class EmptyCollectionComparisonExpression extends Node
{
    private $_expression;
    private $_not;

    public function __construct($expression)
    {
        $this->_expression = $expression;
    }

    public function getExpression()
    {
        return $this->_expression;
    }

    public function setNot($bool)
    {
        $this->_not = $bool;
    }

    public function isNot()
    {
        return $this->_not;
    }

    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkEmptyCollectionComparisonExpression($this);
    }
}

