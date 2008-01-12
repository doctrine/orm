<?php
/**
 * RangeVariableDeclaration = AbstractSchemaName ["AS"] IdentificationVariable
 */
class Doctrine_Query_Production_RangeVariableDeclaration extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->AbstractSchemaName();

        if ($this->_isNextToken(Doctrine_Query_Token::T_AS)) {
            $this->_parser->match(Doctrine_Query_Token::T_AS);
        }

        $this->IdentificationVariable();
    }
}
