<?php

namespace Doctrine\DBAL\Platforms;

class MsSqlPlatform extends AbstractPlatform
{ 
    /**
     * the constructor
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Adds an adapter-specific LIMIT clause to the SELECT statement.
     * [ borrowed from Zend Framework ]
     *
     * @param string $query
     * @param mixed $limit
     * @param mixed $offset
     * @link http://lists.bestpractical.com/pipermail/rt-devel/2005-June/007339.html
     * @return string
     * @override
     */
    public function writeLimitClause($query, $limit, $offset)
    {
        if ($limit > 0) {
            $count = intval($limit);

            $offset = intval($offset);
            if ($offset < 0) {
                throw \Doctrine\Common\DoctrineException::updateMe("LIMIT argument offset=$offset is not valid");
            }
    
            $orderby = stristr($query, 'ORDER BY');
            if ($orderby !== false) {
                $sort = (stripos($orderby, 'desc') !== false) ? 'desc' : 'asc';
                $order = str_ireplace('ORDER BY', '', $orderby);
                $order = trim(preg_replace('/ASC|DESC/i', '', $order));
            }
    
            $query = preg_replace('/^SELECT\s/i', 'SELECT TOP ' . ($count+$offset) . ' ', $query);
    
            $query = 'SELECT * FROM (SELECT TOP ' . $count . ' * FROM (' . $query . ') AS inner_tbl';
            if ($orderby !== false) {
                $query .= ' ORDER BY ' . $order . ' ';
                $query .= (stripos($sort, 'asc') !== false) ? 'DESC' : 'ASC';
            }
            $query .= ') AS outer_tbl';
            if ($orderby !== false) {
                $query .= ' ORDER BY ' . $order . ' ' . $sort;
            }
    
            return $query;

        }

        return $query;
    }
    
    /**
     * Return string to call a variable with the current timestamp inside an SQL statement
     * There are three special variables for current date and time:
     * - CURRENT_TIMESTAMP (date and time, TIMESTAMP type)
     * - CURRENT_DATE (date, DATE type)
     * - CURRENT_TIME (time, TIME type)
     *
     * @return string to call a variable with the current timestamp
     * @override
     */
    public function getNowExpression($type = 'timestamp')
    {
        switch ($type) {
            case 'time':
            case 'date':
            case 'timestamp':
            default:
                return 'GETDATE()';
        }
    }

    /**
     * return string to call a function to get a substring inside an SQL statement
     *
     * @return string to call a function to get a substring
     * @override
     */
    public function getSubstringExpression($value, $position, $length = null)
    {
        if ( ! is_null($length)) {
            return 'SUBSTRING(' . $value . ', ' . $position . ', ' . $length . ')';
        }
        return 'SUBSTRING(' . $value . ', ' . $position . ', LEN(' . $value . ') - ' . $position . ' + 1)';
    }

    /**
     * Returns string to concatenate two or more string parameters
     *
     * @param string $arg1
     * @param string $arg2
     * @param string $values...
     * @return string to concatenate two strings
     * @override
     */
    public function getConcatExpression()
    {
        $args = func_get_args();
        return '(' . implode(' + ', $args) . ')';
    }

