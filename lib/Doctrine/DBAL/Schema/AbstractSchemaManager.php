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

use \Doctrine\DBAL\Types;
use \Doctrine\Common\DoctrineException;

/**
 * Base class for schema managers. Schema managers are used to inspect and/or
 * modify the database schema/structure.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @author      Roman Borschel <roman@code-factory.org>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @version     $Revision$
 * @since       2.0
 */
abstract class AbstractSchemaManager
{
    /**
     * Holds instance of the Doctrine connection for this schema manager
     *
     * @var object \Doctrine\DBAL\Connection
     */
    protected $_conn;

    /**
     * Holds instance of the database platform used for this schema manager
     *
     * @var string
     */
    protected $_platform;

    /**
     * Constructor. Accepts the Connection instance to manage the schema for
     *
     * @param \Doctrine\DBAL\Connection $conn
     */
    public function __construct(\Doctrine\DBAL\Connection $conn)
    {
        $this->_conn = $conn;
        $this->_platform = $this->_conn->getDatabasePlatform();
    }

    /**
     * List the available databases for this connection
     *
     * @return array $databases
     */
    public function listDatabases()
    {
        $sql = $this->_platform->getListDatabasesSql();

        $databases = $this->_conn->fetchAll($sql);

        return $this->_getPortableDatabasesList($databases);
    }

    /**
     * List the available functions for this connection
     *
     * @return array $functions
     */
    public function listFunctions()
    {
        $sql = $this->_platform->getListFunctionsSql();

        $functions = $this->_conn->fetchAll($sql);

        return $this->_getPortableFunctionsList($functions);
    }

    /**
     * List the available triggers for this connection
     *
     * @return array $triggers
     */
    public function listTriggers()
    {
        $sql = $this->_platform->getListTriggersSql();

        $triggers = $this->_conn->fetchAll($sql);

        return $this->_getPortableTriggersList($triggers);
    }

    /**
     * List the available sequences for this connection
     *
     * @return array $sequences
     */
    public function listSequences($database = null)
    {
        if (is_null($database)) {
            $database = $this->_conn->getDatabase();
        }
        $sql = $this->_platform->getListSequencesSql($database);

        $sequences = $this->_conn->fetchAll($sql);

        return $this->_getPortableSequencesList($sequences);
    }

    /**
     * List the constraints for a given table
     *
     * @param string $table The name of the table
     * @return array $tableConstraints
     */
    public function listTableConstraints($table)
    {
        $sql = $this->_platform->getListTableConstraintsSql($table);

        $tableConstraints = $this->_conn->fetchAll($sql);

        return $this->_getPortableTableConstraintsList($tableConstraints);
    }

    /**
     * List the columns for a given table
     *
     * @param string $table The name of the table
     * @return array $tableColumns
     */
    public function listTableColumns($table)
    {
        $sql = $this->_platform->getListTableColumnsSql($table);

        $tableColumns = $this->_conn->fetchAll($sql);

        return $this->_getPortableTableColumnList($tableColumns);
    }

    /**
     * List the indexes for a given table
     *
     * @param string $table The name of the table
     * @return array $tableIndexes
     */
    public function listTableIndexes($table)
    {
        $sql = $this->_platform->getListTableIndexesSql($table);

        $tableIndexes = $this->_conn->fetchAll($sql);

        return $this->_getPortableTableIndexesList($tableIndexes);
    }

    /**
     * List the tables for this connection
     *
     * @return array $tables
     */
    public function listTables()
    {
        $sql = $this->_platform->getListTablesSql();

        $tables = $this->_conn->fetchAll($sql);

        return $this->_getPortableTablesList($tables);
    }

    /**
     * List the users this connection has
     *
     * @return array $users
     */
    public function listUsers()
    {
        $sql = $this->_platform->getListUsersSql();

        $users = $this->_conn->fetchAll($sql);

        return $this->_getPortableUsersList($users);
    }

