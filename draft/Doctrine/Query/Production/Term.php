<?php
/**
 * Term = Factor {("*" | "/") Factor}
 */
class Doctrine_Query_Production_Term extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->Factor();

        while ($this->_isNextToken('*') || $this->_isNextToken('/')) {
            if ($this->_isNextToken('*')) {
                $this->_parser->match('*');
            } else {
                $this->_parser->match('/');
            }
            $this->Factor();
        }
    }
}
