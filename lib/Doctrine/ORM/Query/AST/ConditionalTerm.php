<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * ConditionalTerm ::= ConditionalFactor {"AND" ConditionalFactor}*
 *
 * @author robo
 */
class Doctrine_ORM_Query_AST_ConditionalTerm extends Doctrine_ORM_Query_AST
{
    private $_conditionalFactors = array();

    public function __construct(array $conditionalFactors)
    {
        $this->_conditionalFactors = $conditionalFactors;
    }

    public function getConditionalFactors()
    {
        return $this->_conditionalFactors;
    }
}

