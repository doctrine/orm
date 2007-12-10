<?php
/**
 * Factor = [("+" | "-")] Primary
 */
class Doctrine_Query_Production_Factor extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        if ($this->_isNextToken('+')) {
            $this->_parser->match('+');
        } elseif ($this->_isNextToken('-')) {
            $this->_parser->match('-');
        }

        $this->Primary();
    }
}
