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
    public function listSequences()
    {
        $sql = $this->_platform->getListSequencesSql();

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

        $tableContraints = $this->_conn->fetchAll($sql);

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
     * Drop the database for this connection
     *
     * @return boolean $result
     */
    public function dropDatabase()
    {
        $sql = $this->_platform->getDropDatabaseSql();

        return $this->_executeSql($sql, 'execute');
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
     * @param string $database The name of the database
     * @return boolean $result
     */
    public function createDatabase($database)
    {
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

    protected function _getPortableDatabasesList($databases)
    {
        return $databases;
    }

    protected function _getPortableFunctionsList($functions)
    {
        return $functions;
    }

    protected function _getPortableTriggersList($triggers)
    {
        return $triggers;
    }

    protected function _getPortableSequencesList($sequences)
    {
        return $sequences;
    }

    protected function _getPortableTableConstraintsList($tableConstraints)
    {
        return $tableConstraints;
    }

    protected function _getPortableTableColumnList($tableColumns)
    {
        return $tableColumns;
    }

    protected function _getPortableTableIndexesList($tableIndexes)
    {
        return $tableIndexes;
    }

    protected function _getPortableTablesList($tables)
    {
        return $tables;
    }

    protected function _getPortableUsersList($users)
    {
        return $users;
    }

    protected function _getPortableViewsList($views)
    {
        return $views;
    }

    protected function _executeSql($sql, $method = 'exec')
    {
        $result = true;
        foreach ((array) $sql as $query) {
            $result = $this->_conn->$method($query);
        }
        return $result;
    }
}