    /**
     * List the views this connection has
     *
     * @return array $views
     */
    public function listViews()
    {
        $sql = $this->_platform->getListViewsSql();

        $views = $this->_conn->fetchAll($sql);

        return $this->_getPortableViewsList($views);
    }

    /**
     * List the foreign keys for the given table
     *
     * @param string $table  The name of the table
     * @return array $tableForeignKeys
     */
    public function listTableForeignKeys($table, $database = null)
    {
        if (is_null($database)) {
            $database = $this->_conn->getDatabase();
        }
        $sql = $this->_platform->getListTableForeignKeysSql($table, $database);
        $tableForeignKeys = $this->_conn->fetchAll($sql);

        return $this->_getPortableTableForeignKeysList($tableForeignKeys);
    }

    /**
     * Drops a database.
     * 
     * NOTE: You can not drop the database this SchemaManager is currently connected to.
     *
     * @param  string $database The name of the database to drop
     */
    public function dropDatabase($database)
    {
        $this->_conn->exec($this->_platform->getDropDatabaseSql($database));
    }

    /**
     * Drop the given table
     *
     * @param string $table The name of the table to drop
     * @return boolean $result
     */
    public function dropTable($table)
    {
        $sql = $this->_platform->getDropTableSql($table);

        return $this->_executeSql($sql, 'execute');
    }

    /**
     * Drop the index from the given table
     *
     * @param string $table The name of the table
     * @param string $name  The name of the index
     * @return boolean $result
     */
    public function dropIndex($table, $name)
    {
        //FIXME: Something is wrong here. The signature of getDropIndexSql is:
        // public function getDropIndexSql($index, $name)
        // $table == $index ???
        $sql = $this->_platform->getDropIndexSql($table, $name);

        return $this->_executeSql($sql, 'exec');
    }

    /**
     * Drop the constraint from the given table
     *
     * @param string $table   The name of the table
     * @param string $name    The name of the constraint
     * @param string $primary Whether or not it is a primary constraint
     * @return boolean $result
     */
    public function dropConstraint($table, $name, $primary = false)
    {
        $sql = $this->_platform->getDropConstraintSql($table, $name, $primary);

        return $this->_executeSql($sql, 'exec');
    }

    /**
     * Drop the foreign key from the given table
     *
     * @param string $table The name of the table
     * @param string $name  The name of the foreign key
     * @return boolean $result
     */
    public function dropForeignKey($table, $name)
    {
        $sql = $this->_platform->getDropForeignKeySql($table, $name);

        return $this->_executeSql($sql, 'exec');
    }

    /**
     * Drop the given sequence
     *
     * @param string $name The name of the sequence
     * @return boolean $result
     */
    public function dropSequence($name)
    {
        $sql = $this->_platform->getDropSequenceSql($name);

        return $this->_executeSql($sql, 'exec');
    }

    /**
     * Create the given database on the connection
     *
     * @param  string $database The name of the database to create
     * @return boolean $result
     */
    public function createDatabase($database = null)
    {
        if (is_null($database)) {
            $database = $this->_conn->getDatabase();
        }
        $sql = $this->_platform->getCreateDatabaseSql($database);

        return $this->_executeSql($sql, 'exec');
    }

    /**
     * Create a new database table
     *
     * @param string $name   Name of the database that should be created
     * @param array $fields  Associative array that contains the definition of each field of the new table
     * @param array $options  An associative array of table options:
     *
     * @return boolean $result
     */
    public function createTable($name, array $columns, array $options = array())
    {
        // Build array of the primary keys if any of the individual field definitions
        // specify primary => true
        $count = 0;
        foreach ($columns as $columnName => $definition) {
            if (isset($definition['primary']) && $definition['primary']) {
                if ($count == 0) {
                    $options['primary'] = array();
                }
                ++$count;
                $options['primary'][] = $columnName;
            }
        }

        $sql = $this->_platform->getCreateTableSql($name, $columns, $options);

        return $this->_executeSql($sql, 'exec');
    }

