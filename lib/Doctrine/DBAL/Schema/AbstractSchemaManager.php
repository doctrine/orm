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
use \Doctrine\DBAL\DBALException;
use \Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Base class for schema managers. Schema managers are used to inspect and/or
 * modify the database schema/structure.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @author      Roman Borschel <roman@code-factory.org>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @version     $Revision$
 * @since       2.0
 */
abstract class AbstractSchemaManager
{
    /**
     * Holds instance of the Doctrine connection for this schema manager
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $_conn;

    /**
     * Holds instance of the database platform used for this schema manager
     *
     * @var \Doctrine\DBAL\Platform\AbstractPlatform
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
     * Return associated platform.
     *
     * @return \Doctrine\DBAL\Platform\AbstractPlatform
     */
    public function getDatabasePlatform()
    {
        return $this->_platform;
    }

    /**
     * Try any method on the schema manager. Normally a method throws an 
     * exception when your DBMS doesn't support it or if an error occurs.
     * This method allows you to try and method on your SchemaManager
     * instance and will return false if it does not work or is not supported.
     *
     * <code>
     * $result = $sm->tryMethod('dropView', 'view_name');
     * </code>
     *
     * @return mixed
     */
    public function tryMethod()
    {
        $args = func_get_args();
        $method = $args[0];
        unset($args[0]);
        $args = array_values($args);

        try {
            return call_user_func_array(array($this, $method), $args);
        } catch (\Exception $e) {
            return false;
        }
    }

    /* list*() Methods */

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
     * List the names of available functions for this connection
     *
     * @example array(
     *  'functionA', 'functionB', 'procedureA',
     * )
     * @return array $functions
     */
    public function listFunctions()
    {
        $sql = $this->_platform->getListFunctionsSql();

        $functions = $this->_conn->fetchAll($sql);

        return $this->_getPortableFunctionsList($functions);
    }

    /**
     * List the names of available triggers for this connection
     *
     * @example array(
     *  'triggerName1', 'triggerName2',
     * );
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
     * List the columns for a given table.
     *
     * @example array(
     *     'colA' => array(
     *          'name' => 'colA',
     *          'type' => \Doctrine\DBAL\Types\StringType instance,
     *          'length' => 255,
     *          'precision' => null,
     *          'scale' => null,
     *          'unsigned' => false,
     *          'fixed' => false,
     *          'notnull' => false,
     *          'default' => null,
     *          'platformDetails' => array(),
     *     ),
     * );
     *
     * In contrast to other libraries and to the old version of Doctrine,
     * this column definition does try to contain the 'primary' field for
     * the reason that it is not portable accross different RDBMS. Use
     * {@see listTableIndexes($tableName)} to retrieve the primary key
     * of a table. We're a RDBMS specifies more details these are held
     * in the platformDetails array.
     *
     * @param string $table The name of the table.
     * @return array $tableColumns The column descriptions.
     */
    public function listTableColumns($table)
    {
        $sql = $this->_platform->getListTableColumnsSql($table);

        $tableColumns = $this->_conn->fetchAll($sql);

        return $this->_getPortableTableColumnList($tableColumns);
    }

    /**
     * List the indexes for a given table returning an array of Index instances.
     *
     * Keys of the portable indexes list are all lower-cased.
     *
     * @param string $table The name of the table
     * @return Index[] $tableIndexes
     */
    public function listTableIndexes($table)
    {
        $sql = $this->_platform->getListTableIndexesSql($table);

        $tableIndexes = $this->_conn->fetchAll($sql);

        return $this->_getPortableTableIndexesList($tableIndexes, $table);
    }

