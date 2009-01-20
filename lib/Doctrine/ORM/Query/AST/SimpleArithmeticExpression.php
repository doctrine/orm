<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * SimpleArithmeticExpression ::= ArithmeticTerm {("+" | "-") ArithmeticTerm}*
 *
 * @author robo
 */
class Doctrine_ORM_Query_AST_SimpleArithmeticExpression extends Doctrine_ORM_Query_AST
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

