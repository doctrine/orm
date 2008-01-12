<?php
/**
 * SelectExpression = (PathExpressionEndingWithAsterisk | Expression | "(" Subselect ")")
 *                    [["AS"] IdentificationVariable]
 */
class Doctrine_Query_Production_SelectExpression extends Doctrine_Query_Production
{
    private function _isPathExpressionEndingWithAsterisk()
    {
        $token = $this->_parser->lookahead;
        $this->_parser->getScanner()->resetPeek();

        while (($token['type'] === Doctrine_Query_Token::T_IDENTIFIER) || ($token['value'] === '.')) {
            $token = $this->_parser->getScanner()->peek();
        }

        return $token['value'] === '*';
    }

    private function _isSubquery()
    {
        $lookahead = $this->_parser->lookahead;
        $next = $this->_parser->getScanner()->peek();

        return $lookahead['value'] === '(' && $next['type'] === Doctrine_Query_Token::T_SELECT;
    }

    public function execute(array $params = array())
    {
        if ($this->_isPathExpressionEndingWithAsterisk()) {
            $this->PathExpressionEndingWithAsterisk();
        } elseif ($this->_isSubquery()) {
            $this->_parser->match('(');
            $this->Subselect();
            $this->_parser->match(')');
        } else {
            $this->Expression();
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_AS)) {
            $this->_parser->match(Doctrine_Query_Token::T_AS);
            $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);
        } elseif ($this->_isNextToken(Doctrine_Query_Token::T_IDENTIFIER)) {
            $this->IdentificationVariable();
        }
    }
}
