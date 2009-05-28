<?php

namespace Doctrine\DBAL\Platforms;

class FirebirdPlatform extends AbstractPlatform
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Adds an driver-specific LIMIT clause to the query
     *
     * @param string $query     query to modify
     * @param integer $limit    limit the number of rows
     * @param integer $offset   start reading from given offset
     * @return string modified  query
     * @override
     */
    public function writeLimitClause($query, $limit, $offset)
    {
        if ( ! $offset) {
            $offset = 0;
        }
        if ($limit > 0) {
            $query = preg_replace('/^([\s(])*SELECT(?!\s*FIRST\s*\d+)/i',
                "SELECT FIRST $limit SKIP $offset", $query);
        }
        return $query;
    }
    
    /**
     * return string for internal table used when calling only a function
     *
     * @return string for internal table used when calling only a function
     */
    public function getFunctionTableExpression()
    {
        return ' FROM RDB$DATABASE';
    }

    /**
     * build string to define escape pattern string
     *
     * @return string define escape pattern
     * @override
     */
    public function getPatternEscapeStringExpression()
    {
        return " ESCAPE '". $this->_properties['escape_pattern'] ."'";
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the CHARACTER SET
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $charset   name of the charset
     * @return string  DBMS specific SQL code portion needed to set the CHARACTER SET
     *                 of a field declaration.
     */
    public function getCharsetFieldDeclaration($charset)
    {
        return 'CHARACTER SET ' . $charset;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the COLLATION
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $collation   name of the collation
     * @return string  DBMS specific SQL code portion needed to set the COLLATION
     *                 of a field declaration.
     */
    public function getCollationFieldDeclaration($collation)
    {
        return 'COLLATE ' . $collation;
    }

    public function getSequenceNextValSql($sequenceName)
    {
        return 'SELECT GEN_ID(' . $this->quoteIdentifier($sequenceName) . ', 1) FROM RDB$DATABASE';
    }

    protected function _getTransactionIsolationLevelSql($level)
    {
        switch ($level) {
            case Doctrine_DBAL_Connection::TRANSACTION_READ_UNCOMMITTED:
                return 'READ COMMITTED RECORD_VERSION';
            case Doctrine_DBAL_Connection::TRANSACTION_READ_COMMITTED:
                return 'READ COMMITTED NO RECORD_VERSION';
            case Doctrine_DBAL_Connection::TRANSACTION_REPEATABLE_READ:
                return 'SNAPSHOT';
            case Doctrine_DBAL_Connection::TRANSACTION_SERIALIZABLE:
                return 'SNAPSHOT TABLE STABILITY';
            default:
                return parent::_getTransactionIsolationLevelSql($level);
        }
    }

    public function getSetTransactionIsolationSql($level)
    {
        return 'SET TRANSACTION ISOLATION LEVEL ' . $this->_getTransactionIsolationLevelSql($level);
    }

    public function getName()
    {
        return 'firebird';
    }
}