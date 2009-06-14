<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST\Functions;

/**
 * "MOD" "(" SimpleArithmeticExpression "," SimpleArithmeticExpression ")"
 *
 * @author robo
 */
class ModFunction extends FunctionNode
{
    private $_firstSimpleArithmeticExpression;
    private $_secondSimpleArithmeticExpression;

    public function getFirstSimpleArithmeticExpression()
    {
        return $this->_firstSimpleArithmeticExpression;
    }

    public function getSecondSimpleArithmeticExpression()
    {
        return $this->_secondSimpleArithmeticExpression;
    }

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        //TODO: Use platform to get SQL
        return 'SQRT(' .
                $sqlWalker->walkSimpleArithmeticExpression($this->_firstSimpleArithmeticExpression)
                . ', ' .
                $sqlWalker->walkSimpleArithmeticExpression($this->_secondSimpleArithmeticExpression)
                . ')';
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $lexer = $parser->getLexer();
        $parser->match($lexer->lookahead['value']);
        $parser->match('(');
        $this->_firstSimpleArithmeticExpression = $parser->SimpleArithmeticExpression();
        $parser->match(',');
        $this->_secondSimpleArithmeticExpression = $parser->SimpleArithmeticExpression();
        $parser->match(')');
    }
}

