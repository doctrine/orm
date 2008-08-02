<?php


class Doctrine_DatabasePlatform_PostgreSqlPlatform extends Doctrine_DatabasePlatform
{
    /**
     * The reserved keywords by pgsql. Ordered alphabetically.
     * 
     * @param array
     * @todo Nedded? What about lazy initialization?
     */
    /*protected static $_reservedKeywords = array(
            'abort', 'absolute', 'access', 'action', 'add', 'after', 'aggregate',
            'all', 'alter', 'analyse', 'analyze', 'and', 'any', 'as', 'asc',
            'assertion', 'assignment', 'at', 'authorization', 'backward', 'before',
            'begin', 'between', 'bigint', 'binary', 'bit', 'boolean', 'both',
            'by', 'cache', 'called', 'cascade', 'case', 'cast', 'chain', 'char',
            'character', 'characteristics', 'check', 'checkpoint', 'class',
            'close', 'cluster', 'coalesce', 'collate', 'column', 'comment',
            'commit', 'committed', 'constraint', 'constraints', 'conversion',
            'convert', 'copy', 'create', 'createdb', 'createuser', 'cross',
            'current_date', 'current_time', 'current_timestamp', 'current_user',
            'cursor', 'cycle', 'database', 'day', 'deallocate', 'dec', 'decimal',
            'declare', 'default', 'deferrable', 'deferred', 'definer', 'delete',
            'delimiter', 'delimiters', 'desc', 'distinct', 'do', 'domain', 'double',
            'drop', 'each', 'else', 'encoding', 'encrypted', 'end', 'escape',
            'except', 'exclusive', 'execute', 'exists', 'explain', 'external',
            'extract', 'false', 'fetch', 'float', 'for', 'force', 'foreign',
            'forward', 'freeze', 'from', 'full', 'function', 'get', 'global',
            'grant', 'group', 'handler', 'having', 'hour', 'ilike', 'immediate',
            'immutable', 'implicit', 'in', 'increment', 'index', 'inherits',
            'initially', 'inner', 'inout', 'input', 'insensitive', 'insert',
            'instead', 'int', 'integer', 'intersect', 'interval', 'into', 'invoker',
            'is', 'isnull', 'isolation', 'join', 'key', 'lancompiler', 'language',
            'leading', 'left', 'level', 'like', 'limit', 'listen', 'load', 'local',
            'localtime', 'localtimestamp', 'location', 'lock', 'match', 'maxvalue',
            'minute', 'minvalue', 'mode', 'month', 'move', 'names', 'national',
            'natural', 'nchar', 'new', 'next', 'no', 'nocreatedb', 'nocreateuser',
            'none', 'not', 'nothing', 'notify', 'notnull', 'null', 'nullif',
            'numeric', 'of', 'off', 'offset', 'oids', 'old', 'on', 'only', 'operator',
            'option', 'or', 'order', 'out', 'outer', 'overlaps', 'overlay',
            'owner', 'partial', 'password', 'path', 'pendant', 'placing', 'position',
            'precision', 'prepare', 'primary', 'prior', 'privileges', 'procedural',
            'procedure', 'read', 'real', 'recheck', 'references', 'reindex',
            'relative', 'rename', 'replace', 'reset', 'restrict', 'returns',
            'revoke', 'right', 'rollback', 'row', 'rule', 'schema', 'scroll',
            'second', 'security', 'select', 'sequence', 'serializable', 'session',
            'session_user', 'set', 'setof', 'share', 'show', 'similar', 'simple',
            'smallint', 'some', 'stable', 'start', 'statement', 'statistics',
            'stdin', 'stdout', 'storage', 'strict', 'substring', 'sysid', 'table',
            'temp', 'template', 'temporary', 'then', 'time', 'timestamp', 'to',
            'toast', 'trailing', 'transaction', 'treat', 'trigger', 'trim', 'true',
            'truncate', 'trusted', 'type', 'unencrypted', 'union', 'unique',
            'unknown', 'unlisten', 'until', 'update', 'usage', 'user', 'using',
            'vacuum', 'valid', 'validator', 'values', 'varchar', 'varying',
            'verbose', 'version', 'view', 'volatile', 'when', 'where', 'with',
            'without', 'work', 'write', 'year','zone');*/
    
    
    /**
     * Constructor.
     * Creates a new PostgreSqlPlatform.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_properties['string_quoting'] = array('start' => "'",
                                                    'end' => "'",
                                                    'escape' => "'",
                                                    'escape_pattern' => '\\');
        $this->_properties['identifier_quoting'] = array('start' => '"',
                                                        'end' => '"',
                                                        'escape' => '"');
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
    public function getNativeDeclaration(array $field)
    {
        if ( ! isset($field['type'])) {
            throw new Doctrine_DataDict_Exception('Missing column type.');
        }
        switch ($field['type']) {
            case 'char':
            case 'string':
            case 'array':
            case 'object':
            case 'varchar':
            case 'gzip':
                // TODO: what is the maximum VARCHAR length in pgsql ?
                $length = (isset($field['length']) && $field['length'] && $field['length'] < 10000) ? $field['length'] : null;

                $fixed  = ((isset($field['fixed']) && $field['fixed']) || $field['type'] == 'char') ? true : false;

                return $fixed ? ($length ? 'CHAR(' . $length . ')' : 'CHAR('.$this->conn->options['default_text_field_length'].')')
                    : ($length ? 'VARCHAR(' .$length . ')' : 'TEXT');

            case 'clob':
                return 'TEXT';
            case 'blob':
                return 'BYTEA';
            case 'enum':
            case 'integer':
            case 'int':
                if ( ! empty($field['autoincrement'])) {
                    if ( ! empty($field['length'])) {
                        $length = $field['length'];
                        if ($length > 4) {
                            return 'BIGSERIAL';
                        }
                    }
                    return 'SERIAL';
                }
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 2) {
                        return 'SMALLINT';
                    } elseif ($length == 3 || $length == 4) {
                        return 'INT';
                    } elseif ($length > 4) {
                        return 'BIGINT';
                    }
                }
                return 'INT';
            case 'boolean':
                return 'BOOLEAN';
            case 'date':
                return 'DATE';
            case 'time':
                return 'TIME without time zone';
            case 'timestamp':
                return 'TIMESTAMP without time zone';
            case 'float':
            case 'double':
                return 'FLOAT';
            case 'decimal':
                $length = !empty($field['length']) ? $field['length'] : 18;
                $scale = !empty($field['scale']) ? $field['scale'] : $this->conn->getAttribute(Doctrine::ATTR_DECIMAL_PLACES);
                return 'NUMERIC('.$length.','.$scale.')';
        }
        throw new Doctrine_DataDict_Exception('Unknown field type \'' . $field['type'] .  '\'.');
    }

    /**
     * Maps a native array description of a field to a portable Doctrine datatype and length
     *
     * @param array  $field native field description
     *
     * @return array containing the various possible types, length, sign, fixed
     * @override
     */
    public function getPortableDeclaration(array $field)
    {

        $length = (isset($field['length'])) ? $field['length'] : null;
        if ($length == '-1' && isset($field['atttypmod'])) {
            $length = $field['atttypmod'] - 4;
        }
        if ((int)$length <= 0) {
            $length = null;
        }
        $type = array();
        $unsigned = $fixed = null;

        if ( ! isset($field['name'])) {
            $field['name'] = '';
        }

        $dbType = strtolower($field['type']);

        switch ($dbType) {
            case 'smallint':
            case 'int2':
                $type[] = 'integer';
                $unsigned = false;
                $length = 2;
                if ($length == '2') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                }
                break;
            case 'int':
            case 'int4':
            case 'integer':
            case 'serial':
            case 'serial4':
                $type[] = 'integer';
                $unsigned = false;
                $length = 4;
                break;
            case 'bigint':
            case 'int8':
            case 'bigserial':
            case 'serial8':
                $type[] = 'integer';
                $unsigned = false;
                $length = 8;
                break;
            case 'bool':
            case 'boolean':
                $type[] = 'boolean';
                $length = 1;
                break;
            case 'text':
            case 'varchar':
            case 'interval':
            case '_varchar':
                $fixed = false;
            case 'unknown':
            case 'char':
            case 'bpchar':
                $type[] = 'string';
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
            case 'timestamptz':
                $type[] = 'timestamp';
                $length = null;
                break;
            case 'time':
                $type[] = 'time';
                $length = null;
                break;
            case 'float':
            case 'float4':
            case 'float8':
            case 'double':
            case 'double precision':
            case 'real':
                $type[] = 'float';
                break;
            case 'decimal':
            case 'money':
            case 'numeric':
                $type[] = 'decimal';
                break;
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'blob':
            case 'bytea':
                $type[] = 'blob';
                $length = null;
                break;
            case 'oid':
                $type[] = 'blob';
                $type[] = 'clob';
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
     * Returns the md5 sum of a field.
     *
     * Note: Not SQL92, but common functionality
     *
     * md5() works with the default PostgreSQL 8 versions.
     *
     * If you are using PostgreSQL 7.x or older you need
     * to make sure that the digest procedure is installed.
     * If you use RPMS (Redhat and Mandrake) install the postgresql-contrib
     * package. You must then install the procedure by running this shell command:
     * <code>
     * psql [dbname] < /usr/share/pgsql/contrib/pgcrypto.sql
     * </code>
     * You should make sure you run this as the postgres user.
     *
     * @return string
     * @override
     */
    public function getMd5Expression($column)
    {
        $column = $this->getIdentifier($column);

        if ($this->_version > 7) {
            return 'MD5(' . $column . ')';
        } else {
            return 'encode(digest(' . $column .', md5), hex)';
        }
    }

    /**
     * Returns part of a string.
     *
     * Note: Not SQL92, but common functionality.
     *
     * @param string $value the target $value the string or the string column.
     * @param int $from extract from this characeter.
     * @param int $len extract this amount of characters.
     * @return string sql that extracts part of a string.
     * @override
     */
    public function getSubstringExpression($value, $from, $len = null)
    {
        $value = $this->getIdentifier($value);

        if ($len === null) {
            $len = $this->getIdentifier($len);
            return 'SUBSTR(' . $value . ', ' . $from . ')';
        } else {
            return 'SUBSTR(' . $value . ', ' . $from . ', ' . $len . ')';
        }
    }

    /**
     * PostgreSQLs AGE(<timestamp1> [, <timestamp2>]) function.
     *
     * @param string $timestamp1 timestamp to subtract from NOW()
     * @param string $timestamp2 optional; if given: subtract arguments
     * @return string
     */
    public function getAgeExpression($timestamp1, $timestamp2 = null)
    {
        if ( $timestamp2 == null ) {
            return 'AGE(' . $timestamp1 . ')';
        }
        return 'AGE(' . $timestamp1 . ', ' . $timestamp2 . ')';
    }

    /**
     * PostgreSQLs DATE_PART( <text>, <time> ) function.
     *
     * @param string $text what to extract
     * @param string $time timestamp or interval to extract from
     * @return string
     */
    public function getDatePartExpression($text, $time)
    {
        return 'DATE_PART(' . $text . ', ' . $time . ')';
    }

    /**
     * PostgreSQLs TO_CHAR( <time>, <text> ) function.
     *
     * @param string $time timestamp or interval
     * @param string $text how to the format the output
     * @return string
     */
    public function getToCharExpression($time, $text)
    {
        return 'TO_CHAR(' . $time . ', ' . $text . ')';
    }

    /**
     * Returns the SQL string to return the current system date and time.
     *
     * @return string
     */
    public function getNowExpression()
    {
        return 'LOCALTIMESTAMP(0)';
    }

    /**
     * regexp
     *
     * @return string           the regular expression operator
     * @override
     */
    public function getRegexpExpression()
    {
        return 'SIMILAR TO';
    }

    /**
     * return string to call a function to get random value inside an SQL statement
     *
     * @return return string to generate float between 0 and 1
     * @access public
     * @override
     */
    public function getRandomExpression()
    {
        return 'RANDOM()';
    }

    /**
     * build a pattern matching string
     *
     * EXPERIMENTAL
     *
     * WARNING: this function is experimental and may change signature at
     * any time until labelled as non-experimental
     *
     * @access public
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
                $match = $field.'ILIKE ';
                break;
                // case sensitive
            case 'LIKE':
                $match = $field.'LIKE ';
                break;
            default:
                throw new Doctrine_Expression_Pgsql_Exception('not a supported operator type:'. $operator);
            }
        }
        $match.= "'";
        foreach ($pattern as $key => $value) {
            if ($key % 2) {
                $match.= $value;
            } else {
                $match.= $this->conn->escapePattern($this->conn->escape($value));
            }
        }
        $match.= "'";
        $match.= $this->patternEscapeString();
        
        return $match;
    }
    
    /**
     * parses a literal boolean value and returns
     * proper sql equivalent
     *
     * @param string $value     boolean value to be parsed
     * @return string           parsed boolean value
     */
    public function parseBoolean($value)
    {
        return $value;
    }
    
    /**
     * Whether the platform supports sequences.
     * Postgres has native support for sequences.
     *
     * @return boolean
     */
    public function supportsSequences()
    {
        return true;
    }
    
    /**
     * Whether the platform supports identity columns.
     * Postgres supports these through the SERIAL keyword.
     *
     * @return boolean
     */
    public function supportsIdentityColumns()
    {
        return true;
    }
    
    /**
     * Whether the platform prefers sequences for ID generation.
     *
     * @return boolean
     */
    public function prefersSequences()
    {
        return true;
    }
}

?>