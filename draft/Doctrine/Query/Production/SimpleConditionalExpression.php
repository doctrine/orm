<?php
/**
 * SimpleConditionalExpression =
 *     Expression (ComparisonExpression | BetweenExpression | LikeExpression |
 *     InExpression | NullComparisonExpression | QuantifiedExpression) |
 *     ExistsExpression
 */
class Doctrine_Query_Production_SimpleConditionalExpression extends Doctrine_Query_Production
{
    protected function _getExpressionType() {
        if ($this->_isNextToken(Doctrine_Query_Token::T_NOT)) {
            $token = $this->_parser->getScanner()->peek();
            $this->_parser->getScanner()->resetPeek();
        } else {
            $token = $this->_parser->lookahead;
        }

        return $token['type'];
    }

    public function execute(array $params = array())
    {
        if ($this->_getExpressionType() === Doctrine_Query_Token::T_EXISTS) {
            $this->ExistsExpression();
        } else {
            $this->Expression();

            switch ($this->_getExpressionType()) {
                case Doctrine_Query_Token::T_BETWEEN:
                    $this->BetweenExpression();
                break;
                case Doctrine_Query_Token::T_LIKE:
                    $this->LikeExpression();
                break;
                case Doctrine_Query_Token::T_IN:
                    $this->InExpression();
                break;
                case Doctrine_Query_Token::T_IS:
                    $this->NullComparisonExpression();
                break;
                case Doctrine_Query_Token::T_NONE:
                    $this->ComparisonExpression();
                break;
                default:
                    $this->_parser->syntaxError();
            }
        }

    }
}
