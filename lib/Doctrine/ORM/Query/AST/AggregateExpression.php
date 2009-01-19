<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AggregateExpression
 *
 * @author robo
 */
class Doctrine_ORM_Query_AST_AggregateExpression extends Doctrine_ORM_Query_AST
{
    private $_functionName;
    private $_pathExpression;
    private $_isDistinct = false; // Some aggregate expressions support distinct, eg COUNT

    public function __construct($functionName, $pathExpression, $isDistinct)
    {
        $this->_functionName = $functionName;
        $this->_pathExpression = $pathExpression;
        $this->_isDistinct = $isDistinct;
    }

    public function getPathExpression()
    {
        return $this->_pathExpression;
    }

    public function isDistinct()
    {
        return $this->_isDistinct;
    }

    public function getFunctionName()
    {
        return $this->_functionName;
    }
}

