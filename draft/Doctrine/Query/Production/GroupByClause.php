<?php
/**
 * GroupByClause = "GROUP" "BY" GroupByItem {"," GroupByItem}
 */
class Doctrine_Query_Production_GroupByClause extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->_parser->match(Doctrine_Query_Token::T_GROUP);
        $this->_parser->match(Doctrine_Query_Token::T_BY);

        $this->GroupByItem();

        while ($this->_isNextToken(',')) {
            $this->_parser->match(',');
            $this->GroupByItem();
        }
    }
}
