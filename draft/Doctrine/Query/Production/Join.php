<?php
/**
 * Join = ["LEFT" ["OUTER"] | "INNER"] "JOIN" PathExpression "AS" identifier
 */
class Doctrine_Query_Production_Join extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        if ($this->_isNextToken(Doctrine_Query_Token::T_LEFT)) {
            $this->_parser->match(Doctrine_Query_Token::T_LEFT);

            if ($this->_isNextToken(Doctrine_Query_Token::T_OUTER)) {
                $this->_parser->match(Doctrine_Query_Token::T_OUTER);
            }

        } elseif ($this->_isNextToken(Doctrine_Query_Token::T_INNER)) {
            $this->_parser->match(Doctrine_Query_Token::T_INNER);
        }

        $this->_parser->match(Doctrine_Query_Token::T_JOIN);

        $this->PathExpression();

        $this->_parser->match(Doctrine_Query_Token::T_AS);
        $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);
    }
}
