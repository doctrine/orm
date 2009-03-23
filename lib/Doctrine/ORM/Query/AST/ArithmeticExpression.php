<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * ArithmeticExpression ::= SimpleArithmeticExpression | "(" Subselect ")"
 *
 * @author robo
 */
class ArithmeticExpression extends Node
{
    private $_simpleArithmeticExpression;
    private $_subselect;

    public function setSimpleArithmeticExpression($simpleArithmeticExpr)
    {
        if ($this->_subselect) {
            throw \Doctrine\Common\DoctrineException::updateMe();
        }
        $this->_simpleArithmeticExpression = $simpleArithmeticExpr;
    }

    public function setSubselect($subselect)
    {
        if ($this->_simpleArithmeticExpression){
            throw \Doctrine\Common\DoctrineException::updateMe();
        }
        $this->_subselect = $subselect;
    }

    public function getSimpleArithmeticExpression()
    {
        return $this->_simpleArithmeticExpression;
    }

    public function getSubselect()
    {
        return $this->_subselect;
    }

    public function isSimpleArithmeticExpression()
    {
        return (bool) $this->_simpleArithmeticExpression;
    }

    public function isSubselect()
    {
        return (bool) $this->_subselect;
    }

    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkArithmeticExpression($this);
    }
}