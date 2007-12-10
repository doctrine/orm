<?php
/**
 * UpdateStatement = UpdateClause [WhereClause] [OrderByClause] [LimitClause]
 */
class Doctrine_Query_Production_UpdateStatement extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->UpdateClause();

        if ($this->_isNextToken(Doctrine_Query_Token::T_WHERE)) {
            $this->WhereClause();
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_ORDER)) {
            $this->OrderByClause();
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_LIMIT)) {
            $this->LimitClause();
        }
    }
}
