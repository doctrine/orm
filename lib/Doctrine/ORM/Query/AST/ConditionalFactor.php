<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * ConditionalFactor ::= ["NOT"] ConditionalPrimary
 *
 * @author robo
 */
class Doctrine_ORM_Query_AST_ConditionalFactor extends Doctrine_ORM_Query_AST
{
    private $_not = false;
    private $_conditionalPrimary;

    public function __construct($conditionalPrimary, $not = false)
    {
        $this->_conditionalPrimary = $conditionalPrimary;
        $this->_not = $not;
    }

    public function isNot()
    {
        return $this->_not;
    }
    
    public function getConditionalPrimary()
    {
        return $this->_conditionalPrimary;
    }
}

