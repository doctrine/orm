<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST\Functions;

/**
 * "CONCAT" "(" StringPrimary "," StringPrimary ")"
 *
 * @author robo
 */
class ConcatFunction extends FunctionNode
{
    private $_firstStringPrimary;
    private $_secondStringPriamry;

    public function getFirstStringPrimary()
    {
        return $this->_firstStringPrimary;
    }

    public function getSecondStringPrimary()
    {
        return $this->_secondStringPrimary;
    }

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        //TODO: Use platform to get SQL
        $sql = 'CONCAT(' .
                $sqlWalker->walkStringPrimary($this->_firstStringPrimary)
                . ', ' .
                $sqlWalker->walkStringPrimary($this->_secondStringPrimary)
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

        $this->_firstStringPrimary = $parser->_StringPrimary();
        $parser->match(',');
        $this->_secondStringPrimary = $parser->_StringPrimary();

        $parser->match(')');
    }
}

