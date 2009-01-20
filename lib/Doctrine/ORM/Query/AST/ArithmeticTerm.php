<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * ArithmeticTerm ::= ArithmeticFactor {("*" | "/") ArithmeticFactor}*
 *
 * @author robo
 */
class Doctrine_ORM_Query_AST_ArithmeticTerm extends Doctrine_ORM_Query_AST
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

