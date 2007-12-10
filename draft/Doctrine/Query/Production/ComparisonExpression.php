<?php
/**
 * ComparisonExpression = ComparisonOperator ( QuantifiedExpression | Expression | "(" Subselect ")" )
 */
class Doctrine_Query_Production_ComparisonExpression extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->ComparisonOperator();

        if ($this->_isSubquery()) {
            $this->_parser->match('(');
            $this->Subselect();
            $this->_parser->match(')');
        } else {
            switch ($this->_parser->lookahead['type']) {
                case Doctrine_Query_Token::T_ALL:
                case Doctrine_Query_Token::T_SOME:
                case Doctrine_Query_Token::T_NONE:
                    $this->QuantifiedExpression();
                break;
                default:
                    $this->Expression();
            }
        }
    }
}
