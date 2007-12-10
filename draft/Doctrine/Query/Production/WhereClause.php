<?php
/**
 * WhereClause = "WHERE" ConditionalExpression
 */
class Doctrine_Query_Production_WhereClause extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->_parser->match(Doctrine_Query_Token::T_WHERE);

        $this->ConditionalExpression();
    }
}
