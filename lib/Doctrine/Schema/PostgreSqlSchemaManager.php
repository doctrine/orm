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
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine::DBAL::Schema;

/**
 * xxx
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision$
 * @since       2.0
 */
class Doctrine_Schema_PostgreSqlSchemaManager extends Doctrine_Schema_SchemaManager
{
    protected $sql = array(
                        'listDatabases' => 'SELECT datname FROM pg_database',
                        'listFunctions' => "SELECT
                                                proname
                                            FROM
                                                pg_proc pr,
                                                pg_type tp
                                            WHERE
                                                tp.oid = pr.prorettype
                                                AND pr.proisagg = FALSE
                                                AND tp.typname <> 'trigger'
                                                AND pr.pronamespace IN
                                                    (SELECT oid FROM pg_namespace
                                                     WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema'",
                        'listSequences' => "SELECT
                                                relname
                                            FROM
                                                pg_class
                                            WHERE relkind = 'S' AND relnamespace IN
                                                (SELECT oid FROM pg_namespace
                                                 WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema')",
                        'listTables'    => "SELECT
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
                                                AND c.relname !~ '^pg_'",
                        'listViews'     => 'SELECT viewname FROM pg_views',
                        'listUsers'     => 'SELECT usename FROM pg_user',
                        'listTableConstraints' => "SELECT
                                                        relname
                                                   FROM
                                                        pg_class
                                                   WHERE oid IN (
                                                        SELECT indexrelid
                                                        FROM pg_index, pg_class
                                                        WHERE pg_class.relname = %s
                                                            AND pg_class.oid = pg_index.indrelid
                                                            AND (indisunique = 't' OR indisprimary = 't')
                                                        )",
                        'listTableIndexes'     => "SELECT
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
                                                        )",
                        'listTableColumns'     => "SELECT
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
                                                  WHERE c.relname = %s
                                                        AND a.attnum > 0
                                                        AND a.attrelid = c.oid
                                                        AND a.atttypid = t.oid
                                                  ORDER BY a.attnum",
                        );
    
    
    
    public function __construct(Doctrine_Connection_Pgsql $conn)
    {
        $this->_conn = $conn;
    }
    
    /**
     * create a new database
     *
     * @param string $name name of the database that should be created
     * @throws PDOException
     * @return void
     */
    public function createDatabaseSql($name)
    {
        $query  = 'CREATE DATABASE ' . $this->_conn->quoteIdentifier($name);

        return $query;
    }

    /**
     * drop an existing database
     *
     * @param string $name name of the database that should be dropped
     * @throws PDOException
     * @access public
     */
    public function dropDatabaseSql($name)
    {
        $query  = 'DROP DATABASE ' . $this->_conn->quoteIdentifier($name);

        return $query;
    }

    /**
     * getAdvancedForeignKeyOptions
     * Return the FOREIGN KEY query section dealing with non-standard options
     * as MATCH, INITIALLY DEFERRED, ON UPDATE, ...
     *
     * @param array $definition         foreign key definition
     * @return string
     * @access protected
     */
    public function getAdvancedForeignKeyOptions(array $definition)
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
     */
    public function alterTableSql($name, array $changes, $check = false)
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
                    throw new Doctrine_Export_Exception('change type "' . $changeName . '\" not yet supported');
            }
        }

        if ($check) {
            return true;
        }

        $sql = array();

        if (isset($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $fieldName => $field) {
                $query = 'ADD ' . $this->getDeclaration($fieldName, $field);
                $sql[] = 'ALTER TABLE ' . $name . ' ' . $query;
            }
        }

        if (isset($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $fieldName => $field) {
                $fieldName = $this->_conn->quoteIdentifier($fieldName, true);
                $query = 'DROP ' . $fieldName;
                $sql[] = 'ALTER TABLE ' . $name . ' ' . $query;
            }
        }

        if (isset($changes['change']) && is_array($changes['change'])) {
            foreach ($changes['change'] as $fieldName => $field) {
                $fieldName = $this->_conn->quoteIdentifier($fieldName, true);
                if (isset($field['type'])) {
                    $serverInfo = $this->_conn->getServerVersion();

                    if (is_array($serverInfo) && $serverInfo['major'] < 8) {
                        throw new Doctrine_Export_Exception('changing column type for "'.$field['type'].'\" requires PostgreSQL 8.0 or above');
                    }
                    $query = 'ALTER ' . $fieldName . ' TYPE ' . $this->_conn->datatype->getTypeDeclaration($field['definition']);
                    $sql[] = 'ALTER TABLE ' . $name . ' ' . $query;
                }
                if (array_key_exists('default', $field)) {
                    $query = 'ALTER ' . $fieldName . ' SET DEFAULT ' . $this->_conn->quote($field['definition']['default'], $field['definition']['type']);
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
                $fieldName = $this->_conn->quoteIdentifier($fieldName, true);
                $sql[] = 'ALTER TABLE ' . $name . ' RENAME COLUMN ' . $fieldName . ' TO ' . $this->_conn->quoteIdentifier($field['name'], true);
            }
        }

        $name = $this->_conn->quoteIdentifier($name, true);
        if (isset($changes['name'])) {
            $changeName = $this->_conn->quoteIdentifier($changes['name'], true);
            $sql[] = 'ALTER TABLE ' . $name . ' RENAME TO ' . $changeName;
        }

        return $sql;
    }

    /**
     * alter an existing table
     *
     * @param string $name         name of the table that is intended to be changed.
     * @param array $changes     associative array that contains the details of each type
     *                             of change that is intended to be performed. The types of
     *                             changes that are currently supported are defined as follows:
     *
     *                             name
     *
     *                                New name for the table.
     *
     *                            add
     *
     *                                Associative array with the names of fields to be added as
     *                                 indexes of the array. The value of each entry of the array
     *                                 should be set to another associative array with the properties
     *                                 of the fields to be added. The properties of the fields should
     *                                 be the same as defined by the Metabase parser.
     *
     *
     *                            remove
     *
     *                                Associative array with the names of fields to be removed as indexes
     *                                 of the array. Currently the values assigned to each entry are ignored.
     *                                 An empty array should be used for future compatibility.
     *
     *                            rename
     *
     *                                Associative array with the names of fields to be renamed as indexes
     *                                 of the array. The value of each entry of the array should be set to
     *                                 another associative array with the entry named name with the new
     *                                 field name and the entry named Declaration that is expected to contain
     *                                 the portion of the field declaration already in DBMS specific SQL code
     *                                 as it is used in the CREATE TABLE statement.
     *
     *                            change
     *
     *                                Associative array with the names of the fields to be changed as indexes
     *                                 of the array. Keep in mind that if it is intended to change either the
     *                                 name of a field and any other properties, the change array entries
     *                                 should have the new names of the fields as array indexes.
     *
     *                                The value of each entry of the array should be set to another associative
     *                                 array with the properties of the fields to that are meant to be changed as
     *                                 array entries. These entries should be assigned to the new values of the
     *                                 respective properties. The properties of the fields should be the same
     *                                 as defined by the Metabase parser.
     *
     *                            Example
     *                                array(
     *                                    'name' => 'userlist',
     *                                    'add' => array(
     *                                        'quota' => array(
     *                                            'type' => 'integer',
     *                                            'unsigned' => 1
     *                                        )
     *                                    ),
     *                                    'remove' => array(
     *                                        'file_limit' => array(),
     *                                        'time_limit' => array()
     *                                    ),
     *                                    'change' => array(
     *                                        'name' => array(
     *                                            'length' => '20',
     *                                            'definition' => array(
     *                                                'type' => 'text',
     *                                                'length' => 20,
     *                                            ),
     *                                        )
     *                                    ),
     *                                    'rename' => array(
     *                                        'sex' => array(
     *                                            'name' => 'gender',
     *                                            'definition' => array(
     *                                                'type' => 'text',
     *                                                'length' => 1,
     *                                                'default' => 'M',
     *                                            ),
     *                                        )
     *                                    )
     *                                )
     *
     * @param boolean $check     indicates whether the function should just check if the DBMS driver
     *                             can perform the requested table alterations if the value is true or
     *                             actually perform them otherwise.
     * @throws Doctrine_Connection_Exception
     * @return boolean
     */
    public function alterTable($name, array $changes, $check = false)
    {
        $sql = $this->alterTableSql($name, $changes, $check);
        foreach ($sql as $query) {
            $this->_conn->exec($query);
        }
        return true;
    }

    /**
     * return RDBMS specific create sequence statement
     *
     * @throws Doctrine_Connection_Exception     if something fails at database level
     * @param string    $seqName        name of the sequence to be created
     * @param string    $start          start value of the sequence; default is 1
     * @param array     $options  An associative array of table options:
     *                          array(
     *                              'comment' => 'Foo',
     *                              'charset' => 'utf8',
     *                              'collate' => 'utf8_unicode_ci',
     *                          );
     * @return string
     */
    public function createSequenceSql($sequenceName, $start = 1, array $options = array())
    {
        $sequenceName = $this->_conn->quoteIdentifier($this->_conn->formatter->getSequenceName($sequenceName), true);
        return $this->_conn->exec('CREATE SEQUENCE ' . $sequenceName . ' INCREMENT 1' .
                    ($start < 1 ? ' MINVALUE ' . $start : '') . ' START ' . $start);
    }

    /**
     * drop existing sequence
     *
     * @param string $sequenceName name of the sequence to be dropped
     */
    public function dropSequenceSql($sequenceName)
    {
        $sequenceName = $this->_conn->quoteIdentifier($this->_conn->formatter->getSequenceName($sequenceName), true);
        return 'DROP SEQUENCE ' . $sequenceName;
    }

    /**
     * Creates a table.
     *
     * @param unknown_type $name
     * @param array $fields
     * @param array $options
     * @return unknown
     */
    public function createTableSql($name, array $fields, array $options = array())
    {
        if ( ! $name) {
            throw new Doctrine_Export_Exception('no valid table name specified');
        }

        if (empty($fields)) {
            throw new Doctrine_Export_Exception('no fields specified for table ' . $name);
        }

        $queryFields = $this->getFieldDeclarationList($fields);


        if (isset($options['primary']) && ! empty($options['primary'])) {
            $keyColumns = array_values($options['primary']);
            $keyColumns = array_map(array($this->_conn, 'quoteIdentifier'), $keyColumns);
            $queryFields .= ', PRIMARY KEY(' . implode(', ', $keyColumns) . ')';
        }

        $query = 'CREATE TABLE ' . $this->_conn->quoteIdentifier($name, true) . ' (' . $queryFields . ')';

        $sql[] = $query;

        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach($options['indexes'] as $index => $definition) {
                $sql[] = $this->createIndexSql($name, $index, $definition);
            }
        }

        if (isset($options['foreignKeys'])) {

            foreach ((array) $options['foreignKeys'] as $k => $definition) {
                if (is_array($definition)) {
                    $sql[] = $this->createForeignKeySql($name, $definition);
                }
            }
        }

        return $sql;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       unsigned
     *           Boolean flag that indicates whether the field should be
     *           declared as unsigned integer if possible.
     *
     *       default
     *           Integer value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     */
    public function getIntegerDeclaration($name, $field)
    {
        /**
        if ( ! empty($field['unsigned'])) {
            $this->_conn->warnings[] = "unsigned integer field \"$name\" is being declared as signed integer";
        }
        */

        if ( ! empty($field['autoincrement'])) {
            $name = $this->_conn->quoteIdentifier($name, true);
            return $name . ' ' . $this->_conn->dataDict->getNativeDeclaration($field);
        }

        $default = '';
        if (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull']) ? null : 0;
            }
            $default = ' DEFAULT '.$this->_conn->quote($field['default'], $field['type']);
        } elseif (empty($field['notnull'])) {
            $default = ' DEFAULT NULL';
        }

        $notnull = empty($field['notnull']) ? '' : ' NOT NULL';
        $name = $this->_conn->quoteIdentifier($name, true);
        return $name . ' ' . $this->_conn->dataDict->getNativeDeclaration($field) . $default . $notnull;
    }
    
    /**
     * lists all database triggers
     *
     * @param string|null $database
     * @return array
     */
    public function listTriggers($database = null)
    {

    }

    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableConstraints($table)
    {
        $table = $this->conn->quote($table);
        $query = sprintf($this->sql['listTableConstraints'], $table);

        return $this->conn->fetchColumn($query);
    }

    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableColumns($table)
    {
        $table = $this->conn->quote($table);
        $query = sprintf($this->sql['listTableColumns'], $table);
        $result = $this->conn->fetchAssoc($query);

        $columns     = array();
        foreach ($result as $key => $val) {
            $val = array_change_key_case($val, CASE_LOWER);

            if (strtolower($val['type']) === 'varchar') {
                // get length from varchar definition
                $length = preg_replace('~.*\(([0-9]*)\).*~', '$1', $val['complete_type']);
                $val['length'] = $length;
            }
            
            $decl = $this->_conn->dataDict->getPortableDeclaration($val);

            $description = array(
                'name'      => $val['field'],
                'ntype'     => $val['type'],
                'type'      => $decl['type'][0],
                'alltypes'  => $decl['type'],
                'length'    => $decl['length'],
                'fixed'     => $decl['fixed'],
                'unsigned'  => $decl['unsigned'],
                'notnull'   => ($val['isnotnull'] == true),
                'default'   => $val['default'],
                'primary'   => ($val['pri'] == 't'),
            );
            
            $matches = array(); 

            if (preg_match("/^nextval\('(.*)'(::.*)?\)$/", $description['default'], $matches)) { 
     
                $description['sequence'] = $this->_conn->formatter->fixSequenceName($matches[1]); 
                $description['default'] = null; 
            } 
            
            $columns[$val['field']] = $description;
        }
        
        return $columns;
    }

    /**
     * list all indexes in a table
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableIndexes($table)
    {
        $table = $this->_conn->quote($table);
        $query = sprintf($this->sql['listTableIndexes'], $table);

        return $this->_conn->fetchColumn($query);
    }

    /**
     * lists tables
     *
     * @param string|null $database
     * @return array
     */
    public function listTables($database = null)
    {
        return $this->_conn->fetchColumn($this->sql['listTables']);
    }

    /**
     * lists table triggers
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableTriggers($table)
    {
        $query = 'SELECT trg.tgname AS trigger_name
                    FROM pg_trigger trg,
                         pg_class tbl
                   WHERE trg.tgrelid = tbl.oid';
        if ($table !== null) {
            $table = $this->_conn->quote(strtoupper($table), 'string');
            $query .= " AND tbl.relname = $table";
        }
        return $this->_conn->fetchColumn($query);
    }

    /**
     * list the views in the database that reference a given table
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableViews($table)
    {
        return $this->_conn->fetchColumn($query);
    }
}

?>