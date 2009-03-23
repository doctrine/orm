<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST\Functions;

/**
 * "UPPER" "(" StringPrimary ")"
 *
 * @author robo
 */
class UpperFunction extends FunctionNode
{
    private $_stringPrimary;

    public function getStringPrimary()
    {
        return $this->_stringPrimary;
    }

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        //TODO: Use platform to get SQL
        return 'UPPER(' . $sqlWalker->walkStringPrimary($this->_stringPrimary) . ')';
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
        $parser->match(')');
    }
}

