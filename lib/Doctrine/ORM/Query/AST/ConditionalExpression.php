<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * ConditionalExpression ::= ConditionalTerm {"OR" ConditionalTerm}*
 *
 * @author robo
 */
class Doctrine_ORM_Query_AST_ConditionalExpression extends Doctrine_ORM_Query_AST
{
    private $_conditionalTerms = array();

    public function __construct(array $conditionalTerms)
    {
        $this->_conditionalTerms = $conditionalTerms;
    }

    public function getConditionalTerms()
    {
        return $this->_conditionalTerm;
    }
}