    /**
     * List the tables for this connection
     *
     * @return array(int => Table)
     */
    public function listTables()
    {
        $sql = $this->_platform->getListTablesSql();

        $tables = $this->_conn->fetchAll($sql);

        $tableNames = $this->_getPortableTablesList($tables);
        $tables = array();

        foreach ($tableNames AS $tableName) {
            $columns = $this->listTableColumns($tableName);
            $foreignKeys = array();
            if ($this->_platform->supportsForeignKeyConstraints()) {
                $foreignKeys = $this->listTableForeignKeys($tableName);
            }
            $indexes = $this->listTableIndexes($tableName);

            $idGeneratorType = Table::ID_NONE;
            foreach ($columns AS $column) {
                if ($column->hasPlatformOption('autoincrement') && $column->getPlatformOption('autoincrement')) {
                    $idGeneratorType = Table::ID_IDENTITY;
                }
            }

            $tables[] = new Table($tableName, $columns, $indexes, $foreignKeys, $idGeneratorType, array());
        }

        return $tables;
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
     * @example array(
     *  array('name' => 'ViewA', 'sql' => 'SELECT * FROM foo'),
     *  array('name' => 'ViewB', 'sql' => 'SELECT * FROM bar'),
     * )
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

    /* drop*() Methods */

    /**
     * Drops a database.
     * 
     * NOTE: You can not drop the database this SchemaManager is currently connected to.
     *
     * @param  string $database The name of the database to drop
     */
    public function dropDatabase($database)
    {
        $this->_execSql($this->_platform->getDropDatabaseSql($database));
    }

    /**
     * Drop the given table
     *
     * @param string $table The name of the table to drop
     */
    public function dropTable($table)
    {
        $this->_execSql($this->_platform->getDropTableSql($table));
    }

    /**
     * Drop the index from the given table
     *
     * @param Index|string $index  The name of the index
     * @param string|Table $table The name of the table
     */
    public function dropIndex($index, $table)
    {
        if($index instanceof Index) {
            $index = $index->getName();
        }

        $this->_execSql($this->_platform->getDropIndexSql($index, $table));
    }

    /**
     * Drop the constraint from the given table
     *
     * @param Constraint $constraint
     * @param string $table   The name of the table
     */
    public function dropConstraint(Constraint $constraint, $table)
    {
        $this->_execSql($this->_platform->getDropConstraintSql($constraint, $table));
    }

    /**
     * Drops a foreign key from a table.
     *
     * @param ForeignKeyConstraint|string $table The name of the table with the foreign key.
     * @param Table|string $name  The name of the foreign key.
     * @return boolean $result
     */
    public function dropForeignKey($foreignKey, $table)
    {
        $this->_execSql($this->_platform->getDropForeignKeySql($foreignKey, $table));
    }

    /**
     * Drops a sequence with a given name.
     *
     * @param string $name The name of the sequence to drop.
     */
    public function dropSequence($name)
    {
        $this->_execSql($this->_platform->getDropSequenceSql($name));
    }

    /**
     * Drop a view
     *
     * @param string $name The name of the view
     * @return boolean $result
     */
    public function dropView($name)
    {
        $this->_execSql($this->_platform->getDropViewSql($name));
    }

    /* create*() Methods */

    /**
     * Creates a new database.
     *
     * @param string $database The name of the database to create.
     */
    public function createDatabase($database)
    {
        $this->_execSql($this->_platform->getCreateDatabaseSql($database));
    }

    /**
     * Create a new table.
     *
     * @param Table $table
     * @param int $createFlags
     */
    public function createTable(Table $table)
    {
        $createFlags = AbstractPlatform::CREATE_INDEXES|AbstractPlatform::CREATE_FOREIGNKEYS;
        $this->_execSql($this->_platform->getCreateTableSql($table, $createFlags));
    }

    /**
     * Create a new sequence
     *
     * @param Sequence $sequence
     * @throws Doctrine\DBAL\ConnectionException     if something fails at database level
     */
    public function createSequence($sequence)
    {
        $this->_execSql($this->_platform->getCreateSequenceSql($sequence));
    }

    /**
     * Create a constraint on a table
     *
     * @param Constraint $constraint
     * @param string|Table $table
     */
    public function createConstraint(Constraint $constraint, $table)
    {
        $this->_execSql($this->_platform->getCreateConstraintSql($constraint, $table));
    }

    /**
     * Create a new index on a table
     *
     * @param Index     $index
     * @param string    $table         name of the table on which the index is to be created
     */
    public function createIndex(Index $index, $table)
    {
        $this->_execSql($this->_platform->getCreateIndexSql($index, $table));
    }

    /**
     * Create a new foreign key
     *
     * @param ForeignKeyConstraint  $foreignKey    ForeignKey instance
     * @param string|Table          $table         name of the table on which the foreign key is to be created
     */
    public function createForeignKey(ForeignKeyConstraint $foreignKey, $table)
    {
        $this->_execSql($this->_platform->getCreateForeignKeySql($foreignKey, $table));
    }

    /**
     * Create a new view
     *
     * @param string $name The name of the view
     * @param string $sql  The sql to set to the view
     */
    public function createView($name, $sql)
    {
        $this->_execSql($this->_platform->getCreateViewSql($name, $sql));
    }

    /* dropAndCreate*() Methods */

    /**
     * Drop and create a constraint
     *
     * @param Constraint    $constraint
     * @param string        $table
     * @see dropConstraint()
     * @see createConstraint()
     */
    public function dropAndCreateConstraint(Constraint $constraint, $table)
    {
        $this->tryMethod('dropConstraint', $constraint, $table);
        $this->createConstraint($constraint, $table);
    }

    /**
     * Drop and create a new index on a table
     *
     * @param string|Table $table         name of the table on which the index is to be created
     * @param Index $index
     */
    public function dropAndCreateIndex(Index $index, $table)
    {
        $this->tryMethod('dropIndex', $index->getName(), $table);
        $this->createIndex($index, $table);
    }

    /**
     * Drop and create a new foreign key
     *
     * @param ForeignKeyConstraint  $foreignKey    associative array that defines properties of the foreign key to be created.
     * @param string|Table          $table         name of the table on which the foreign key is to be created
     */
    public function dropAndCreateForeignKey(ForeignKeyConstraint $foreignKey, $table)
    {
        $this->tryMethod('dropForeignKey', $foreignKey, $table);
        $this->createForeignKey($foreignKey, $table);
    }

    /**
     * Drop and create a new sequence
     *
     * @param Sequence $sequence
     * @throws Doctrine\DBAL\ConnectionException     if something fails at database level
     */
    public function dropAndCreateSequence(Sequence $sequence)
    {
        $this->tryMethod('createSequence', $seqName, $start, $allocationSize);
        $this->createSequence($seqName, $start, $allocationSize);
    }

    /**
     * Drop and create a new table.
     *
     * @param Table $table
     */
    public function dropAndCreateTable(Table $table)
    {
        $this->tryMethod('dropTable', $table->getName());
        $this->createTable($table);
    }

    /**
     * Drop and creates a new database.
     *
     * @param string $database The name of the database to create.
     */
    public function dropAndCreateDatabase($database)
    {
        $this->tryMethod('dropDatabase', $database);
        $this->createDatabase($database);
    }

    /**
     * Drop and create a new view
     *
     * @param string $name The name of the view
     * @param string $sql  The sql to set to the view
     */
    public function dropAndCreateView($name, $sql)
    {
        $this->tryMethod('dropView', $name);
        $this->createView($name, $sql);
    }

    /* alterTable() Methods */

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
     */
    public function alterTable($name, array $changes, $check = false)
    {
        $queries = $this->_platform->getAlterTableSql($name, $changes, $check);
        foreach($queries AS $ddlQuery) {
            $this->_execSql($ddlQuery);
        }
    }

    /**
     * Rename a given table to another name
     *
     * @param string $name     The current name of the table
     * @param string $newName  The new name of the table
     */
    public function renameTable($name, $newName)
    {
        $change = array(
            'name' => $newName
        );
        $this->alterTable($name, $change);
    }

    /**
     * Add a new table column
     *
     * @param string $name          The name of the table
     * @param string $column        The name of the column to add
     * @param array  $definition    The definition of the column to add
     */
    public function addTableColumn($name, $column, $definition)
    {
        $change = array(
            'add' => array(
                $column => $definition
            )
        );
        $this->alterTable($name, $change);
    }

    /**
     * Remove a column from a table
     *
     * @param string $tableName The name of the table
     * @param array|string $column The column name or array of names
     */
    public function removeTableColumn($name, $column)
    {
        $change = array(
            'remove' => is_array($column) ? $column : array($column => array())
        );
        $this->alterTable($name, $change);
    }

    /**
     * Change a given table column. You can change the type, length, etc.
     *
     * @param string $name       The name of the table
     * @param string $type       The type of the column
     * @param string $length     The length of the column
     * @param string $definition The definition array for the column
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
        $this->alterTable($name, $change);
    }

    /**
     * Rename a given table column
     *
     * @param string $name       The name of the table
     * @param string $oldName    The old column name
     * @param string $newName    The new column
     * @param string $definition The column definition array if you want to change something
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
        $this->alterTable($name, $change);
    }

    /**
     * Methods for filtering return values of list*() methods to convert
     * the native DBMS data definition to a portable Doctrine definition
     */

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

    /**
     * @param array $sequence
     * @return Sequence
     */
    protected function _getPortableSequenceDefinition($sequence)
    {
        throw DBALException::notSupported('Sequences');
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

    /**
     * Independent of the database the keys of the column list result are lowercased.
     *
     * The name of the created column instance however is kept in its case.
     *
     * @param  array $tableColumns
     * @return array
     */
    protected function _getPortableTableColumnList($tableColumns)
    {
        $list = array();
        foreach ($tableColumns as $key => $column) {
            if ($column = $this->_getPortableTableColumnDefinition($column)) {
                $name = strtolower($column->getName());
                $list[$name] = $column;
            }
        }
        return $list;
    }

    /**
     * Get Table Column Definition
     *
     * @param array $tableColumn
     * @return Column
     */
    abstract protected function _getPortableTableColumnDefinition($tableColumn);

    /**
     * Aggregate and group the index results according to the required data result.
     *
     * @param  array $tableIndexRows
     * @param  string $tableName
     * @return array
     */
    protected function _getPortableTableIndexesList($tableIndexRows, $tableName=null)
    {
        $result = array();
        foreach($tableIndexRows AS $tableIndex) {
            $indexName = $keyName = $tableIndex['key_name'];
            if($tableIndex['primary']) {
                $keyName = 'primary';
            }
            $keyName = strtolower($keyName);

            if(!isset($result[$keyName])) {
                $result[$keyName] = array(
                    'name' => $indexName,
                    'columns' => array($tableIndex['column_name']),
                    'unique' => $tableIndex['non_unique'] ? false : true,
                    'primary' => $tableIndex['primary'],
                );
            } else {
                $result[$keyName]['columns'][] = $tableIndex['column_name'];
            }
        }

        $indexes = array();
        foreach($result AS $indexKey => $data) {
            $indexes[$indexKey] = new Index($data['name'], $data['columns'], $data['unique'], $data['primary']);
        }

        return $indexes;
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

    protected function _execSql($sql)
    {
        foreach ((array) $sql as $query) {
            $this->_conn->executeUpdate($query);
        }
    }

    /**
     * Create a schema instance for the current database.
     * 
     * @return Schema
     */
    public function createSchema()
    {
        $sequences = array();
        if($this->_platform->supportsSequences()) {
            $sequences = $this->listSequences();
        }
        $tables = $this->listTables();

        return new Schema($tables, $sequences);
    }
}