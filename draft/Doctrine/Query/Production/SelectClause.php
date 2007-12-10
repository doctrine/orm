<?php
/**
 * SelectClause = "SELECT" ["DISTINCT"] SelectExpression {"," SelectExpression}
 */
class Doctrine_Query_Production_SelectClause extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->_parser->match(Doctrine_Query_Token::T_SELECT);

        if ($this->_isNextToken(Doctrine_Query_Token::T_DISTINCT)) {
            $this->_parser->match(Doctrine_Query_Token::T_DISTINCT);
        }

        $this->SelectExpression();

        while ($this->_isNextToken(',')) {
            $this->_parser->match(',');
            $this->SelectExpression();
        }
    }
}
