<?php

#namespace Doctrine::DBAL::Platforms;

/**
 * Enter description here...
 *
 * @since 2.0
 */
class Doctrine_DBAL_Platforms_SqlitePlatform extends Doctrine_DBAL_Platforms_AbstractPlatform
{
    
    /**
     * the constructor
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Returns the md5 sum of the data that SQLite's md5() function receives.
     *
     * @param mixed $data
     * @return string
     */
    public static function md5Impl($data)
    {
        return md5($data);
    }

    /**
     * Returns the modules of the data that SQLite's mod() function receives.
     *
     * @param integer $dividend
     * @param integer $divisor
     * @return string
     */
    public static function modImpl($dividend, $divisor)
    {
        return $dividend % $divisor;
    }

    /**
     * locate
     * returns the position of the first occurrence of substring $substr in string $str that
     * SQLite's locate() function receives
     *
     * @param string $substr    literal string to find
     * @param string $str       literal string
     * @return string
     */
    public static function locateImpl($substr, $str)
    {
        return strpos($str, $substr);
    }
    public static function sha1Impl($str)
    {
        return sha1($str);
    }
    public static function ltrimImpl($str)
    {
        return ltrim($str);
    }
    public static function rtrimImpl($str)
    {
        return rtrim($str);
    }
    public static function trimImpl($str)
    {
        return trim($str);
    }

    /**
     * returns the regular expression operator
     *
     * @return string
     * @override
     */
    public function getRegexpExpression()
    {
        return 'RLIKE';
    }

    /**
     * Returns a string to call a function to compute the
     * soundex encoding of a string
     *
     * The string "?000" is returned if the argument is NULL.
     *
     * @param string $value
     * @return string   SQL soundex function with given parameter
     */
    public function getSoundexExpression($value)
    {
        return 'SOUNDEX(' . $value . ')';
    }

    /**
     * Return string to call a variable with the current timestamp inside an SQL statement
     * There are three special variables for current date and time.
     *
     * @return string       sqlite function as string
     * @override
     */
    public function getNowExpression($type = 'timestamp')
    {
        switch ($type) {
            case 'time':
                return 'time(\'now\')';
            case 'date':
                return 'date(\'now\')';
            case 'timestamp':
            default:
                return 'datetime(\'now\')';
        }
    }

    /**
     * return string to call a function to get random value inside an SQL statement
     *
     * @return string to generate float between 0 and 1
     * @override
     */
    public function getRandomExpression()
    {
        return '((RANDOM() + 2147483648) / 4294967296)';
    }

