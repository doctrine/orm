<?php
/**
 * UpdateClause = "UPDATE" RangeVariableDeclaration "SET" UpdateItem {"," UpdateItem}
 */
class Doctrine_Query_Production_UpdateClause extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->_parser->match(Doctrine_Query_Token::T_UPDATE);
        $this->RangeVariableDeclaration();
        $this->_parser->match(Doctrine_Query_Token::T_SET);

        $this->RangeVariableDeclaration();
        while ($this->_isNextToken(',')) {
            $this->_parser->match(',');
            $this->RangeVariableDeclaration();
        }
    }
}
