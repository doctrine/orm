<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST\Functions;

/**
 * "SUBSTRING" "(" StringPrimary "," SimpleArithmeticExpression "," SimpleArithmeticExpression ")"
 *
 * @author robo
 */
class SubstringFunction extends FunctionNode
{
    private $_stringPrimary;
    private $_firstSimpleArithmeticExpression;
    private $_secondSimpleArithmeticExpression;

    public function geStringPrimary()
    {
        return $this->_stringPrimary;
    }

    public function getSecondSimpleArithmeticExpression()
    {
        return $this->_secondSimpleArithmeticExpression;
    }

    public function getFirstSimpleArithmeticExpression()
    {
        return $this->_firstSimpleArithmeticExpression;
    }

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        //TODO: Use platform to get SQL
        $sql = 'SUBSTRING(' .
                $sqlWalker->walkStringPrimary($this->_stringPrimary)
                . ', ' .
                $sqlWalker->walkSimpleArithmeticExpression($this->_firstSimpleArithmeticExpression)
                . ', ' .
                $sqlWalker->walkSimpleArithmeticExpression($this->_secondSimpleArithmeticExpression)
                . ')';
        return $sql;
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $lexer = $parser->getLexer();
        $parser->match($lexer->lookahead['value']);
        $parser->match('(');

        $this->_stringPrimary = $parser->_StringPrimary();
        $parser->match(',');
        $this->_firstSimpleArithmeticExpression = $parser->_SimpleArithmeticExpression();
        $parser->match(',');
        $this->_secondSimpleArithmeticExpression = $parser->_SimpleArithmeticExpression();

        $parser->match(')');
    }
}

