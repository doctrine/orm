<?php
/**
 * SelectExpression  = (Expression | "(" Subselect ")" ) [["AS"] identifier]
 */
class Doctrine_Query_Production_SelectExpression extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        if ($this->_isSubquery()) {
            $this->_parser->match('(');
            $this->Subselect();
            $this->_parser->match(')');
        } else {
            $this->Expression();
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_AS)) {
            $this->_parser->match(Doctrine_Query_Token::T_AS);
            $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);
        } elseif ($this->_isNextToken(Doctrine_Query_Token::T_IDENTIFIER)) {
            $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);
        }
    }
}
