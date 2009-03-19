<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * Description of BetweenExpression
 *
 * @author robo
 */
class BetweenExpression extends Node
{
    private $_baseExpression;
    private $_leftBetweenExpression;
    private $_rightBetweenExpression;
    private $_not;

    public function __construct($baseExpr, $leftExpr, $rightExpr)
    {
        $this->_baseExpression = $baseExpr;
        $this->_leftBetweenExpression = $leftExpr;
        $this->_rightBetweenExpression = $rightExpr;
    }

    public function getBaseExpression()
    {
        return $this->_baseExpression;
    }

    public function getLeftBetweenExpression()
    {
        return $this->_leftBetweenExpression;
    }

    public function getRightBetweenExpression()
    {
        return $this->_rightBetweenExpression;
    }

    public function setNot($bool)
    {
        $this->_not = $bool;
    }

    public function getNot()
    {
        return $this->_not;
    }
}

