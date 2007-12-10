<?php
/**
 * HavingClause = "HAVING" ConditionalExpression
 */
class Doctrine_Query_Production_HavingClause extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->_parser->match(Doctrine_Query_Token::T_HAVING);

        $this->ConditionalExpression();
    }
}
