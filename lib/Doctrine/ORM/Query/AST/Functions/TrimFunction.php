<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\Lexer;

/**
 * "TRIM" "(" [["LEADING" | "TRAILING" | "BOTH"] [char] "FROM"] StringPrimary ")"
 *
 * @author robo
 */
class TrimFunction extends FunctionNode
{
    private $_leading;
    private $_trailing;
    private $_both;
    private $_trimChar;
    private $_stringPrimary;

    public function getStringPrimary()
    {
        return $this->_stringPrimary;
    }

    public function isLeading()
    {
        return $this->_leading;
    }

    public function setLeading($bool)
    {
        $this->_leading = $bool;
    }

    public function isTrailing()
    {
        return $this->_trailing;
    }

    public function setTrailing($bool)
    {
        $this->_trailing = $bool;
    }

    public function isBoth()
    {
        return $this->_both;
    }

    public function setBoth($bool)
    {
        $this->_both = $bool;
    }

    public function getTrimChar()
    {
        return $this->_trimChar;
    }

    public function setTrimChar($trimChar)
    {
        $this->_trimChar = $trimChar;
    }

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        $sql = 'TRIM(';
        if ($this->_leading) $sql .= 'LEADING ';
        else if ($this->_trailing) $sql .= 'TRAILING ';
        else if ($this->_both) $sql .= 'BOTH ';
        if ($this->_trimChar) $sql .= $this->_trimChar . ' '; //TODO: quote()
        $sql .= 'FROM ' . $sqlWalker->walkStringPrimary($this->_stringPrimary);
        $sql .= ')';
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

        if (strcasecmp('leading', $lexer->lookahead['value']) === 0) {
            $parser->match($lexer->lookahead['value']);
            $this->_leading = true;
        } else if (strcasecmp('trailing', $lexer->lookahead['value']) === 0) {
            $parser->match($lexer->lookahead['value']);
            $this->_trailing = true;
        } else if (strcasecmp('both', $lexer->lookahead['value']) === 0) {
            $parser->match($lexer->lookahead['value']);
            $this->_both = true;
        }

        if ($lexer->isNextToken(Lexer::T_STRING)) {
            $parser->match(Lexer::T_STRING);
            $this->_trimChar = $lexer->token['value'];
        }

        if ($this->_leading || $this->_trailing || $this->_both || $this->_trimChar) {
            $parser->match(Lexer::T_FROM);
        }

        $this->_stringPrimary = $parser->_StringPrimary();
        
        $parser->match(')');
    }
    
}
