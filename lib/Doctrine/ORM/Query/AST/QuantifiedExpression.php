<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * QuantifiedExpression ::= ("ALL" | "ANY" | "SOME") "(" Subselect ")"
 *
 * @author robo
 */
class QuantifiedExpression extends Node
{
    private $_all;
    private $_any;
    private $_some;
    private $_subselect;

    public function __construct($subselect)
    {
        $this->_subselect = $subselect;
    }

    public function getSubselect()
    {
        return $this->_subselect;
    }

    public function isAll()
    {
        return $this->_all;
    }

    public function isAny()
    {
        return $this->_any;
    }

    public function isSome()
    {
        return $this->_some;
    }

    public function setAll($bool)
    {
        $this->_all = $bool;
    }

    public function setAny($bool)
    {
        $this->_any = $bool;
    }

    public function setSome($bool)
    {
        $this->_some = $bool;
    }

    /**
     * @override
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkQuantifiedExpression($this);
    }
}

