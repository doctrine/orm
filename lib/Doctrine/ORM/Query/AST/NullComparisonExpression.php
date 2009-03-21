<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * NullComparisonExpression ::= (SingleValuedPathExpression | InputParameter) "IS" ["NOT"] "NULL"
 *
 * @author robo
 */
class NullComparisonExpression extends Node
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
}

