<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * ConditionalPrimary ::= SimpleConditionalExpression | "(" ConditionalExpression ")"
 *
 * @author robo
 */
class ConditionalPrimary extends Node
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
        return (bool) $this->_simpleConditionalExpression;
    }

    public function isConditionalExpression()
    {
        return (bool) $this->_conditionalExpression;
    }

    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkConditionalPrimary($this);
    }
}