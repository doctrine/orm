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

use Doctrine\DBAL\Schema\TableDiff;

/**
 * PostgreSqlPlatform.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 * @author Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @author Benjamin Eberlei <kontakt@beberlei.de>
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
        if ($len === null) {
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
                throw DoctrineException::operatorNotSupported($operator);
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
    /*public function parseBoolean($value)
    {
        return $value;
    }*/
    
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
     * Whether the platform supports database schemas.
     * 
     * @return boolean
     */
    public function supportsSchemas()
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

    public function getListDatabasesSql()
    {
        return 'SELECT datname FROM pg_database';
    }

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
                    WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema')";
    }

    public function getListSequencesSql($database)
    {
        return "SELECT
                    relname
                FROM
                   pg_class
                WHERE relkind = 'S' AND relnamespace IN
                    (SELECT oid FROM pg_namespace
                        WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema')";
    }

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

    public function getListViewsSql()
    {
        return 'SELECT viewname, definition FROM pg_views';
    }

    public function getListTriggersSql($table = null)
    {
        $sql = 'SELECT trg.tgname AS trigger_name
                    FROM pg_trigger trg,
                         pg_class tbl
                   WHERE trg.tgrelid = tbl.oid';

        if ( ! is_null($table)) {
            $sql .= " AND tbl.relname = " . $table;
        }

        return $sql;
    }

    public function getListUsersSql()
    {
        return 'SELECT usename, passwd FROM pg_user';
    }

    public function getListTableForeignKeysSql($table, $database = null)
    {
        return "SELECT r.conname, pg_catalog.pg_get_constraintdef(r.oid, true) as condef
                  FROM pg_catalog.pg_constraint r
                  WHERE r.conrelid =
                  (
                      SELECT c.oid
                      FROM pg_catalog.pg_class c
                      LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                      WHERE c.relname = '" . $table . "' AND pg_catalog.pg_table_is_visible(c.oid)
                  )
                  AND r.contype = 'f'";
    }

    public function getCreateViewSql($name, $sql)
    {
        return 'CREATE VIEW ' . $name . ' AS ' . $sql;
    }

    public function getDropViewSql($name)
    {
        return 'DROP VIEW '. $name;
    }

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
     * @license New BSD License
     * @link http://ezcomponents.org/docs/api/trunk/DatabaseSchema/ezcDbSchemaPgsqlReader.html
     * @param  string $table
     * @return string
     */
    public function getListTableIndexesSql($table)
    {
        return "SELECT relname, pg_index.indisunique, pg_index.indisprimary,
                       pg_index.indkey, pg_index.indrelid
                 FROM pg_class, pg_index
                 WHERE oid IN (
                    SELECT indexrelid
                    FROM pg_index, pg_class
                    WHERE pg_class.relname='$table' AND pg_class.oid=pg_index.indrelid
                 ) AND pg_index.indexrelid = oid";
    }

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
        return 'CREATE DATABASE ' . $name;
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
        return 'DROP DATABASE ' . $name;
    }
    
    /**
     * getAdvancedForeignKeyOptions
     * Return the FOREIGN KEY query section dealing with non-standard options
     * as MATCH, INITIALLY DEFERRED, ON UPDATE, ...
     *
     * @param \Doctrine\DBAL\Schema\ForeignKeyConstraint $foreignKey         foreign key definition
     * @return string
     * @override
     */
    public function getAdvancedForeignKeyOptionsSql(\Doctrine\DBAL\Schema\ForeignKeyConstraint $foreignKey)
    {
        $query = '';
        if ($foreignKey->hasOption('match')) {
            $query .= ' MATCH ' . $foreignKey->getOption('match');
        }
        $query .= parent::getAdvancedForeignKeyOptionsSql($foreignKey);
        if ($foreignKey->hasOption('deferrable') && $foreignKey->getOption('deferrable') !== false) {
            $query .= ' DEFERRABLE';
        } else {
            $query .= ' NOT DEFERRABLE';
        }
        if ($foreignKey->hasOption('feferred') && $foreignKey->getOption('feferred') !== false) {
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
    public function getAlterTableSql(TableDiff $diff)
    {
        $sql = array();

        foreach ($diff->addedColumns as $column) {
            $query = 'ADD ' . $this->getColumnDeclarationSql($column->getName(), $column->toArray());
            $sql[] = 'ALTER TABLE ' . $diff->name . ' ' . $query;
        }

        foreach ($diff->removedColumns as $column) {
            $query = 'DROP ' . $column->getName();
            $sql[] = 'ALTER TABLE ' . $diff->name . ' ' . $query;
        }

        foreach ($diff->changedColumns AS $columnDiff) {
            $oldColumnName = $columnDiff->oldColumnName;
            $column = $columnDiff->column;
            
            if ($columnDiff->hasChanged('type')) {
                $type = $column->getType();

                // here was a server version check before, but DBAL API does not support this anymore.
                $query = 'ALTER ' . $oldColumnName . ' TYPE ' . $type->getSqlDeclaration($column->toArray(), $this);
                $sql[] = 'ALTER TABLE ' . $diff->name . ' ' . $query;
            }
            if ($columnDiff->hasChanged('default')) {
                $query = 'ALTER ' . $oldColumnName . ' SET DEFAULT ' . $column->getDefault();
                $sql[] = 'ALTER TABLE ' . $diff->name . ' ' . $query;
            }
            if ($columnDiff->hasChanged('notnull')) {
                $query = 'ALTER ' . $oldColumnName . ' ' . ($column->getNotNull() ? 'SET' : 'DROP') . ' NOT NULL';
                $sql[] = 'ALTER TABLE ' . $diff->name . ' ' . $query;
            }
        }

        foreach ($diff->renamedColumns as $oldColumnName => $column) {
            $sql[] = 'ALTER TABLE ' . $diff->name . ' RENAME COLUMN ' . $oldColumnName . ' TO ' . $column->getName();
        }

        if ($diff->newName !== false) {
            $sql[] = 'ALTER TABLE ' . $diff->name . ' RENAME TO ' . $diff->newName;
        }

        return $sql;
    }
    
    /**
     * Gets the SQL to create a sequence on this platform.
     *
     * @param \Doctrine\DBAL\Schema\Sequence $sequence
     * @throws DoctrineException
     */
    public function getCreateSequenceSql(\Doctrine\DBAL\Schema\Sequence $sequence)
    {
        return 'CREATE SEQUENCE ' . $sequence->getName() .
               ' INCREMENT BY ' . $sequence->getAllocationSize() .
               ' MINVALUE ' . $sequence->getInitialValue() .
               ' START ' . $sequence->getInitialValue();
    }
    
    /**
     * Drop existing sequence
     * @param  \Doctrine\DBAL\Schema\Sequence $sequence
     * @return string
     */
    public function getDropSequenceSql($sequence)
    {
        if ($sequence instanceof \Doctrine\DBAL\Schema\Sequence) {
            $sequence = $sequence->getName();
        }
        return 'DROP SEQUENCE ' . $sequence;
    }
    
    /**
     * Gets the SQL used to create a table.
     *
     * @param unknown_type $tableName
     * @param array $columns
     * @param array $options
     * @return unknown
     */
    protected function _getCreateTableSql($tableName, array $columns, array $options = array())
    {
        $queryFields = $this->getColumnDeclarationListSql($columns);

        if (isset($options['primary']) && ! empty($options['primary'])) {
            $keyColumns = array_unique(array_values($options['primary']));
            $queryFields .= ', PRIMARY KEY(' . implode(', ', $keyColumns) . ')';
        }

        $query = 'CREATE TABLE ' . $tableName . ' (' . $queryFields . ')';

        $sql[] = $query;

        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach ($options['indexes'] AS $index) {
                $sql[] = $this->getCreateIndexSql($index, $tableName);
            }
        }

        if (isset($options['foreignKeys'])) {
            foreach ((array) $options['foreignKeys'] as $definition) {
                $sql[] = $this->getCreateForeignKeySql($definition, $tableName);
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

    public function getSequenceNextValSql($sequenceName)
    {
        return "SELECT NEXTVAL('" . $sequenceName . "')";
    }

    public function getSetTransactionIsolationSql($level)
    {
        return 'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL '
                . $this->_getTransactionIsolationLevelSql($level);
    }
    
    /**
     * @override
     */
    public function getBooleanTypeDeclarationSql(array $field)
    {
        return 'BOOLEAN';
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
    public function getDateTimeTypeDeclarationSql(array $fieldDeclaration)
    {
        return 'TIMESTAMP(0) WITH TIME ZONE';
    }
    
    /**
     * @override
     */
    public function getDateTypeDeclarationSql(array $fieldDeclaration)
    {
        return 'DATE';
    }

    /**
     * @override
     */
    public function getTimeTypeDeclarationSql(array $fieldDeclaration)
    {
        return 'TIME';
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
    
    /** @override */
    public function getClobTypeDeclarationSql(array $field)
    {
        return 'TEXT';
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
    
    /**
     * Gets the character casing of a column in an SQL result set.
     * 
     * PostgreSQL returns all column names in SQL result sets in lowercase.
     * 
     * @param string $column The column name for which to get the correct character casing.
     * @return string The column name in the character casing used in SQL result sets.
     */
    public function getSqlResultCasing($column)
    {
        return strtolower($column);
    }
    
    public function getDateTimeFormatString()
    {
        return 'Y-m-d H:i:sO';
    }

    /**
     * Get the insert sql for an empty insert statement
     *
     * @param string $tableName 
     * @param string $identifierColumnName 
     * @return string $sql
     */
    public function getEmptyIdentityInsertSql($quotedTableName, $quotedIdentifierColumnName)
    {
        return 'INSERT INTO ' . $quotedTableName . ' (' . $quotedIdentifierColumnName . ') VALUES (DEFAULT)';
    }
}
