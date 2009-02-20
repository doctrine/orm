<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * ConditionalFactor ::= ["NOT"] ConditionalPrimary
 *
 * @author robo
 */
class ConditionalFactor extends Node
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