    /**
     * Create a new sequence
     *
     * @param string    $seqName        name of the sequence to be created
     * @param string    $start          start value of the sequence; default is 1
     * @param array     $options  An associative array of table options:
     *                          array(
     *                              'comment' => 'Foo',
     *                              'charset' => 'utf8',
     *                              'collate' => 'utf8_unicode_ci',
     *                          );
     * @return boolean $result
     * @throws Doctrine\DBAL\ConnectionException     if something fails at database level
     */
    public function createSequence($seqName, $start = 1, array $options = array())
    {
        $sql = $this->_platform->getCreateSequenceSql($seqName, $start, $options);

        return $this->_executeSql($sql, 'exec');
    }

    /**
     * Create a constraint on a table
     *
     * @param string    $table         name of the table on which the constraint is to be created
     * @param string    $name          name of the constraint to be created
     * @param array     $definition    associative array that defines properties of the constraint to be created.
     *                                 Currently, only one property named FIELDS is supported. This property
     *                                 is also an associative with the names of the constraint fields as array
     *                                 constraints. Each entry of this array is set to another type of associative
     *                                 array that specifies properties of the constraint that are specific to
     *                                 each field.
     *
     *                                 Example
     *                                    array(
     *                                        'fields' => array(
     *                                            'user_name' => array(),
     *                                            'last_login' => array()
     *                                        )
     *                                    )
     * @return boolean $result
     */
    public function createConstraint($table, $name, $definition)
    {
        $sql = $this->_platform->getCreateConstraintSql($table, $name, $definition);

        return $this->_executeSql($sql, 'exec');
    }

    /**
     * Create a new index on a table
     *
     * @param string    $table         name of the table on which the index is to be created
     * @param string    $name          name of the index to be created
     * @param array     $definition    associative array that defines properties of the index to be created.
     *                                 Currently, only one property named FIELDS is supported. This property
     *                                 is also an associative with the names of the index fields as array
     *                                 indexes. Each entry of this array is set to another type of associative
     *                                 array that specifies properties of the index that are specific to
     *                                 each field.
     *
     *                                 Currently, only the sorting property is supported. It should be used
     *                                 to define the sorting direction of the index. It may be set to either
     *                                 ascending or descending.
     *
     *                                 Not all DBMS support index sorting direction configuration. The DBMS
     *                                 drivers of those that do not support it ignore this property. Use the
     *                                 function supports() to determine whether the DBMS driver can manage indexes.
     *
     *                                 Example
     *                                    array(
     *                                        'fields' => array(
     *                                            'user_name' => array(
     *                                                'sorting' => 'ascending'
     *                                            ),
     *                                            'last_login' => array()
     *                                        )
     *                                    )
     * @return boolean $result
     */
    public function createIndex($table, $name, array $definition)
    {
        $sql = $this->_platform->getCreateIndexSql($table, $name, $definition);

        return $this->_executeSql($sql, 'exec');
    }

    /**
     * createForeignKey
     *
     * @param string    $table         name of the table on which the foreign key is to be created
     * @param array     $definition    associative array that defines properties of the foreign key to be created.
     * @return boolean $result
     */
    public function createForeignKey($table, array $definition)
    {
        $sql = $this->_platform->getCreateForeignKeySql($table, $definition);

        return $this->_executeSql($sql, 'exec');
    }

    /**
     * Create a new view
     *
     * @param string $name The name of the view
     * @param string $sql  The sql to set to the view
     * @return boolean $result
     */
    public function createView($name, $sql)
    {
        $sql = $this->_platform->getCreateViewSql($name, $sql);

        return $this->_executeSql($sql, 'exec');
    }

    /**
     * Drop a view
     *
     * @param string $name The name of the view
     * @return boolean $result
     */
    public function dropView($name)
    {
        $sql = $this->_platform->getDropViewSql($name);

        return $this->_executeSql($sql, 'exec');
    }

