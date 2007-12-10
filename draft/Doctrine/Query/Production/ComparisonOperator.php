<?php
/**
 * ComparisonOperator = "=" | "<" | "<=" | "<>" | ">" | ">="
 */
class Doctrine_Query_Production_ComparisonOperator extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        switch ($this->_parser->lookahead['value']) {
            case '=':
                $this->_parser->match('=');
            break;
            case '<':
                $this->_parser->match('<');
                if ($this->_isNextToken('=')) {
                    $this->_parser->match('=');
                } elseif ($this->_isNextToken('>')) {
                    $this->_parser->match('>');
                }
            break;
            case '>':
                $this->_parser->match('>');
                if ($this->_isNextToken('=')) {
                    $this->_parser->match('=');
                }
            break;
            default:
                $this->_parser->syntaxError();
        }
    }
}
