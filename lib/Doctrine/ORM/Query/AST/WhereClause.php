<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WhereClause
 *
 * @author robo
 */
class Doctrine_ORM_Query_AST_WhereClause extends Doctrine_ORM_Query_AST
{
    private $_conditionalExpression;

    public function __construct($conditionalExpression)
    {
        $this->_conditionalExpression = $conditionalExpression;
    }

    public function getConditionalExpression()
    {
        return $this->_conditionalExpression;
    }
}

