<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL\Platforms;

/**
 * PostgreSqlPlatform.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 * @author Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 */
class PostgreSqlPlatform extends AbstractPlatform
{
    /**
     * Constructor.
     * Creates a new PostgreSqlPlatform.
     */
    public function __construct()
    {
        parent::__construct();
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
            throw DoctrineException::updateMe('Missing column type.');
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
        throw DoctrineException::updateMe('Unknown field type \'' . $field['type'] .  '\'.');
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
                throw DoctrineException::updateMe('unknown database attribute type: '.$dbType);
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
        $column = $this->quoteIdentifier($column);
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
        $value = $this->quoteIdentifier($value);

        if ($len === null) {
            $len = $this->quoteIdentifier($len);
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
                throw DoctrineException::updateMe('not a supported operator type:'. $operator);
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
    
    /**
     * Enter description here...
     *
     * @override
     */
    public function getListDatabasesSql()
    {
        return 'SELECT datname FROM pg_database';
    }
    
    /**
     * Enter description here...
     *
     * @override
     */
    public function getListFunctionsSql()
    {
        return "SELECT
                    proname
                FROM
                    pg_proc pr, pg_type tp
                WHERE
                    tp.oid = pr.prorettype
                AND pr.proisagg = FALSE
                AND tp.typname <> 'trigger'
                AND pr.pronamespace IN
                    (SELECT oid FROM pg_namespace
                    WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema'";
    }
    
    /**
     * Enter description here...
     *
     * @override
     */
    public function getListSequencesSql()
    {
        return "SELECT
                    relname
                FROM
                   pg_class
                WHERE relkind = 'S' AND relnamespace IN
                    (SELECT oid FROM pg_namespace
                        WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema')";
    }
    
    /**
     * Enter description here...
     *
     * @override
     */
    public function getListTablesSql()
    {
        return "SELECT
                    c.relname AS table_name
                FROM pg_class c, pg_user u
                WHERE c.relowner = u.usesysid
                    AND c.relkind = 'r'
                    AND NOT EXISTS (SELECT 1 FROM pg_views WHERE viewname = c.relname)
                    AND c.relname !~ '^(pg_|sql_)'
                UNION
                SELECT c.relname AS table_name
                FROM pg_class c
                WHERE c.relkind = 'r'
                    AND NOT EXISTS (SELECT 1 FROM pg_views WHERE viewname = c.relname)
                    AND NOT EXISTS (SELECT 1 FROM pg_user WHERE usesysid = c.relowner)
                    AND c.relname !~ '^pg_'";
    }
    
    /**
     * Enter description here...
     *
     * @override
     */
    public function getListViewsSql()
    {
        return 'SELECT viewname FROM pg_views';
    }
    
    /**
     * Enter description here...
     *
     * @override
     */
    public function getListUsersSql()
    {
        return 'SELECT usename FROM pg_user';
    }
    
    /**
     * Enter description here...
     *
     * @override
     */
    public function getListTableConstraintsSql($table)
    {
        return "SELECT
                    relname
                FROM
                    pg_class
                WHERE oid IN (
                    SELECT indexrelid
                    FROM pg_index, pg_class
                    WHERE pg_class.relname = '$table'
                        AND pg_class.oid = pg_index.indrelid
                        AND (indisunique = 't' OR indisprimary = 't')
                        )";
    }
    
    /**
     * Enter description here...
     *
     * @override
     */
    public function getListTableIndexesSql()
    {
        return "SELECT
                    relname
                FROM
                    pg_class
                WHERE oid IN (
                    SELECT indexrelid
                    FROM pg_index, pg_class
                    WHERE pg_class.relname = %s
                        AND pg_class.oid=pg_index.indrelid
                        AND indisunique != 't'
                        AND indisprimary != 't'
                )";
    }
    
    /**
     * Enter description here...
     *
     * @override
     */
    public function getListTableColumnsSql($table)
    {
        return "SELECT
                    a.attnum,
                    a.attname AS field,
                    t.typname AS type,
                    format_type(a.atttypid, a.atttypmod) AS complete_type,
                    a.attnotnull AS isnotnull,
                    (SELECT 't'
                     FROM pg_index
                     WHERE c.oid = pg_index.indrelid
                        AND pg_index.indkey[0] = a.attnum
                        AND pg_index.indisprimary = 't'
                    ) AS pri,
                    (SELECT pg_attrdef.adsrc
                     FROM pg_attrdef
                     WHERE c.oid = pg_attrdef.adrelid
                        AND pg_attrdef.adnum=a.attnum
                    ) AS default
                    FROM pg_attribute a, pg_class c, pg_type t
                    WHERE c.relname = '$table'
                        AND a.attnum > 0
                        AND a.attrelid = c.oid
                        AND a.atttypid = t.oid
                    ORDER BY a.attnum";
    }
    
    /**
     * create a new database
     *
     * @param string $name name of the database that should be created
     * @throws PDOException
     * @return void
     * @override
     */
    public function getCreateDatabaseSql($name)
    {
        return 'CREATE DATABASE ' . $this->quoteIdentifier($name);
    }
    
    /**
     * drop an existing database
     *
     * @param string $name name of the database that should be dropped
     * @throws PDOException
     * @access public
     */
    public function getDropDatabaseSql($name)
    {
        return 'DROP DATABASE ' . $this->quoteIdentifier($name);
    }
    
    /**
     * getAdvancedForeignKeyOptions
     * Return the FOREIGN KEY query section dealing with non-standard options
     * as MATCH, INITIALLY DEFERRED, ON UPDATE, ...
     *
     * @param array $definition         foreign key definition
     * @return string
     * @override
     */
    public function getAdvancedForeignKeyOptionsSql(array $definition)
    {
        $query = '';
        if (isset($definition['match'])) {
            $query .= ' MATCH ' . $definition['match'];
        }
        if (isset($definition['onUpdate'])) {
            $query .= ' ON UPDATE ' . $definition['onUpdate'];
        }
        if (isset($definition['onDelete'])) {
            $query .= ' ON DELETE ' . $definition['onDelete'];
        }
        if (isset($definition['deferrable'])) {
            $query .= ' DEFERRABLE';
        } else {
            $query .= ' NOT DEFERRABLE';
        }
        if (isset($definition['feferred'])) {
            $query .= ' INITIALLY DEFERRED';
        } else {
            $query .= ' INITIALLY IMMEDIATE';
        }
        return $query;
    }
    
    /**
     * generates the sql for altering an existing table on postgresql
     *
     * @param string $name          name of the table that is intended to be changed.
     * @param array $changes        associative array that contains the details of each type      *
     * @param boolean $check        indicates whether the function should just check if the DBMS driver
     *                              can perform the requested table alterations if the value is true or
     *                              actually perform them otherwise.
     * @see Doctrine_Export::alterTable()
     * @return array
     * @override
     */
    public function getAlterTableSql($name, array $changes, $check = false)
    {
        foreach ($changes as $changeName => $change) {
            switch ($changeName) {
                case 'add':
                case 'remove':
                case 'change':
                case 'name':
                case 'rename':
                    break;
                default:
                    throw DoctrineException::updateMe('change type "' . $changeName . '\" not yet supported');
            }
        }

        if ($check) {
            return true;
        }

        $sql = array();

        if (isset($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $fieldName => $field) {
                $query = 'ADD ' . $this->getColumnDeclarationSql($fieldName, $field);
                $sql[] = 'ALTER TABLE ' . $name . ' ' . $query;
            }
        }

        if (isset($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $fieldName => $field) {
                $fieldName = $this->quoteIdentifier($fieldName, true);
                $query = 'DROP ' . $fieldName;
                $sql[] = 'ALTER TABLE ' . $name . ' ' . $query;
            }
        }

        if (isset($changes['change']) && is_array($changes['change'])) {
            foreach ($changes['change'] as $fieldName => $field) {
                $fieldName = $this->quoteIdentifier($fieldName, true);
                if (isset($field['type'])) {
                    $serverInfo = $this->getServerVersion();

                    if (is_array($serverInfo) && $serverInfo['major'] < 8) {
                        throw DoctrineException::updateMe('changing column type for "'.$field['type'].'\" requires PostgreSQL 8.0 or above');
                    }
                    $query = 'ALTER ' . $fieldName . ' TYPE ' . $this->getTypeDeclarationSql($field['definition']);
                    $sql[] = 'ALTER TABLE ' . $name . ' ' . $query;
                }
                if (array_key_exists('default', $field)) {
                    $query = 'ALTER ' . $fieldName . ' SET DEFAULT ' . $this->quote($field['definition']['default'], $field['definition']['type']);
                    $sql[] = 'ALTER TABLE ' . $name . ' ' . $query;
                }
                if ( ! empty($field['notnull'])) {
                    $query = 'ALTER ' . $fieldName . ' ' . ($field['definition']['notnull'] ? 'SET' : 'DROP') . ' NOT NULL';
                    $sql[] = 'ALTER TABLE ' . $name . ' ' . $query;
                }
            }
        }

        if (isset($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $fieldName => $field) {
                $fieldName = $this->quoteIdentifier($fieldName, true);
                $sql[] = 'ALTER TABLE ' . $name . ' RENAME COLUMN ' . $fieldName . ' TO ' . $this->quoteIdentifier($field['name'], true);
            }
        }

        $name = $this->quoteIdentifier($name, true);
        if (isset($changes['name'])) {
            $changeName = $this->quoteIdentifier($changes['name'], true);
            $sql[] = 'ALTER TABLE ' . $name . ' RENAME TO ' . $changeName;
        }

        return $sql;
    }
    
    /**
     * {@inheritdoc}
     * 
     * @return string
     * @override
     */
    public function getCreateSequenceSql($sequenceName, $start = 1, $allocationSize = 1)
    {
        return 'CREATE SEQUENCE ' . $this->quoteIdentifier($sequenceName)
                . ' INCREMENT BY ' . $allocationSize . ' START ' . $start;
    }
    
    /**
     * drop existing sequence
     *
     * @param string $sequenceName name of the sequence to be dropped
     * @override
     */
    public function getDropSequenceSql($sequenceName)
    {
        return 'DROP SEQUENCE ' . $this->quoteIdentifier($sequenceName);
    }
    
    /**
     * Gets the SQL used to create a table.
     *
     * @param unknown_type $name
     * @param array $fields
     * @param array $options
     * @return unknown
     */
    public function getCreateTableSql($name, array $fields, array $options = array())
    {
        $queryFields = $this->getColumnDeclarationListSql($fields);

        if (isset($options['primary']) && ! empty($options['primary'])) {
            $keyColumns = array_unique(array_values($options['primary']));
            $keyColumns = array_map(array($this, 'quoteIdentifier'), $keyColumns);
            $queryFields .= ', PRIMARY KEY(' . implode(', ', $keyColumns) . ')';
        }

        $query = 'CREATE TABLE ' . $this->quoteIdentifier($name) . ' (' . $queryFields . ')';

        $sql[] = $query;

        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach($options['indexes'] as $index => $definition) {
                $sql[] = $this->getCreateIndexSql($name, $index, $definition);
            }
        }

        if (isset($options['foreignKeys'])) {

            foreach ((array) $options['foreignKeys'] as $k => $definition) {
                if (is_array($definition)) {
                    $sql[] = $this->getCreateForeignKeySql($name, $definition);
                }
            }
        }

        return $sql;
    }
    
    /**
     * Postgres wants boolean values converted to the strings 'true'/'false'.
     *
     * @param array $item
     * @override
     */
    public function convertBooleans($item)
    {
        if (is_array($item)) {
            foreach ($item as $key => $value) {
                if (is_bool($value) || is_numeric($item)) {
                    $item[$key] = ($value) ? 'true' : 'false';
                }
            }
        } else {
           if (is_bool($item) || is_numeric($item)) {
               $item = ($item) ? 'true' : 'false';
           }
        }
        return $item;
    }
    
    /**
     * Enter description here...
     *
     * @param string $sequenceName
     * @override
     */
    public function getSequenceNextValSql($sequenceName)
    {
        return "SELECT NEXTVAL('" . $sequenceName . "')";
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $level
     * @override
     */
    public function getSetTransactionIsolationSql($level)
    {
        return 'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL '
                . $this->_getTransactionIsolationLevelSql($level);
    }

    /**
     * @override
     */
    public function getIntegerTypeDeclarationSql(array $field)
    {
        if ( ! empty($field['autoincrement'])) {
            return 'SERIAL';
        }
        return 'INT';
    }

    /**
     * @override
     */
    public function getBigIntTypeDeclarationSql(array $field)
    {
        if ( ! empty($field['autoincrement'])) {
            return 'BIGSERIAL';
        }
        return 'BIGINT';
    }

    /**
     * @override
     */
    public function getSmallIntTypeDeclarationSql(array $field)
    {
        return 'SMALLINT';
    }

    /**
     * @override
     */
    protected function _getCommonIntegerTypeDeclarationSql(array $columnDef)
    {
        return '';
    }

    /**
     * Gets the SQL snippet used to declare a VARCHAR column on the MySql platform.
     *
     * @params array $field
     * @override
     */
    public function getVarcharTypeDeclarationSql(array $field)
    {
        if ( ! isset($field['length'])) {
            if (array_key_exists('default', $field)) {
                $field['length'] = $this->getVarcharMaxLength();
            } else {
                $field['length'] = false;
            }
        }

        $length = ($field['length'] <= $this->getVarcharMaxLength()) ? $field['length'] : false;
        $fixed = (isset($field['fixed'])) ? $field['fixed'] : false;

        return $fixed ? ($length ? 'CHAR(' . $length . ')' : 'CHAR(255)')
                : ($length ? 'VARCHAR(' . $length . ')' : 'TEXT');
    }

    /**
     * Get the platform name for this instance
     *
     * @return string
     */
    public function getName()
    {
        return 'postgresql';
    }
}