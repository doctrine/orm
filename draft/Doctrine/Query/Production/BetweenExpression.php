<?php
/**
 * BetweenExpression = ["NOT"] "BETWEEN" Expression "AND" Expression
 */
class Doctrine_Query_Production_BetweenExpression extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        if ($this->_isNextToken(Doctrine_Query_Token::T_NOT)) {
            $this->_parser->match(Doctrine_Query_Token::T_NOT);
        }

        $this->_parser->match(Doctrine_Query_Token::T_BETWEEN);
        $this->Expression();
        $this->_parser->match(Doctrine_Query_Token::T_AND);
        $this->Expression();
    }
}
