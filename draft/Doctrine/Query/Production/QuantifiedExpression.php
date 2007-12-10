<?php
/**
 * QuantifiedExpression = ("ALL" | "ANY" | "SOME") "(" Subselect ")"
 */
class Doctrine_Query_Production_QuantifiedExpression extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        switch ($this->_parser->lookahead['type']) {
            case Doctrine_Query_Token::T_ALL:
                $this->_parser->match(Doctrine_Query_Token::T_ALL);
            break;
            case Doctrine_Query_Token::T_ANY:
                $this->_parser->match(Doctrine_Query_Token::T_ANY);
            break;
            case Doctrine_Query_Token::T_SOME:
                $this->_parser->match(Doctrine_Query_Token::T_SOME);
            break;
            default:
                $this->syntaxError();
        }

        $this->_parser->match('(');
        $this->Subselect();
        $this->_parser->match(')');
    }
}
