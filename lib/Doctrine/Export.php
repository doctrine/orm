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
 * <http://www.phpdoctrine.com>.
 */
/**
 * Doctrine_Export
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Export {
    /**
     * @var Doctrine_Connection $conn       Doctrine_Connection object
     */
    private $conn;
    /**
     * @var mixed $dbh                      the database handler (either PDO or Doctrine_Db object)
     */
    private $dbh;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->dbh  = $conn->getDBH();
    }
    /**
     * drop an existing database
     *
     * @param string $name name of the database that should be dropped
     * @return void
     */
    public function dropDatabase($database) {
        throw new Doctrine_Export_Exception('Drop database not supported by this driver.');
    }
    /**
     * dropTable
     *
     * @param string    $table              name of table that should be dropped from the database
     * @throws PDOException
     * @return void
     */
    public function dropTable($table) {
        $this->dbh->query('DROP TABLE '.$table);
    }

    /**
     * drop existing index
     *
     * @param string    $table         name of table that should be used in method
     * @param string    $name         name of the index to be dropped
     * @return void
     */
    public function dropIndex($table, $name) {
        $name = $db->quoteIdentifier($db->getIndexName($name), true);
        return $db->exec("DROP INDEX $name");
    }
    /**
     * drop existing constraint
     *
     * @param string    $table        name of table that should be used in method
     * @param string    $name         name of the constraint to be dropped
     * @param string    $primary      hint if the constraint is primary
     * @return void
     */
    public function dropConstraint($table, $name, $primary = false) {
        $table = $db->quoteIdentifier($table, true);
        $name  = $db->quoteIdentifier($db->getIndexName($name), true);
        return $db->exec("ALTER TABLE $table DROP CONSTRAINT $name");
    }
    /**
     * drop existing sequence
     *
     * @param string    $seq_name     name of the sequence to be dropped
     * @return void
     */
    public function dropSequence($name) {
        throw new Doctrine_Export_Exception('Drop sequence not supported by this driver.');
    }
    /**
     * create a new database
     *
     * @param string $name name of the database that should be created
     * @return void
     */
    public function createDatabase($database) {
        throw new Doctrine_Export_Exception('Create database not supported by this driver.');
    }
    /**
     * create a new table
     *
     * @param string $name   Name of the database that should be created
     * @param array $fields  Associative array that contains the definition of each field of the new table
     *                       The indexes of the array entries are the names of the fields of the table an
     *                       the array entry values are associative arrays like those that are meant to be
     *                       passed with the field definitions to get[Type]Declaration() functions.
     *                          array(
     *                              'id' => array(
     *                                  'type' => 'integer',
     *                                  'unsigned' => 1
     *                                  'notnull' => 1
     *                                  'default' => 0
     *                              ),
     *                              'name' => array(
     *                                  'type' => 'text',
     *                                  'length' => 12
     *                              ),
     *                              'password' => array(
     *                                  'type' => 'text',
     *                                  'length' => 12
     *                              )
     *                          );
     * @param array $options  An associative array of table options:
     *
     * @return void
     */
    public function createTable($name, $fields, $options = array()) {
        if ( ! $name)
            throw new Doctrine_Export_Exception('no valid table name specified');

        if (empty($fields))
            throw new Doctrine_Export_Exception('no fields specified for table '.$name);

        $query_fields = $this->getFieldDeclarationList($fields);

        if (!empty($options['primary'])) {
            $query_fields.= ', PRIMARY KEY ('.implode(', ', array_keys($options['primary'])).')';
        }

        $name  = $db->quoteIdentifier($name, true);
        $query = "CREATE TABLE $name ($query_fields)";
        return $db->exec($query);
    }
    /**
     * create sequence
     *
     * @param string    $seq_name     name of the sequence to be created
     * @param string    $start         start value of the sequence; default is 1
     * @return void
     */
    public function createSequence($seq_name, $start = 1) {
        throw new Doctrine_Export_Exception('Create sequence not supported by this driver.');
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
    public function createConstraint($table, $name, $definition) {
        $table = $db->quoteIdentifier($table, true);
        $name = $db->quoteIdentifier($db->getIndexName($name), true);
        $query = "ALTER TABLE $table ADD CONSTRAINT $name";
        if (!empty($definition['primary'])) {
            $query.= ' PRIMARY KEY';
        } elseif (!empty($definition['unique'])) {
            $query.= ' UNIQUE';
        }
        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $db->quoteIdentifier($field, true);
        }
        $query .= ' ('. implode(', ', $fields) . ')';
        return $db->exec($query);
    }
    /**
     * Get the stucture of a field into an array
     *
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
     * @throws PDOException
     * @return void
     */
    public function createIndex($table, $name, array $definition) {
        $table  = $this->conn->quoteIdentifier($table);
        $name   = $this->conn->quoteIdentifier($name);

        $query = "CREATE INDEX $name ON $table";
        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $this->conn->quoteIdentifier($field);
        }
        $query .= ' ('. implode(', ', $fields) . ')';
        return $this->dbh->query($query);
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
    public function alterTable($name, $changes, $check) {
        throw new Doctrine_Export_Exception('Alter table not supported by this driver.');
    }
    /**
     * Get declaration of a number of field in bulk
     *
     * @param string $fields  a multidimensional associative array.
     *      The first dimension determines the field name, while the second
     *      dimension is keyed with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      default
     *          Boolean value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     *
     * @return string
     */
    public function getFieldDeclarationList(array $fields) {
        foreach ($fields as $field_name => $field) {
            $query = $db->getDeclaration($field['type'], $field_name, $field);
            $query_fields[] = $query;
        }
        return implode(', ', $query_fields);
    }
    /**
     * export
     */
    public function export() {
        $parent = new ReflectionClass('Doctrine_Record');
        $conn   = Doctrine_Manager::getInstance()->getCurrentConnection();
        $old    = $conn->getAttribute(Doctrine::ATTR_CREATE_TABLES);

        $conn->setAttribute(Doctrine::ATTR_CREATE_TABLES, true);
        
        foreach(get_declared_classes() as $name) {
            $class = new ReflectionClass($name);

            if($class->isSubclassOf($parent) && ! $class->isAbstract())
                $obj = new $class();
        }
        $conn->setAttribute(Doctrine::ATTR_CREATE_TABLES, $old);
    }
}
