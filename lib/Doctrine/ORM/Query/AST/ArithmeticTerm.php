<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * ArithmeticTerm ::= ArithmeticFactor {("*" | "/") ArithmeticFactor}*
 *
 * @author robo
 */
class ArithmeticTerm extends Node
{
    private $_factors;

    public function __construct(array $arithmeticFactors)
    {
        $this->_factors = $arithmeticFactors;
    }

    public function getArithmeticFactors()
    {
        return $this->_factors;
    }
}