    /**
     * return string to call a function to get a substring inside an SQL statement
     *
     * Note: Not SQL92, but common functionality.
     *
     * SQLite only supports the 2 parameter variant of this function
     *
     * @param string $value         an sql string literal or column name/alias
     * @param integer $position     where to start the substring portion
     * @param integer $length       the substring portion length
     * @return string               SQL substring function with given parameters
     * @override
     */
    public function getSubstringExpression($value, $position, $length = null)
    {
        if ($length !== null) {
            return 'SUBSTR(' . $value . ', ' . $position . ', ' . $length . ')';
        }
        return 'SUBSTR(' . $value . ', ' . $position . ', LENGTH(' . $value . '))';
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
     * @author Lukas Smith (PEAR MDB2 library)
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @override
     */
    public function getNativeDeclaration(array $field)
    {
        if ( ! isset($field['type'])) {
            throw new Doctrine_DataDict_Exception('Missing column type.');
        }
        switch ($field['type']) {
            case 'text':
            case 'object':
            case 'array':
            case 'string':
            case 'char':
            case 'gzip':
            case 'varchar':
                $length = (isset($field['length']) && $field['length']) ? $field['length'] : null;

                $fixed  = ((isset($field['fixed']) && $field['fixed']) || $field['type'] == 'char') ? true : false;

                return $fixed ? ($length ? 'CHAR('.$length.')' : 'CHAR('.$this->conn->getAttribute(Doctrine::ATTR_DEFAULT_TEXTFLD_LENGTH).')')
                    : ($length ? 'VARCHAR('.$length.')' : 'TEXT');
            case 'clob':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 255) {
                        return 'TINYTEXT';
                    } elseif ($length <= 65535) {
                        return 'TEXT';
                    } elseif ($length <= 16777215) {
                        return 'MEDIUMTEXT';
                    }
                }
                return 'LONGTEXT';
            case 'blob':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 255) {
                        return 'TINYBLOB';
                    } elseif ($length <= 65535) {
                        return 'BLOB';
                    } elseif ($length <= 16777215) {
                        return 'MEDIUMBLOB';
                    }
                }
                return 'LONGBLOB';
            case 'enum':
            case 'integer':
            case 'boolean':
            case 'int':
                return 'INTEGER';
            case 'date':
                return 'DATE';
            case 'time':
                return 'TIME';
            case 'timestamp':
                return 'DATETIME';
            case 'float':
            case 'double':
                return 'DOUBLE';//($this->conn->options['fixed_float'] ? '('.
                    //($this->conn->options['fixed_float']+2).','.$this->conn->options['fixed_float'].')' : '');
            case 'decimal':
                $length = !empty($field['length']) ? $field['length'] : 18;
                $scale = !empty($field['scale']) ? $field['scale'] : $this->conn->getAttribute(Doctrine::ATTR_DECIMAL_PLACES);
                return 'DECIMAL('.$length.','.$scale.')';
        }
        throw new Doctrine_DataDict_Exception('Unknown field type \'' . $field['type'] .  '\'.');
    }

    /**
     * Maps a native array description of a field to Doctrine datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     * @override
     */
    public function getPortableDeclaration(array $field)
    {
        $dbType = strtolower($field['type']);
        $length = (isset($field['length'])) ? $field['length'] : null;
        $unsigned = (isset($field['unsigned'])) ? $field['unsigned'] : null;
        $fixed = null;
        $type = array();

        if ( ! isset($field['name'])) {
            $field['name'] = '';
        }

        switch ($dbType) {
            case 'boolean':
                $type[] = 'boolean';
                break;
            case 'tinyint':
                $type[] = 'integer';
                $type[] = 'boolean';
                if (preg_match('/^(is|has)/', $field['name'])) {
                    $type = array_reverse($type);
                }
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 1;
                break;
            case 'smallint':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 2;
                break;
            case 'mediumint':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 3;
                break;
            case 'int':
            case 'integer':
            case 'serial':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 4;
                break;
            case 'bigint':
            case 'bigserial':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 8;
                break;
            case 'clob':
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
            case 'text':
            case 'varchar':
            case 'varchar2':
                $fixed = false;
            case 'char':
                $type[] = 'text';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                } elseif (strstr($dbType, 'text')) {
                    $type[] = 'clob';
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
                break;
            case 'date':
                $type[] = 'date';
                $length = null;
                break;
            case 'datetime':
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
            case 'real':
                $type[] = 'float';
                $length = null;
                break;
            case 'decimal':
            case 'numeric':
                $type[] = 'decimal';
                $length = null;
                break;
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'blob':
                $type[] = 'blob';
                $length = null;
                break;
            case 'year':
                $type[] = 'integer';
                $type[] = 'date';
                $length = null;
                break;
            default:
                throw new Doctrine_DataDict_Exception('unknown database attribute type: '.$dbType);
        }

        return array('type'     => $type,
                     'length'   => $length,
                     'unsigned' => $unsigned,
                     'fixed'    => $fixed);
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
                return 0;
            case Doctrine_DBAL_Connection::TRANSACTION_READ_COMMITTED:
            case Doctrine_DBAL_Connection::TRANSACTION_REPEATABLE_READ:
            case Doctrine_DBAL_Connection::TRANSACTION_SERIALIZABLE:
                return 1;
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
        return 'PRAGMA read_uncommitted = ' . $this->_getTransactionIsolationLevelSql($level);
    }
}

?>