    /**
     * Alter an existing tables schema
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
     *                                 be the same as defined by the MDB2 parser.
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
     *                                 as defined by the MDB2 parser.
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
     * @return boolean $result
     */
    public function alterTable($name, array $changes, $check = false)
    {
        $sql = $this->_platform->getAlterTableSql($name, $changes, $check);

        return $this->_executeSql($sql, 'exec');
    }

    /**
     * Rename a given table to another name
     *
     * @param string $name     The current name of the table
     * @param string $newName  The new name of the table
     * @return boolean $result
     */
    public function renameTable($name, $newName)
    {
        $change = array(
            'name' => $newName
        );

        return $this->alterTable($name, $change);
    }

    /**
     * Add a new table column
     *
     * @param string $name          The name of the table
     * @param string $column        The name of the column to add
     * @param array  $definition    The definition of the column to add
     * @return boolean $result
     */
    public function addTableColumn($name, $column, $definition)
    {
        $change = array(
            'add' => array(
                $column => $definition
            )
        );

        return $this->alterTable($name, $change);
    }

    /**
     * Remove a column from a table
     *
     * @param string $tableName The name of the table
     * @param array|string $column The column name or array of names
     * @return boolean $result
     */
    public function removeTableColumn($name, $column)
    {
        $change = array(
            'remove' => is_array($column) ? $column : array($column => array())
        );

        return $this->alterTable($name, $change);
    }

    /**
     * Change a given table column. You can change the type, length, etc.
     *
     * @param string $name       The name of the table
     * @param string $type       The type of the column
     * @param string $length     The length of the column
     * @param string $definition The definition array for the column
     * @return boolean $result
     */
    public function changeTableColumn($name, $type, $length = null, $definition = array())
    {
        $definition['type'] = $type;

        $change = array(
            'change' => array(
                $name => array(
                    'length' => $length,
                    'definition' => $definition
                )
            )
        );

        return $this->alterTable($name, $change);
    }

    /**
     * Rename a given table column
     *
     * @param string $name       The name of the table
     * @param string $oldName    The old column name
     * @param string $newName    The new column
     * @param string $definition The column definition array if you want to change something
     * @return boolean $result
     */
    public function renameTableColumn($name, $oldName, $newName, $definition = array())
    {
        $change = array(
            'rename' => array(
                $oldName => array(
                    'name' => $newName,
                    'definition' => $definition
                )
            )
        );

        return $this->alterTable($name, $change);
    }

    protected function _getPortableDatabasesList($databases)
    {
        $list = array();
        foreach ($databases as $key => $value) {
            if ($value = $this->_getPortableDatabaseDefinition($value)) {
                $list[] = $value;
            }
        }
        return $list;
    }

    protected function _getPortableDatabaseDefinition($database)
    {
        return $database;
    }

    protected function _getPortableFunctionsList($functions)
    {
        $list = array();
        foreach ($functions as $key => $value) {
            if ($value = $this->_getPortableFunctionDefinition($value)) {
                $list[] = $value;
            }
        }
        return $list;
    }

    protected function _getPortableFunctionDefinition($function)
    {
        return $function;
    }

    protected function _getPortableTriggersList($triggers)
    {
        $list = array();
        foreach ($triggers as $key => $value) {
            if ($value = $this->_getPortableTriggerDefinition($value)) {
                $list[] = $value;
            }
        }
        return $list;
    }

    protected function _getPortableTriggerDefinition($trigger)
    {
        return $trigger;
    }

    protected function _getPortableSequencesList($sequences)
    {
        $list = array();
        foreach ($sequences as $key => $value) {
            if ($value = $this->_getPortableSequenceDefinition($value)) {
                $list[] = $value;
            }
        }
        return $list;
    }

    protected function _getPortableSequenceDefinition($sequence)
    {
        return $sequence;
    }

    protected function _getPortableTableConstraintsList($tableConstraints)
    {
        $list = array();
        foreach ($tableConstraints as $key => $value) {
            if ($value = $this->_getPortableTableConstraintDefinition($value)) {
                $list[] = $value;
            }
        }
        return $list;
    }

