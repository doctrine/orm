<?php

/**
 * The MySqlPlatform provides the behavior, features and SQL dialect of the
 * MySQL database platform.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
class Doctrine_DatabasePlatform_MySqlPlatform extends Doctrine_DatabasePlatform
{
    /**
     * MySql reserved words.
     *
     * @var array
     * @todo Needed? What about lazy initialization?
     */
    /*protected static $_reservedKeywords = array(
                          'ADD', 'ALL', 'ALTER',
                          'ANALYZE', 'AND', 'AS',
                          'ASC', 'ASENSITIVE', 'BEFORE',
                          'BETWEEN', 'BIGINT', 'BINARY',
                          'BLOB', 'BOTH', 'BY',
                          'CALL', 'CASCADE', 'CASE',
                          'CHANGE', 'CHAR', 'CHARACTER',
                          'CHECK', 'COLLATE', 'COLUMN',
                          'CONDITION', 'CONNECTION', 'CONSTRAINT',
                          'CONTINUE', 'CONVERT', 'CREATE',
                          'CROSS', 'CURRENT_DATE', 'CURRENT_TIME',
                          'CURRENT_TIMESTAMP', 'CURRENT_USER', 'CURSOR',
                          'DATABASE', 'DATABASES', 'DAY_HOUR',
                          'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND',
                          'DEC', 'DECIMAL', 'DECLARE',
                          'DEFAULT', 'DELAYED', 'DELETE',
                          'DESC', 'DESCRIBE', 'DETERMINISTIC',
                          'DISTINCT', 'DISTINCTROW', 'DIV',
                          'DOUBLE', 'DROP', 'DUAL',
                          'EACH', 'ELSE', 'ELSEIF',
                          'ENCLOSED', 'ESCAPED', 'EXISTS',
                          'EXIT', 'EXPLAIN', 'FALSE',
                          'FETCH', 'FLOAT', 'FLOAT4',
                          'FLOAT8', 'FOR', 'FORCE',
                          'FOREIGN', 'FROM', 'FULLTEXT',
                          'GRANT', 'GROUP', 'HAVING',
                          'HIGH_PRIORITY', 'HOUR_MICROSECOND', 'HOUR_MINUTE',
                          'HOUR_SECOND', 'IF', 'IGNORE',
                          'IN', 'INDEX', 'INFILE',
                          'INNER', 'INOUT', 'INSENSITIVE',
                          'INSERT', 'INT', 'INT1',
                          'INT2', 'INT3', 'INT4',
                          'INT8', 'INTEGER', 'INTERVAL',
                          'INTO', 'IS', 'ITERATE',
                          'JOIN', 'KEY', 'KEYS',
                          'KILL', 'LEADING', 'LEAVE',
                          'LEFT', 'LIKE', 'LIMIT',
                          'LINES', 'LOAD', 'LOCALTIME',
                          'LOCALTIMESTAMP', 'LOCK', 'LONG',
                          'LONGBLOB', 'LONGTEXT', 'LOOP',
                          'LOW_PRIORITY', 'MATCH', 'MEDIUMBLOB',
                          'MEDIUMINT', 'MEDIUMTEXT', 'MIDDLEINT',
                          'MINUTE_MICROSECOND', 'MINUTE_SECOND', 'MOD',
                          'MODIFIES', 'NATURAL', 'NOT',
                          'NO_WRITE_TO_BINLOG', 'NULL', 'NUMERIC',
                          'ON', 'OPTIMIZE', 'OPTION',
                          'OPTIONALLY', 'OR', 'ORDER',
                          'OUT', 'OUTER', 'OUTFILE',
                          'PRECISION', 'PRIMARY', 'PROCEDURE',
                          'PURGE', 'RAID0', 'READ',
                          'READS', 'REAL', 'REFERENCES',
                          'REGEXP', 'RELEASE', 'RENAME',
                          'REPEAT', 'REPLACE', 'REQUIRE',
                          'RESTRICT', 'RETURN', 'REVOKE',
                          'RIGHT', 'RLIKE', 'SCHEMA',
                          'SCHEMAS', 'SECOND_MICROSECOND', 'SELECT',
                          'SENSITIVE', 'SEPARATOR', 'SET',
                          'SHOW', 'SMALLINT', 'SONAME',
                          'SPATIAL', 'SPECIFIC', 'SQL',
                          'SQLEXCEPTION', 'SQLSTATE', 'SQLWARNING',
                          'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT',
                          'SSL', 'STARTING', 'STRAIGHT_JOIN',
                          'TABLE', 'TERMINATED', 'THEN',
                          'TINYBLOB', 'TINYINT', 'TINYTEXT',
                          'TO', 'TRAILING', 'TRIGGER',
                          'TRUE', 'UNDO', 'UNION',
                          'UNIQUE', 'UNLOCK', 'UNSIGNED',
                          'UPDATE', 'USAGE', 'USE',
                          'USING', 'UTC_DATE', 'UTC_TIME',
                          'UTC_TIMESTAMP', 'VALUES', 'VARBINARY',
                          'VARCHAR', 'VARCHARACTER', 'VARYING',
                          'WHEN', 'WHERE', 'WHILE',
                          'WITH', 'WRITE', 'X509',
                          'XOR', 'YEAR_MONTH', 'ZEROFILL'
                          );*/
    
    /**
     * Constructor.
     * Creates a new MySqlPlatform instance.
     */
    public function __construct()
    {
        parent::__construct();      
    }
    
    /**
     * Gets the character used for identifier quoting.
     *
     * @return string
     * @override
     */
    public function getIdentifierQuoteCharacter()
    {
        return '`';
    }
    
    /**
     * Returns the regular expression operator.
     *
     * @return string
     * @override
     */
    public function getRegexpExpression()
    {
        return 'RLIKE';
    }

    /**
     * return string to call a function to get random value inside an SQL statement
     *
     * @return string to generate float between 0 and 1
     */
    public function getRandomExpression()
    {
        return 'RAND()';
    }

    /**
     * Builds a pattern matching string.
     *
     * EXPERIMENTAL
     *
     * WARNING: this function is experimental and may change signature at
     * any time until labelled as non-experimental.
     *
     * @param array $pattern even keys are strings, odd are patterns (% and _)
     * @param string $operator optional pattern operator (LIKE, ILIKE and maybe others in the future)
     * @param string $field optional field name that is being matched against
     *                  (might be required when emulating ILIKE)
     *
     * @return string SQL pattern
     * @override
     */
    public function getMatchPatternExpression($pattern, $operator = null, $field = null)
    {
        $match = '';
        if ( ! is_null($operator)) {
            $field = is_null($field) ? '' : $field.' ';
            $operator = strtoupper($operator);
            switch ($operator) {
                // case insensitive
                case 'ILIKE':
                    $match = $field.'LIKE ';
                    break;
                // case sensitive
                case 'LIKE':
                    $match = $field.'LIKE BINARY ';
                    break;
                default:
                    throw new Doctrine_Expression_Mysql_Exception('not a supported operator type:'. $operator);
            }
        }
        $match.= "'";
        foreach ($pattern as $key => $value) {
            if ($key % 2) {
                $match .= $value;
            } else {
                $match .= $this->conn->escapePattern($this->conn->escape($value));
            }
        }
        $match.= "'";
        $match.= $this->patternEscapeString();
        
        return $match;
    }

    /**
     * Returns global unique identifier
     *
     * @return string to get global unique identifier
     * @override
     */
    public function getGuidExpression()
    {
        return 'UUID()';
    }

    /**
     * Returns a series of strings concatinated
     *
     * concat() accepts an arbitrary number of parameters. Each parameter
     * must contain an expression or an array with expressions.
     *
     * @param string|array(string) strings that will be concatinated.
     * @override
     */
    public function getConcatExpression()
    {
        $args = func_get_args();
        return 'CONCAT(' . join(', ', (array) $args) . ')';
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
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @override
     */
    public function getNativeDeclaration($field)
    {
        if ( ! isset($field['type'])) {
            throw new Doctrine_DataDict_Exception('Missing column type.');
        }

        switch ($field['type']) {
            case 'char':
                $length = ( ! empty($field['length'])) ? $field['length'] : false;

                return $length ? 'CHAR('.$length.')' : 'CHAR(255)';
            case 'varchar':
            case 'array':
            case 'object':
            case 'string':
            case 'gzip':
                if ( ! isset($field['length'])) {
                    if (array_key_exists('default', $field)) {
                        $field['length'] = $this->conn->varchar_max_length;
                    } else {
                        $field['length'] = false;
                    }
                }

                $length = ($field['length'] <= $this->conn->varchar_max_length) ? $field['length'] : false;
                $fixed  = (isset($field['fixed'])) ? $field['fixed'] : false;

                return $fixed ? ($length ? 'CHAR(' . $length . ')' : 'CHAR(255)')
                    : ($length ? 'VARCHAR(' . $length . ')' : 'TEXT');
            case 'clob':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 255) {
                        return 'TINYTEXT';
                    } elseif ($length <= 65532) {
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
                    } elseif ($length <= 65532) {
                        return 'BLOB';
                    } elseif ($length <= 16777215) {
                        return 'MEDIUMBLOB';
                    }
                }
                return 'LONGBLOB';
            case 'enum':
                if ($this->conn->getAttribute(Doctrine::ATTR_USE_NATIVE_ENUM)) {
                    $values = array();
                    foreach ($field['values'] as $value) {
                      $values[] = $this->conn->quote($value, 'varchar');
                    }
                    return 'ENUM('.implode(', ', $values).')';
                }
                // fall back to integer
            case 'integer':
            case 'int':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 1) {
                        return 'TINYINT';
                    } elseif ($length == 2) {
                        return 'SMALLINT';
                    } elseif ($length == 3) {
                        return 'MEDIUMINT';
                    } elseif ($length == 4) {
                        return 'INT';
                    } elseif ($length > 4) {
                        return 'BIGINT';
                    }
                }
                return 'INT';
            case 'boolean':
                return 'TINYINT(1)';
            case 'date':
                return 'DATE';
            case 'time':
                return 'TIME';
            case 'timestamp':
                return 'DATETIME';
            case 'float':
            case 'double':
                return 'DOUBLE';
            case 'decimal':
                $length = !empty($field['length']) ? $field['length'] : 18;
                $scale = !empty($field['scale']) ? $field['scale'] : $this->conn->getAttribute(Doctrine::ATTR_DECIMAL_PLACES);
                return 'DECIMAL('.$length.','.$scale.')';
        }
        throw new Doctrine_DataDict_Exception('Unknown field type \'' . $field['type'] .  '\'.');
    }

    /**
     * Maps a native array description of a field to a Doctrine datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     * @override
     */
    public function getPortableDeclaration(array $field)
    {
        $dbType = strtolower($field['type']);
        $dbType = strtok($dbType, '(), ');
        if ($dbType == 'national') {
            $dbType = strtok('(), ');
        }
        if (isset($field['length'])) {
            $length = $field['length'];
            $decimal = '';
        } else {
            $length = strtok('(), ');
            $decimal = strtok('(), ');
        }
        $type = array();
        $unsigned = $fixed = null;

        if ( ! isset($field['name'])) {
            $field['name'] = '';
        }

        $values = null;

        switch ($dbType) {
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
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 4;
            break;
            case 'bigint':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 8;
            break;
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
            case 'text':
            case 'text':
            case 'varchar':
                $fixed = false;
            case 'string':
            case 'char':
                $type[] = 'string';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                } elseif (strstr($dbType, 'text')) {
                    $type[] = 'clob';
                    if ($decimal == 'binary') {
                        $type[] = 'blob';
                    }
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
            break;
            case 'enum':
                $type[] = 'enum';
                preg_match_all('/\'((?:\'\'|[^\'])*)\'/', $field['type'], $matches);
                $length = 0;
                $fixed = false;
                if (is_array($matches)) {
                    foreach ($matches[1] as &$value) {
                        $value = str_replace('\'\'', '\'', $value);
                        $length = max($length, strlen($value));
                    }
                    if ($length == '1' && count($matches[1]) == 2) {
                        $type[] = 'boolean';
                        if (preg_match('/^(is|has)/', $field['name'])) {
                            $type = array_reverse($type);
                        }
                    } else {
                        $values = $matches[1];
                    }
                }
                $type[] = 'integer';
                break;
            case 'set':
                $fixed = false;
                $type[] = 'text';
                $type[] = 'integer';
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
                $unsigned = preg_match('/ unsigned/i', $field['type']);
            break;
            case 'unknown':
            case 'decimal':
            case 'numeric':
                $type[] = 'decimal';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
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
                throw new Doctrine_DataDict_Exception('unknown database attribute type: ' . $dbType);
        }

        $length = ((int) $length == 0) ? null : (int) $length;

        if ($values === null) {
            return array('type' => $type, 'length' => $length, 'unsigned' => $unsigned, 'fixed' => $fixed);
        } else {
            return array('type' => $type, 'length' => $length, 'unsigned' => $unsigned, 'fixed' => $fixed, 'values' => $values);
        }
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
     * Whether the platform prefers identity columns for ID generation.
     * MySql prefers "autoincrement" identity columns since sequences can only
     * be emulated with a table.
     *
     * @return boolean
     * @override
     */
    public function prefersIdentityColumns()
    {
        return true;
    }
    
    /**
     * Whether the platform supports identity columns.
     * MySql supports this through AUTO_INCREMENT columns.
     *
     * @return boolean
     * @override
     */
    public function supportsIdentityColumns()
    {
        return true;
    }
    
    /**
     * Whether the platform supports savepoints. MySql does not.
     *
     * @return boolean
     * @override
     */
    public function supportsSavepoints()
    {
        return false;
    }
}

?>