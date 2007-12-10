<?php
/**
 * IdentificationVariableDeclaration = RangeVariableDeclaration {Join}
 */
class Doctrine_Query_Production_IdentificationVariableDeclaration extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->RangeVariableDeclaration();

        while ($this->_isNextToken(Doctrine_Query_Token::T_LEFT) ||
                $this->_isNextToken(Doctrine_Query_Token::T_INNER) ||
                $this->_isNextToken(Doctrine_Query_Token::T_JOIN)) {
            $this->Join();
        }
    }
}
