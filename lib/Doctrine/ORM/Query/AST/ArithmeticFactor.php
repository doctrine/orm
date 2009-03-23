<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * ArithmeticFactor ::= [("+" | "-")] ArithmeticPrimary
 *
 * @author robo
 */
class ArithmeticFactor extends Node
{
    private $_arithmeticPrimary;
    private $_pSigned;
    private $_nSigned;

    public function __construct($arithmeticPrimary, $pSigned = false, $nSigned = false)
    {
        $this->_arithmeticPrimary = $arithmeticPrimary;
        $this->_pSigned = $pSigned;
        $this->_nSigned = $nSigned;
    }

    public function getArithmeticPrimary()
    {
        return $this->_arithmeticPrimary;
    }

    public function isPositiveSigned()
    {
        return $this->_pSigned;
    }

    public function isNegativeSigned()
    {
        return $this->_nSigned;
    }

    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkArithmeticFactor($this);
    }
}