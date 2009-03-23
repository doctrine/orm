<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST\Functions;

/**
 * "ABS" "(" SimpleArithmeticExpression ")"
 *
 * @author robo
 */
class AbsFunction extends FunctionNode
{
    private $_simpleArithmeticExpression;

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
        return 'ABS(' . $sqlWalker->walkSimpleArithmeticExpression($this->_simpleArithmeticExpression) . ')';
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $lexer = $parser->getLexer();
        $parser->match($lexer->lookahead['value']);
        $parser->match('(');
        $this->_simpleArithmeticExpression = $parser->_SimpleArithmeticExpression();
        $parser->match(')');
    }
}

