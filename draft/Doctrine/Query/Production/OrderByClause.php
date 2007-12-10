<?php
/**
 * OrderByClause = "ORDER" "BY" OrderByItem {"," OrderByItem}
 */
class Doctrine_Query_Production_OrderByClause extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->_parser->match(Doctrine_Query_Token::T_ORDER);
        $this->_parser->match(Doctrine_Query_Token::T_BY);

        $this->OrderByItem();

        while ($this->_isNextToken(',')) {
            $this->_parser->match(',');
            $this->OrderByItem();
        }
    }
}
