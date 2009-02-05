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
 * @version     $Revision$
 * @since       2.0
 */
abstract class AbstractSchemaManager
{
    protected $_conn;

    public function __construct(\Doctrine\DBAL\Connection $conn)
    {
        $this->_conn = $conn;
    }

    /**
     * lists all databases
     *
     * @return array
     */
    public function listDatabases()
    {
        return $this->_conn->fetchColumn($this->_conn->getDatabasePlatform()
                ->getListDatabasesSql());
    }

    /**
     * lists all availible database functions
     *
     * @return array
     */
    public function listFunctions()
    {
        return $this->_conn->fetchColumn($this->_conn->getDatabasePlatform()
                ->getListFunctionsSql());
    }

    /**
     * Lists all database triggers.
     *
     * @param string|null $database
     * @return array
     */
    public function listTriggers($database = null)
    {
        return $this->_conn->fetchColumn($this->_conn->getDatabasePlatform()
                ->getListTriggersSql());
    }

    /**
     * lists all database sequences
     *
     * @param string|null $database
     * @return array
     */
    public function listSequences($database = null)
    {
        return $this->_conn->fetchColumn($this->_conn->getDatabasePlatform()
                ->getListSequencesSql());
    }

    /**
     * Lists table constraints.
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableConstraints($table)
    {
        return $this->_conn->fetchColumn($this->_conn->getDatabasePlatform()
                ->getListTableConstraintsSql());
    }

    /**
     * Lists table columns.
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableColumns($table)
    {
        return $this->_conn->fetchColumn($this->_conn->getDatabasePlatform()
                ->getListTableColumnsSql());
    }

    /**
     * Lists table indexes.
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableIndexes($table)
    {
        return $this->_conn->fetchColumn($this->_conn->getDatabasePlatform()
                ->getListTableIndexesSql());
    }

    /**
     * Lists tables.
     *
     * @param string|null $database
     * @return array
     */
    public function listTables($database = null)
    {
        return $this->_conn->fetchColumn($this->_conn->getDatabasePlatform()
                ->getListTablesSql());
    }

    /**
     * Lists database users.
     *
     * @return array
     */
    public function listUsers()
    {
        return $this->_conn->fetchColumn($this->_conn->getDatabasePlatform()
                ->getListUsersSql());
    }

    /**
     * Lists database views.
     *
     * @param string|null $database
     * @return array
     */
    public function listViews($database = null)
    {
        return $this->_conn->fetchColumn($this->_conn->getDatabasePlatform()
                ->getListViewsSql());
    }

    /**
     * drop an existing database
     * (this method is implemented by the drivers)
     *
     * @param string $name name of the database that should be dropped
     * @return void
     */
    public function dropDatabase($database)
    {
        $this->_conn->execute($this->_conn->getDatabasePlatform()
                ->getDropDatabaseSql($database));
    }

    /**
     * drop an existing table
     *
     * @param string $table           name of table that should be dropped from the database
     * @return void
     */
    public function dropTable($table)
    {
        $this->_conn->execute($this->_conn->getDatabasePlatform()
                ->getDropTableSql($table));
    }

    /**
     * drop existing index
     *
     * @param string    $table        name of table that should be used in method
     * @param string    $name         name of the index to be dropped
     * @return void
     */
    public function dropIndex($table, $name)
    {
        return $this->_conn->exec($this->_conn->getDatabasePlatform()
                ->getDropIndexSql($table, $name));
    }

    /**
     * drop existing constraint
     *
     * @param string    $table        name of table that should be used in method
     * @param string    $name         name of the constraint to be dropped
     * @param string    $primary      hint if the constraint is primary
     * @return void
     */
    public function dropConstraint($table, $name, $primary = false)
    {
        $table = $this->_conn->getDatabasePlatform()->quoteIdentifier($table);
        $name = $this->_conn->getDatabasePlatform()->quoteIdentifier($name);
        
        return $this->_conn->exec('ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $name);
    }

    /**
     * drop existing foreign key
     *
     * @param string    $table        name of table that should be used in method
     * @param string    $name         name of the foreign key to be dropped
     * @return void
     */
    public function dropForeignKey($table, $name)
    {
        return $this->dropConstraint($table, $name);
    }

    /**
     * drop existing sequence
     * (this method is implemented by the drivers)
     *
     * @throws Doctrine_Connection_Exception     if something fails at database level
     * @param string $sequenceName      name of the sequence to be dropped
     * @return void
     */
    public function dropSequence($sequenceName)
    {
        $this->_conn->exec($this->_conn->getDatabasePlatform()->getDropSequenceSql($sequenceName));
    }

    /**
     * create a new database
     * (this method is implemented by the drivers)
     *
     * @param string $name name of the database that should be created
     * @return void
     */
    public function createDatabase($database)
    {
        $this->_conn->execute($this->_conn->getDatabasePlatform()->getCreateDatabaseSql($database));
    }

    /**
     * create a new table
     *
     * @param string $name   Name of the database that should be created
     * @param array $fields  Associative array that contains the definition of each field of the new table
     * @param array $options  An associative array of table options:
     * @see Doctrine_Export::createTableSql()
     *
     * @return void
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

        $sql = (array) $this->_conn->getDatabasePlatform()->getCreateTableSql(
                $name, $columns, $options);

        foreach ($sql as $query) {
            $this->_conn->execute($query);
        }
    }

    /**
     * create sequence
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
     * @return void
     */
    public function createSequence($seqName, $start = 1, array $options = array())
    {
        return $this->_conn->execute($this->_conn->getDatabasePlatform()
                ->getCreateSequenceSql($seqName, $start, $options));
    }

    /**
     * create a constraint on a table
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
     * @return void
     */
    public function createConstraint($table, $name, $definition)
    {
        $sql = $this->_conn->getDatabasePlatform()->getCreateConstraintSql($table, $name, $definition);
        return $this->_conn->exec($sql);
    }

    /**
     * Get the stucture of a field into an array
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
     * @return void
     */
    public function createIndex($table, $name, array $definition)
    {
        return $this->_conn->execute($this->_conn->getDatabasePlatform()
                ->getCreateIndexSql($table, $name, $definition));
    }

    /**
     * createForeignKey
     *
     * @param string    $table         name of the table on which the foreign key is to be created
     * @param array     $definition    associative array that defines properties of the foreign key to be created.
     * @return string
     */
    public function createForeignKey($table, array $definition)
    {
        $sql = $this->_conn->getDatabasePlatform()->getCreateForeignKeySql($table, $definition);
        return $this->_conn->execute($sql);
    }

    /**
     * alter an existing table
     * (this method is implemented by the drivers)
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
     * @return void
     */
    public function alterTable($name, array $changes, $check = false)
    {
        $sql = $this->_conn->getDatabasePlatform()->getAlterTableSql($name, $changes, $check);
        
        if (is_string($sql) && $sql) {
            $this->_conn->execute($sql);
        }
    }
}

?>