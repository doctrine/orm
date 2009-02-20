<?php

namespace Doctrine\DBAL\Platforms;

/**
 * Enter description here...
 *
 * @since 2.0
 */
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
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param array $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      default
     *          Text value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @override
     */
    public function getNativeDeclaration($field)
    {
        if ( ! isset($field['type'])) {
            throw \Doctrine\Common\DoctrineException::updateMe('Missing column type.');
        }
        switch ($field['type']) {
            case 'varchar':
            case 'string':
            case 'array':
            case 'object':
            case 'char':
            case 'text':
            case 'gzip':
                $length = !empty($field['length'])
                    ? $field['length'] : 16777215; // TODO: $this->conn->options['default_text_field_length'];

                $fixed  = ((isset($field['fixed']) && $field['fixed']) || $field['type'] == 'char') ? true : false;

                return $fixed ? 'CHAR('.$length.')' : 'VARCHAR('.$length.')';
            case 'clob':
                return 'BLOB SUB_TYPE 1';
            case 'blob':
                return 'BLOB SUB_TYPE 0';
            case 'integer':
            case 'enum':
            case 'int':
                return 'INT';
            case 'boolean':
                return 'SMALLINT';
            case 'date':
                return 'DATE';
            case 'time':
                return 'TIME';
            case 'timestamp':
                return 'TIMESTAMP';
            case 'float':
                return 'DOUBLE PRECISION';
            case 'decimal':
                $length = !empty($field['length']) ? $field['length'] : 18;
                $scale = !empty($field['scale']) ? $field['scale'] : $this->conn->getAttribute(Doctrine::ATTR_DECIMAL_PLACES);
                return 'DECIMAL('.$length.','.$scale.')';
        }

        throw \Doctrine\Common\DoctrineException::updateMe('Unknown field type \'' . $field['type'] .  '\'.');
    }

    /**
     * Maps a native array description of a field to a Doctrine datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     * @override
     */
    public function getPortableDeclaration($field)
    {
        $length = (isset($field['length']) && $field['length'] > 0) ? $field['length'] : null;

        $type = array();
        $unsigned = $fixed = null;
        $dbType = strtolower($field['type']);
        $field['field_sub_type'] = !empty($field['field_sub_type'])
            ? strtolower($field['field_sub_type']) : null;

        if ( ! isset($field['name'])) {
            $field['name'] = '';
        }

        switch ($dbType) {
            case 'smallint':
            case 'integer':
            case 'int64':
                //these may be 'numeric' or 'decimal'
                if (isset($field['field_sub_type'])) {
                    $field['type'] = $field['field_sub_type'];
                    return $this->getPortableDeclaration($field);
                }
            case 'bigint':
            case 'quad':
                $type[] = 'integer';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                }
                break;
            case 'varchar':
                $fixed = false;
            case 'char':
            case 'cstring':
                $type[] = 'string';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
                break;
            case 'date':
                $type[] = 'date';
                $length = null;
                break;
            case 'timestamp':
                $type[] = 'timestamp';
                $length = null;
                break;
            case 'time':
                $type[] = 'time';
                $length = null;
                break;
            case 'float':
            case 'double':
            case 'double precision':
            case 'd_float':
                $type[] = 'float';
                break;
            case 'decimal':
            case 'numeric':
                $type[] = 'decimal';
                break;
            case 'blob':
                $type[] = ($field['field_sub_type'] == 'text') ? 'clob' : 'blob';
                $length = null;
                break;
            default:
                throw \Doctrine\Common\DoctrineException::updateMe('unknown database attribute type: '.$dbType);
        }

        return array('type'     => $type,
                     'length'   => $length,
                     'unsigned' => $unsigned,
                     'fixed'    => $fixed);
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
    
    /**
     * Enter description here...
     *
     * @param unknown_type $sequenceName
     * @override
     */
    public function getSequenceNextValSql($sequenceName)
    {
        return 'SELECT GEN_ID(' . $this->quoteIdentifier($sequenceName) . ', 1) FROM RDB$DATABASE';
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $level
     * @override
     */
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
    
    /**
     * Enter description here...
     *
     * @param unknown_type $level
     * @override
     */
    public function getSetTransactionIsolationSql($level)
    {
        return 'SET TRANSACTION ISOLATION LEVEL ' . $this->_getTransactionIsolationLevelSql($level);
    }
}