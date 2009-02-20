<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * LikeExpression ::= StringExpression ["NOT"] "LIKE" string ["ESCAPE" char]
 *
 * @author robo
 */
class LikeExpression extends Node
{
    private $_stringExpr;
    private $_isNot;
    private $_stringPattern;
    private $_escapeChar;

    public function __construct($stringExpr, $stringPattern, $isNot = false, $escapeChar = null)
    {
        $this->_stringExpr = $stringExpr;
        $this->_stringPattern = $stringPattern;
        $this->_isNot = $isNot;
        $this->_escapeChar = $escapeChar;
    }

    public function isNot()
    {
        return $this->_isNot;
    }

    public function getStringExpression()
    {
        return $this->_stringExpr;
    }

    public function getStringPattern()
    {
        return $this->_stringPattern;
    }
    
    public function getEscapeChar()
    {
        return $this->_escapeChar;
    }
}