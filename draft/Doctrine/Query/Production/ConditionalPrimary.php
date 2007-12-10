<?php
/**
 * ConditionalPrimary = SimpleConditionalExpression | "(" ConditionalExpression ")"
 */
class Doctrine_Query_Production_ConditionalPrimary extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        if ($this->_isNextToken('(')) {
            $this->_parser->match('(');
            $this->ConditionalExpression();
            $this->_parser->match(')');
        } else {
            $this->SimpleConditionalExpression();
        }
    }
}
