<?php

namespace Doctrine\ORM\Query\AST\Functions;

/**
 * "CURRENT_DATE"
 *
 * @author robo
 */
class CurrentDateFunction extends FunctionNode
{
    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return $sqlWalker->getConnection()->getDatabasePlatform()->getCurrentDateSql();
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