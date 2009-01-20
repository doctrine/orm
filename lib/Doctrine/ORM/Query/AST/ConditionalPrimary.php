<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * ConditionalPrimary ::= SimpleConditionalExpression | "(" ConditionalExpression ")"
 *
 * @author robo
 */
class Doctrine_ORM_Query_AST_ConditionalPrimary extends Doctrine_ORM_Query_AST
{
    private $_simpleConditionalExpression;
    private $_conditionalExpression;

    public function setSimpleConditionalExpression($simpleConditionalExpr)
    {
        $this->_simpleConditionalExpression = $simpleConditionalExpr;
    }

    public function setConditionalExpression($conditionalExpr)
    {
        $this->_conditionalExpression = $conditionalExpr;
    }

    public function getSimpleConditionalExpression()
    {
        return $this->_simpleConditionalExpression;
    }

    public function getConditionalExpression()
    {
        return $this->_conditionalExpression;
    }

    public function isSimpleConditionalExpression()
    {
        return (bool)$this->_simpleConditionalExpression;
    }

    public function isConditionalExpression()
    {
        return (bool)$this->_conditionalExpression;
    }
}

