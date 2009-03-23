<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST\Functions;

/**
 * "SIZE" "(" CollectionValuedPathExpression ")"
 *
 * @author robo
 */
class SizeFunction extends FunctionNode
{
    private $_collectionPathExpression;

    public function getCollectionPathExpression()
    {
        return $this->_collectionPathExpression;
    }

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        //TODO: Construct appropriate SQL
        return "";
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $lexer = $parser->getLexer();
        $parser->match($lexer->lookahead['value']);
        $parser->match('(');
        $this->_collectionPathExpression = $parser->_CollectionValuedPathExpression();
        $parser->match(')');
    }
}

