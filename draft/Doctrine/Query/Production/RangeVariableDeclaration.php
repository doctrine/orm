<?php
/**
 * RangeVariableDeclaration = PathExpression [["AS" ] identifier]
 */
class Doctrine_Query_Production_RangeVariableDeclaration extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->PathExpression();

        if ($this->_isNextToken(Doctrine_Query_Token::T_AS)) {
            $this->_parser->match(Doctrine_Query_Token::T_AS);
            $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);
        } elseif ($this->_isNextToken(Doctrine_Query_Token::T_IDENTIFIER)) {
            $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);
        }
    }
}
