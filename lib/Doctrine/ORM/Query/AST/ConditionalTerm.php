<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * ConditionalTerm ::= ConditionalFactor {"AND" ConditionalFactor}*
 *
 * @author robo
 */
class ConditionalTerm extends Node
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