    /**
     * Returns global unique identifier
     *
     * @return string to get global unique identifier
     * @override
     */
    public function getGuidExpression()
    {
        return 'NEWID()';
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
     *
     * @return      string      DBMS specific SQL code portion that should be used to
     *                          declare the specified field.
     * @override
     */
    public function getNativeDeclaration($field)
    {
        if ( ! isset($field['type'])) {
            throw \Doctrine\Common\DoctrineException::updateMe('Missing column type.');
        }
        switch ($field['type']) {
            case 'array':
            case 'object':
            case 'text':
            case 'char':
            case 'varchar':
            case 'string':
            case 'gzip':
                $length = !empty($field['length'])
                    ? $field['length'] : false;

                $fixed  = ((isset($field['fixed']) && $field['fixed']) || $field['type'] == 'char') ? true : false;

                return $fixed ? ($length ? 'CHAR('.$length.')' : 'CHAR('.$this->conn->options['default_text_field_length'].')')
                    : ($length ? 'VARCHAR('.$length.')' : 'TEXT');
            case 'clob':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 8000) {
                        return 'VARCHAR('.$length.')';
                    }
                 }
                 return 'TEXT';
            case 'blob':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 8000) {
                        return "VARBINARY($length)";
                    }
                }
                return 'IMAGE';
            case 'integer':
            case 'enum':
            case 'int':
                return 'INT';
            case 'boolean':
                return 'BIT';
            case 'date':
                return 'CHAR(' . strlen('YYYY-MM-DD') . ')';
            case 'time':
                return 'CHAR(' . strlen('HH:MM:SS') . ')';
            case 'timestamp':
                return 'CHAR(' . strlen('YYYY-MM-DD HH:MM:SS') . ')';
            case 'float':
                return 'FLOAT';
            case 'decimal':
                $length = !empty($field['length']) ? $field['length'] : 18;
                $scale = !empty($field['scale']) ? $field['scale'] : $this->conn->getAttribute(Doctrine::ATTR_DECIMAL_PLACES);
                return 'DECIMAL('.$length.','.$scale.')';
        }

        throw \Doctrine\Common\DoctrineException::updateMe('Unknown field type \'' . $field['type'] .  '\'.');
    }

    /**
     * Maps a native array description of a field to a MDB2 datatype and length
     *
     * @param   array           $field native field description
     * @return  array           containing the various possible types, length, sign, fixed
     * @override
     */
    public function getPortableDeclaration($field)
    {
        $db_type = preg_replace('/[\d\(\)]/','', strtolower($field['type']) );
        $length  = (isset($field['length']) && $field['length'] > 0) ? $field['length'] : null;

        $type = array();
        // todo: unsigned handling seems to be missing
        $unsigned = $fixed = null;

        if ( ! isset($field['name']))
            $field['name'] = '';

        switch ($db_type) {
            case 'bit':
                $type[0] = 'boolean';
            break;
            case 'tinyint':
            case 'smallint':
            case 'int':
                $type[0] = 'integer';
                if ($length == 1) {
                    $type[] = 'boolean';
                }
            break;
            case 'datetime':
                $type[0] = 'timestamp';
            break;
            case 'float':
            case 'real':
            case 'numeric':
                $type[0] = 'float';
            break;
            case 'decimal':
            case 'money':
                $type[0] = 'decimal';
            break;
            case 'text':
            case 'varchar':
            case 'ntext':
            case 'nvarchar':
                $fixed = false;
            case 'char':
            case 'nchar':
                $type[0] = 'string';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^[is|has]/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                } elseif (strstr($db_type, 'text')) {
                    $type[] = 'clob';
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
            break;
            case 'image':
            case 'varbinary':
                $type[] = 'blob';
                $length = null;
            break;
            default:
                throw \Doctrine\Common\DoctrineException::updateMe('unknown database attribute type: '.$db_type);
        }

        return array('type'     => $type,
                     'length'   => $length,
                     'unsigned' => $unsigned,
                     'fixed'    => $fixed);
    }
    
    /**
     * Quote a string so it can be safely used as a table / column name
     *
     * Quoting style depends on which database driver is being used.
     *
     * @param string $identifier    identifier name to be quoted
     * @param bool   $checkOption   check the 'quote_identifier' option
     *
     * @return string  quoted identifier string
     * @override
     */
    public function quoteIdentifier($identifier, $checkOption = false)
    {
        if ($checkOption && ! $this->getAttribute(Doctrine::ATTR_QUOTE_IDENTIFIER)) {
            return $identifier;
        }
        
        if (strpos($identifier, '.') !== false) { 
            $parts = explode('.', $identifier); 
            $quotedParts = array(); 
            foreach ($parts as $p) { 
                $quotedParts[] = $this->quoteIdentifier($p); 
            }
            
            return implode('.', $quotedParts); 
        }
        
        return '[' . str_replace(']', ']]', $identifier) . ']';
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

?>