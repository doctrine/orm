<?php
/**
 * DeleteClause = "DELETE" "FROM" RangeVariableDeclaration
 */
class Doctrine_Query_Production_DeleteClause extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->_parser->match(Doctrine_Query_Token::T_DELETE);
        $this->_parser->match(Doctrine_Query_Token::T_FROM);
        $this->RangeVariableDeclaration();
    }
}
