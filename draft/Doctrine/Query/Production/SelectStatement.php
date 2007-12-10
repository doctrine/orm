<?php
/**
 * SelectStatement = [SelectClause] FromClause [WhereClause] [GroupByClause]
 *     [HavingClause] [OrderByClause] [LimitClause]
 */
class Doctrine_Query_Production_SelectStatement extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        if ($this->_isNextToken(Doctrine_Query_Token::T_SELECT)) {
            $this->SelectClause();
        }

        $this->FromClause();

        if ($this->_isNextToken(Doctrine_Query_Token::T_WHERE)) {
            $this->WhereClause();
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_GROUP)) {
            $this->GroupByClause();
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_HAVING)) {
            $this->HavingClause();
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_ORDER)) {
            $this->OrderByClause();
        }

        if ($this->_isNextToken(Doctrine_Query_Token::T_LIMIT)) {
            $this->LimitClause();
        }
    }
}
