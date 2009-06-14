<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST\Functions;

/**
 * "LENGTH" "(" StringPrimary ")"
 *
 * @author Roman Borschel <roman@code-factory.org>
 */
class LengthFunction extends FunctionNode
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
        return 'LENGTH(' . $sqlWalker->walkStringPrimary($this->_stringPrimary) . ')';
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $lexer = $parser->getLexer();
        $parser->match($lexer->lookahead['value']);
        $parser->match('(');
        $this->_stringPrimary = $parser->StringPrimary();
        $parser->match(')');
    }
}

