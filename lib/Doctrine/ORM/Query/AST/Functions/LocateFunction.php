<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST\Functions;

/**
 * "LOCATE" "(" StringPrimary "," StringPrimary ["," SimpleArithmeticExpression]")"
 *
 * @author robo
 */
class LocateFunction extends FunctionNode
{
    private $_firstStringPrimary;
    private $_secondStringPrimary;
    private $_simpleArithmeticExpression;

    public function getFirstStringPrimary()
    {
        return $this->_firstStringPrimary;
    }

    public function getSecondStringPrimary()
    {
        return $this->_secondStringPrimary;
    }

    public function getSimpleArithmeticExpression()
    {
        return $this->_simpleArithmeticExpression;
    }

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        //TODO: Use platform to get SQL
        $sql = 'LOCATE(' .
                $sqlWalker->walkStringPrimary($this->_firstStringPrimary)
                . ', ' .
                $sqlWalker->walkStringPrimary($this->_secondStringPrimary);
        
        if ($this->_simpleArithmeticExpression) {
            $sql .= ', ' . $sqlWalker->walkSimpleArithmeticExpression($this->_simpleArithmeticExpression);
        }
        return $sql . ')';
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $lexer = $parser->getLexer();
        $parser->match($lexer->lookahead['value']);
        $parser->match('(');
        $this->_firstStringPrimary = $parser->_StringPrimary();
        $parser->match(',');
        $this->_secondStringPrimary = $parser->_StringPrimary();
        if ($lexer->isNextToken(',')) {
            $parser->match(',');
            $this->_simpleArithmeticExpression = $parser->_SimpleArithmeticExpression();
        }
        $parser->match(')');
    }
}

