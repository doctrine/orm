<?php
/**
 * Atom = string | numeric | input_parameter
 */
class Doctrine_Query_Production_Atom extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        switch ($this->_parser->lookahead['type']) {
            case Doctrine_Query_Token::T_STRING:
                $this->_parser->match(Doctrine_Query_Token::T_STRING);
            break;
            case Doctrine_Query_Token::T_NUMERIC:
                $this->_parser->match(Doctrine_Query_Token::T_NUMERIC);
            break;
            case Doctrine_Query_Token::T_INPUT_PARAMETER:
                $this->_parser->match(Doctrine_Query_Token::T_INPUT_PARAMETER);
            break;
            default:
                $this->_parser->syntaxError();
        }
    }
}
