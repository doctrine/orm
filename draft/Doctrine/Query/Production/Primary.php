<?php
/**
 * Primary = PathExpression | Atom | "(" Expression ")" | Function |
 *     AggregateExpression
 */
class Doctrine_Query_Production_Primary extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        switch ($this->_parser->lookahead['type']) {
            case Doctrine_Query_Token::T_IDENTIFIER:
                // @todo: custom functions
                $this->PathExpression();
            break;
            case Doctrine_Query_Token::T_STRING:
            case Doctrine_Query_Token::T_NUMERIC:
            case Doctrine_Query_Token::T_INPUT_PARAMETER:
                $this->Atom();
            break;
            case Doctrine_Query_Token::T_LENGTH:
            case Doctrine_Query_Token::T_LOCATE:
            case Doctrine_Query_Token::T_ABS:
            case Doctrine_Query_Token::T_SQRT:
            case Doctrine_Query_Token::T_MOD:
            case Doctrine_Query_Token::T_SIZE:
            case Doctrine_Query_Token::T_CURRENT_DATE:
            case Doctrine_Query_Token::T_CURRENT_TIMESTAMP:
            case Doctrine_Query_Token::T_CURRENT_TIME:
            case Doctrine_Query_Token::T_SUBSTRING:
            case Doctrine_Query_Token::T_CONCAT:
            case Doctrine_Query_Token::T_TRIM:
            case Doctrine_Query_Token::T_LOWER:
            case Doctrine_Query_Token::T_UPPER:
                $this->Function();
            break;
            case Doctrine_Query_Token::T_AVG:
            case Doctrine_Query_Token::T_MAX:
            case Doctrine_Query_Token::T_MIN:
            case Doctrine_Query_Token::T_SUM:
            case Doctrine_Query_Token::T_MOD:
            case Doctrine_Query_Token::T_SIZE:
                $this->AggregateExpression();
            break;
            case Doctrine_Query_Token::T_NONE:
                if ($this->_isNextToken('(')) {
                    $this->_parser->match('(');
                    $this->Expression();
                    $this->_parser->match(')');
                    break;
                }
            default:
                $this->_parser->syntaxError();
        }
    }
}