    protected function _getPortableTableConstraintDefinition($tableConstraint)
    {
        return $tableConstraint;
    }

    protected function _getPortableTableColumnList($tableColumns)
    {
        $list = array();
        foreach ($tableColumns as $key => $value) {
            if ($value = $this->_getPortableTableColumnDefinition($value)) {
                if (is_string($value['type'])) {
                    $value['type'] = \Doctrine\DBAL\Types\Type::getType($value['type']);
                }
                $list[] = $value;
            }
        }
        return $list;
    }

    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        return $tableColumn;
    }

    protected function _getPortableTableIndexesList($tableIndexes)
    {
        $list = array();
        foreach ($tableIndexes as $key => $value) {
            if ($value = $this->_getPortableTableIndexDefinition($value)) {
                $list[] = $value;
            }
        }
        return $list;
    }

    protected function _getPortableTableIndexDefinition($tableIndex)
    {
        return $tableIndex;
    }

    protected function _getPortableTablesList($tables)
    {
        $list = array();
        foreach ($tables as $key => $value) {
            if ($value = $this->_getPortableTableDefinition($value)) {
                $list[] = $value;
            }
        }
        return $list;
    }

    protected function _getPortableTableDefinition($table)
    {
        return $table;
    }

    protected function _getPortableUsersList($users)
    {
        $list = array();
        foreach ($users as $key => $value) {
            if ($value = $this->_getPortableUserDefinition($value)) {
                $list[] = $value;
            }
        }
        return $list;
    }

    protected function _getPortableUserDefinition($user)
    {
        return $user;
    }

    protected function _getPortableViewsList($views)
    {
        $list = array();
        foreach ($views as $key => $value) {
            if ($value = $this->_getPortableViewDefinition($value)) {
                $list[] = $value;
            }
        }
        return $list;
    }

    protected function _getPortableViewDefinition($view)
    {
        return $view;
    }

    protected function _getPortableTableForeignKeysList($tableForeignKeys)
    {
        $list = array();
        foreach ($tableForeignKeys as $key => $value) {
            if ($value = $this->_getPortableTableForeignKeyDefinition($value)) {
                $list[] = $value;
            }
        }
        return $list;
    }

    protected function _getPortableTableForeignKeyDefinition($tableForeignKey)
    {
        return $tableForeignKey;
    }

    protected function _executeSql($sql, $method = 'exec')
    {
        $result = true;
        foreach ((array) $sql as $query) {
            $result = $this->_conn->$method($query);
        }
        return $result;
    }

    public function tryMethod()
    {
        $args = func_get_args();
        $method = $args[0];
        unset($args[0]);
        $args = array_values($args);

        try {
            return call_user_func_array(array($this, $method), $args);
        } catch (\Exception $e) {
            //var_dump($e->getMessage());
            return false;
        }
    }

    private function _handleDropAndCreate($method, $arguments)
    {
        if (substr($method, 0, 13) == 'dropAndCreate') {
            $base = substr($method, 13, strlen($method));
            $dropMethod = 'drop' . $base;
            $createMethod = 'create' . $base;

            call_user_func_array(array($this, 'tryMethod'),
                array_merge(array($dropMethod), $arguments));

            call_user_func_array(array($this, 'tryMethod'),
                array_merge(array($createMethod), $arguments));

            return true;
        }

        return false;
    }

    private function _handleTryMethod($method, $arguments)
    {
        if (substr($method, 0, 3) == 'try') {
            $method = substr($method, 3, strlen($method));
            $method = strtolower($method[0]).substr($method, 1, strlen($method));

            return call_user_func_array(array($this, 'tryMethod'),
                array_merge(array($method), $arguments));
        }
    }

    public function __call($method, $arguments)
    {
        if ($result = $this->_handleDropAndCreate($method, $arguments)) {
            return $result;
        }

        if ($result = $this->_handleTryMethod($method, $arguments)) {
            return $result;
        }

        throw DoctrineException::updateMe("Invalid method named `" . $method . "` on class `" . __CLASS__ . "`");
    }
}