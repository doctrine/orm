<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * ComparisonExpression ::= ArithmeticExpression ComparisonOperator ( QuantifiedExpression | ArithmeticExpression ) |
 *                          StringExpression ComparisonOperator (StringExpression | QuantifiedExpression) |
 *                          BooleanExpression ("=" | "<>" | "!=") (BooleanExpression | QuantifiedExpression) |
 *                          EnumExpression ("=" | "<>" | "!=") (EnumExpression | QuantifiedExpression) |
 *                          DatetimeExpression ComparisonOperator (DatetimeExpression | QuantifiedExpression) |
 *                          EntityExpression ("=" | "<>") (EntityExpression | QuantifiedExpression)
 *
 * @author robo
 */
class ComparisonExpression extends Node
{
    private $_leftExpr;
    private $_rightExpr;
    private $_operator;

    public function __construct($leftExpr, $operator, $rightExpr)
    {
        $this->_leftExpr = $leftExpr;
        $this->_rightExpr = $rightExpr;
        $this->_operator = $operator;
    }

    public function getLeftExpression()
    {
        return $this->_leftExpr;
    }

    public function getRightExpression()
    {
        return $this->_rightExpr;
    }

    public function getOperator()
    {
        return $this->_operator;
    }
}