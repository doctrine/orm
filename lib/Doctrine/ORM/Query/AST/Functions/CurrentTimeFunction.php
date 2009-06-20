<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST\Functions;

/**
 * "CURRENT_TIME"
 *
 * @author robo
 */
class CurrentTimeFunction extends FunctionNode
{
    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getCurrentTimeSql();
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $lexer = $parser->getLexer();
        $parser->match($lexer->lookahead['value']);
        $parser->match('(');
        $parser->match(')');
    }
}