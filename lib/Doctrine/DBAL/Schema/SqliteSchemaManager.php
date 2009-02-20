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

namespace Doctrine\DBAL\Schema;

/**
 * xxx
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision$
 * @since       2.0
 */
class SqliteSchemaManager extends AbstractSchemaManager
{    
    /**
     * lists all databases
     *
     * @return array
     */
    public function listDatabases()
    {
        
    }

    /**
     * lists all available database functions
     *
     * @return array
     */
    public function listFunctions()
    {

    }

    /**
     * lists all database triggers
     *
     * @param string|null $database
     * @return array
     */
    public function listTriggers($database = null)
    {
        return $this->listTableTriggers(null);
    }

    /**
     * lists all database sequences
     *
     * @param string|null $database
     * @return array
     */
    public function listSequences($database = null)
    {
        $query      = "SELECT name FROM sqlite_master WHERE type='table' AND sql NOT NULL ORDER BY name";
        $tableNames = $this->_conn->fetchColumn($query);

        $result = array();
        foreach ($tableNames as $tableName) {
            if ($sqn = $this->_conn->fixSequenceName($tableName, true)) {
                $result[] = $sqn;
            }
        }
        if ($this->_conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            $result = array_map(($this->_conn->getAttribute(Doctrine::ATTR_FIELD_CASE) == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }

    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableConstraints($table)
    {
        $table = $this->_conn->quote($table, 'text');

        $query = "SELECT sql FROM sqlite_master WHERE type='index' AND ";

        if ($this->_conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            $query .= 'LOWER(tbl_name) = ' . strtolower($table);
        } else {
            $query .= 'tbl_name = ' . $table;
        }
        $query  .= ' AND sql NOT NULL ORDER BY name';
        $indexes = $this->_conn->fetchColumn($query);

        $result = array();
        foreach ($indexes as $sql) {
            if (preg_match("/^create unique index ([^ ]+) on /i", $sql, $tmp)) {
                $index = $this->_conn->formatter->fixIndexName($tmp[1]);
                if ( ! empty($index)) {
                    $result[$index] = true;
                }
            }
        }

        if ($this->_conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            $result = array_change_key_case($result, $this->_conn->getAttribute(Doctrine::ATTR_FIELD_CASE));
        }
        return array_keys($result);
    }

    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableColumns($table)
    {
        $sql    = 'PRAGMA table_info(' . $table . ')';
        $result = $this->_conn->fetchAll($sql);

        $description = array();
        $columns     = array();
        foreach ($result as $key => $val) {
            $val = array_change_key_case($val, CASE_LOWER);
            $decl = $this->_conn->dataDict->getPortableDeclaration($val);

            $description = array(
                    'name'      => $val['name'],
                    'ntype'     => $val['type'],
                    'type'      => $decl['type'][0],
                    'alltypes'  => $decl['type'],
                    'notnull'   => (bool) $val['notnull'],
                    'default'   => $val['dflt_value'],
                    'primary'   => (bool) $val['pk'],
                    'length'    => null,
                    'scale'     => null,
                    'precision' => null,
                    'unsigned'  => null,
                    );
            $columns[$val['name']] = $description;
        }
        return $columns;
    }

    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableIndexes($table)
    {
        $sql  = 'PRAGMA index_list(' . $table . ')';
        return $this->_conn->fetchColumn($sql);
   }
    /**
     * lists tables
     *
     * @param string|null $database
     * @return array
     */
    public function listTables($database = null)
    {
        $sql = "SELECT name FROM sqlite_master WHERE type = 'table' "
             . "UNION ALL SELECT name FROM sqlite_temp_master "
             . "WHERE type = 'table' ORDER BY name";

        return $this->_conn->fetchColumn($sql);
    }

    /**
     * lists table triggers
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableTriggers($table)
    {
        $query = "SELECT name FROM sqlite_master WHERE type='trigger' AND sql NOT NULL";
        if (!is_null($table)) {
            if ($this->_conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
                if ($this->_conn->getAttribute(Doctrine::ATTR_FIELD_CASE) == CASE_LOWER) {
                    $query.= ' AND LOWER(tbl_name)='.$db->quote(strtolower($table), 'text');
                } else {
                    $query.= ' AND UPPER(tbl_name)='.$db->quote(strtoupper($table), 'text');
                }
            } else {
                $query.= ' AND tbl_name='.$db->quote($table, 'text');
            }
        }
        $result = $this->_conn->fetchColumn($query);

        if ($this->_conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            $result = array_map(($this->_conn->getAttribute(Doctrine::ATTR_FIELD_CASE) == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }

    /**
     * lists table views
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableViews($table)
    {
        $query = "SELECT name, sql FROM sqlite_master WHERE type='view' AND sql NOT NULL";
        $views = $db->fetchAll($query);

        $result = array();
        foreach ($views as $row) {
            if (preg_match("/^create view .* \bfrom\b\s+\b{$table}\b /i", $row['sql'])) {
                if ( ! empty($row['name'])) {
                    $result[$row['name']] = true;
                }
            }
        }
        return $result;
    }

    /**
     * lists database users
     *
     * @return array
     */
    public function listUsers()
    {

    }

    /**
     * lists database views
     *
     * @param string|null $database
     * @return array
     */
    public function listViews($database = null)
    {
        $query = "SELECT name FROM sqlite_master WHERE type='view' AND sql NOT NULL";

        return $this->_conn->fetchColumn($query);
    }
    
    /**
     * Drops an existing database
     *
     * @param string $databaseFile          Path of the database that should be dropped
     * @throws Doctrine_Export_Exception    if the database file does not exist
     * @throws Doctrine_Export_Exception    if something failed during the removal of the database file
     * @return void
     */
    public function dropDatabase($databaseFile)
    {
        if ( ! @file_exists($databaseFile)) {
            throw \Doctrine\Common\DoctrineException::updateMe('database does not exist');
        }

        $result = @unlink($databaseFile);

        if ( ! $result) {
            throw \Doctrine\Common\DoctrineException::updateMe('could not remove the database file');
        }
    }

    /**
     * createDatabase
     *
     * Create sqlite database file
     *
     * @param string $databaseFile  Path of the database that should be dropped
     * @return void
     */
    public function createDatabase($databaseFile)
    {
        return new PDO('sqlite:' . $databaseFile);
    }

    /**
     * Get the stucture of a field into an array
     *
     * @param string    $table         name of the table on which the index is to be created
     * @param string    $name         name of the index to be created
     * @param array     $definition        associative array that defines properties of the index to be created.
     *                                 Currently, only one property named FIELDS is supported. This property
     *                                 is also an associative with the names of the index fields as array
     *                                 indexes. Each entry of this array is set to another type of associative
     *                                 array that specifies properties of the index that are specific to
     *                                 each field.
     *
     *                                Currently, only the sorting property is supported. It should be used
     *                                 to define the sorting direction of the index. It may be set to either
     *                                 ascending or descending.
     *
     *                                Not all DBMS support index sorting direction configuration. The DBMS
     *                                 drivers of those that do not support it ignore this property. Use the
     *                                 function support() to determine whether the DBMS driver can manage indexes.

     *                                 Example
     *                                    array(
     *                                        'fields' => array(
     *                                            'user_name' => array(
     *                                                'sorting' => 'ascending'
     *                                            ),
     *                                            'last_login' => array()
     *                                        )
     *                                    )
     * @throws PDOException
     * @return void
     */
    public function createIndexSql($table, $name, array $definition)
    {
        $name  = $this->_conn->formatter->getIndexName($name);
        $name  = $this->_conn->quoteIdentifier($name);
        $query = 'CREATE INDEX ' . $name . ' ON ' . $table;
        $query .= ' (' . $this->getIndexFieldDeclarationList($definition['fields']) . ')';

        return $query;
    }

    /**
     * getIndexFieldDeclarationList
     * Obtain DBMS specific SQL code portion needed to set an index
     * declaration to be used in statements like CREATE TABLE.
     *
     * @return string
     */
    public function getIndexFieldDeclarationList(array $fields)
    {
        $declFields = array();

        foreach ($fields as $fieldName => $field) {
            $fieldString = $this->_conn->quoteIdentifier($fieldName);

            if (is_array($field)) {
                if (isset($field['sorting'])) {
                    $sort = strtoupper($field['sorting']);
                    switch ($sort) {
                        case 'ASC':
                        case 'DESC':
                            $fieldString .= ' ' . $sort;
                            break;
                        default:
                            throw \Doctrine\Common\DoctrineException::updateMe('Unknown index sorting option given.');
                    }
                }
            } else {
                $fieldString = $this->_conn->quoteIdentifier($field);
            }
            $declFields[] = $fieldString;
        }
        return implode(', ', $declFields);
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
     * create sequence
     *
     * @param string    $seqName        name of the sequence to be created
     * @param string    $start          start value of the sequence; default is 1
     * @param array     $options  An associative array of table options:
     *                          array(
     *                              'comment' => 'Foo',
     *                              'charset' => 'utf8',
     *                              'collate' => 'utf8_unicode_ci',
     *                          );
     * @return boolean
     */
    public function createSequence($seqName, $start = 1, array $options = array())
    {
        $sequenceName   = $this->_conn->quoteIdentifier($this->_conn->getSequenceName($seqName), true);
        $seqcolName     = $this->_conn->quoteIdentifier($this->_conn->getAttribute(Doctrine::ATTR_SEQCOL_NAME), true);
        $query          = 'CREATE TABLE ' . $sequenceName . ' (' . $seqcolName . ' INTEGER PRIMARY KEY DEFAULT 0 NOT NULL)';

        $this->_conn->exec($query);

        if ($start == 1) {
            return true;
        }

        try {
            $this->_conn->exec('INSERT INTO ' . $sequenceName . ' (' . $seqcolName . ') VALUES (' . ($start-1) . ')');
            return true;
        } catch(Doctrine\DBAL\ConnectionException $e) {
            // Handle error

            try {
                $result = $db->exec('DROP TABLE ' . $sequenceName);
            } catch(Doctrine\DBAL\ConnectionException $e) {
                throw \Doctrine\Common\DoctrineException::updateMe('could not drop inconsistent sequence table');
            }
        }
        throw \Doctrine\Common\DoctrineException::updateMe('could not create sequence table');
    }

    /**
     * drop existing sequence
     *
     * @param string $sequenceName      name of the sequence to be dropped
     * @return string
     */
    public function dropSequenceSql($sequenceName)
    {
        $sequenceName = $this->_conn->quoteIdentifier($this->_conn->getSequenceName($sequenceName), true);

        return 'DROP TABLE ' . $sequenceName;
    }

    public function alterTableSql($name, array $changes, $check = false)
    {
        if ( ! $name) {
            throw \Doctrine\Common\DoctrineException::updateMe('no valid table name specified');
        }
        foreach ($changes as $changeName => $change) {
            switch ($changeName) {
                case 'add':
                case 'change':
                case 'rename':
                case 'name':
                    break;
                default:
                    throw \Doctrine\Common\DoctrineException::updateMe('change type "' . $changeName . '" not yet supported');
            }
        }

        if ($check) {
            return true;
        }

        $query = '';
        if ( ! empty($changes['name'])) {
            $change_name = $this->_conn->quoteIdentifier($changes['name']);
            $query .= 'RENAME TO ' . $change_name;
        }

        if ( ! empty($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $fieldName => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $query.= 'ADD ' . $this->getDeclaration($fieldName, $field);
            }
        }

        $rename = array();
        if ( ! empty($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $fieldName => $field) {
                $rename[$field['name']] = $fieldName;
            }
        }

        if ( ! empty($changes['change']) && is_array($changes['change'])) {
            foreach ($changes['change'] as $fieldName => $field) {
                if ($query) {
                    $query.= ', ';
                }
                if (isset($rename[$fieldName])) {
                    $oldFieldName = $rename[$fieldName];
                    unset($rename[$fieldName]);
                } else {
                    $oldFieldName = $fieldName;
                }
                $oldFieldName = $this->_conn->quoteIdentifier($oldFieldName, true);
                $query .= 'CHANGE ' . $oldFieldName . ' '
                        . $this->getDeclaration($fieldName, $field['definition']);
            }
        }

        if ( ! empty($rename) && is_array($rename)) {
            foreach ($rename as $renameName => $renamedField) {
                if ($query) {
                    $query.= ', ';
                }
                $field = $changes['rename'][$renamedField];
                $renamedField = $this->_conn->quoteIdentifier($renamedField, true);
                $query .= 'CHANGE ' . $renamedField . ' '
                        . $this->getDeclaration($field['name'], $field['definition']);
            }
        }

        if ( ! $query) {
            return false;
        }

        $name = $this->_conn->quoteIdentifier($name, true);

        return 'ALTER TABLE ' . $name . ' ' . $query;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param array  $field   associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       unsigned
     *                        Boolean flag that indicates whether the field
     *                        should be declared as unsigned integer if
     *                        possible.
     *
     *                       default
     *                        Integer value to be used as default for this
     *                        field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access protected
     */
    public function getIntegerDeclaration($name, array $field)
    {
        $default = $autoinc = '';
        $type    = $this->_conn->dataDict->getNativeDeclaration($field);

        $autoincrement = isset($field['autoincrement']) && $field['autoincrement'];

        if ($autoincrement) {
            $autoinc = ' PRIMARY KEY AUTOINCREMENT';
            $type    = 'INTEGER';
        } elseif (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull']) ? null : 0;
            }
            $default = ' DEFAULT ' . $this->_conn->quote($field['default'], $field['type']);
        } elseif (empty($field['notnull'])) {
            $default = ' DEFAULT NULL';
        }

        $notnull  = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';

        // sqlite does not support unsigned attribute for autoinremented fields
        $unsigned = (isset($field['unsigned']) && $field['unsigned'] && !$autoincrement) ? ' UNSIGNED' : '';

        $name = $this->_conn->quoteIdentifier($name, true);
        return $name . ' ' . $type . $unsigned . $default . $notnull . $autoinc;
    }
}