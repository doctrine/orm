<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * ArithmeticExpression ::= SimpleArithmeticExpression | "(" Subselect ")"
 *
 * @author robo
 */
class Doctrine_ORM_Query_AST_ArithmeticExpression extends Doctrine_ORM_Query_AST
{
    private $_simpleArithmeticExpression;
    private $_subselect;

    public function setSimpleArithmeticExpression($simpleArithmeticExpr)
    {
        if ($this->_subselect) throw new Doctrine_Exception;
        $this->_simpleArithmeticExpression = $simpleArithmeticExpr;
    }

    public function setSubselect($subselect)
    {
        if ($this->_simpleArithmeticExpression) throw new Doctrine_Exception;
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
        return (bool)$this->_simpleArithmeticExpression;
    }

    public function isSubselect()
    {
        return (bool)$this->_subselect;
    }
}

