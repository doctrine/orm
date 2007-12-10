<?php
/**
 * ConditionalTerm = ConditionalFactor {"AND" ConditionalFactor}
 */
class Doctrine_Query_Production_ConditionalTerm extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->ConditionalFactor();

        while ($this->_isNextToken(Doctrine_Query_Token::T_AND)) {
            $this->_parser->match(Doctrine_Query_Token::T_AND);
            $this->ConditionalFactor();
        }
    }
}
