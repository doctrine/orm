<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * ConditionalExpression ::= ConditionalTerm {"OR" ConditionalTerm}*
 *
 * @author robo
 */
class ConditionalExpression extends Node
{
    private $_conditionalTerms = array();

    public function __construct(array $conditionalTerms)
    {
        $this->_conditionalTerms = $conditionalTerms;
    }

    public function getConditionalTerms()
    {
        return $this->_conditionalTerms;
    }
}

