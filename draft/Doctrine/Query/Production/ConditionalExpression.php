<?php
/**
 * ConditionalExpression = ConditionalTerm {"OR" ConditionalTerm}
 */
class Doctrine_Query_Production_ConditionalExpression extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->ConditionalTerm();

        while ($this->_isNextToken(Doctrine_Query_Token::T_OR)) {
            $this->_parser->match(Doctrine_Query_Token::T_OR);
            $this->ConditionalTerm();
        }
    }
}
