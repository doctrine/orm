<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * ExistsExpression ::= ["NOT"] "EXISTS" "(" Subselect ")"
 *
 * @author robo
 */
class ExistsExpression extends Node
{
    private $_not = false;
    private $_subselect;

    public function __construct($subselect)
    {
        $this->_subselect = $subselect;
    }

    public function setNot($bool)
    {
        $this->_not = $bool;
    }

    public function isNot()
    {
        return $this->_not;
    }

    public function getSubselect()
    {
        return $this->_subselect;
    }

    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkExistsExpression($this);
    }
}

