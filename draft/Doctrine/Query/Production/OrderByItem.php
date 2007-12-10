<?php
/**
 * OrderByItem = PathExpression ["ASC" | "DESC"]
 */
class Doctrine_Query_Production_OrderByItem extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->PathExpression();

        if ($this->_isNextToken(Doctrine_Query_Token::T_ASC)) {
            $this->_parser->match(Doctrine_Query_Token::T_ASC);
        } elseif ($this->_isNextToken(Doctrine_Query_Token::T_DESC)) {
            $this->_parser->match(Doctrine_Query_Token::T_DESC);
        }
    }
}
