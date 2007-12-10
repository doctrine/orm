<?php
/**
 * Expression = Term {("+" | "-") Term}
 */
class Doctrine_Query_Production_Expression extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->Term();

        while ($this->_isNextToken('+') || $this->_isNextToken('-')) {
            if ($this->_isNextToken('+')) {
               $this->_parser->match('+');
            } else{
                $this->_parser->match('-');
            }
            $this->Term();
        }
    }
}
