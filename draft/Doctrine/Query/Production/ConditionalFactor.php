<?php
/**
 * ConditionalFactor = ["NOT"] ConditionalPrimary
 */
class Doctrine_Query_Production_ConditionalFactor extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        if ($this->_isNextToken(Doctrine_Query_Token::T_NOT)) {
            $this->_parser->match(Doctrine_Query_Token::T_NOT);
        }

        $this->ConditionalPrimary();
    }
}
