<?php
/**
 * FromClause = "FROM" IdentificationVariableDeclaration {"," IdentificationVariableDeclaration}
 */
class Doctrine_Query_Production_FromClause extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->_parser->match(Doctrine_Query_Token::T_FROM);

        $this->IdentificationVariableDeclaration();

        while ($this->_isNextToken(',')) {
            $this->_parser->match(',');
            $this->IdentificationVariableDeclaration();
        }
    }
}
