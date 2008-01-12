<?php
/**
 * PathExpressionEndingWithAsterisk = {identifier "."} "*"
 */
class Doctrine_Query_Production_PathExpressionEndingWithAsterisk extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        while ($this->_isNextToken(Doctrine_Query_Token::T_IDENTIFIER)) {
            $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);
            $this->_parser->match('.');
        }

        $this->_parser->match('*');
    }
}
