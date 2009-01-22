<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * SimpleArithmeticExpression ::= ArithmeticTerm {("+" | "-") ArithmeticTerm}*
 *
 * @author robo
 */
class SimpleArithmeticExpression extends Node
{
    private $_terms;

    public function __construct(array $arithmeticTerms)
    {
        $this->_terms = $arithmeticTerms;
    }

    public function getArithmeticTerms()
    {
        return $this->_terms;
    }
}

