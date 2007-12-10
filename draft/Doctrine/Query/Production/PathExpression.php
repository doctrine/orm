<?php
/**
 * PathExpression = identifier { "." identifier }
 */
class Doctrine_Query_Production_PathExpression extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);

        while ($this->_isNextToken('.')) {
            $this->_parser->match('.');
            $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);
        }
    }
}
