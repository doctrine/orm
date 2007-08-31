<?php
/*
 *  $Id: Firebird.php 1753 2007-06-19 11:10:13Z zYne $
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
Doctrine::autoload('Doctrine_Export');
/**
 * Doctrine_Export_Sqlite
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @author      Lorenzo Alberton <l.alberton@quipo.it> (PEAR MDB2 Interbase driver)
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1753 $
 */
class Doctrine_Export_Firebird extends Doctrine_Export
{
    /**
     * create a new database
     *
     * @param string $name  name of the database that should be created
     * @return void
     */
    public function createDatabase($name)
    {
        throw new Doctrine_Export_Exception(
                'PHP Interbase API does not support direct queries. You have to ' .
                'create the db manually by using isql command or a similar program');
    }
    /**
     * drop an existing database
     *
     * @param string $name  name of the database that should be dropped
     * @return void
     */
    public  function dropDatabase($name)
    {
        throw new Doctrine_Export_Exception(
                'PHP Interbase API does not support direct queries. You have ' .
                'to drop the db manually by using isql command or a similar program');
    }
    /**
     * add an autoincrement sequence + trigger
     *
     * @param string $name  name of the PK field
     * @param string $table name of the table
     * @param string $start start value for the sequence
     * @return void
     */
    public function _makeAutoincrement($name, $table, $start = null)
    {
        if (is_null($start)) {
            $this->conn->beginTransaction();
            $query = 'SELECT MAX(' . $this->conn->quoteIdentifier($name, true) . ') FROM ' . $this->conn->quoteIdentifier($table, true);
            $start = $this->conn->fetchOne($query, 'integer');

            ++$start;
            $result = $this->createSequence($table, $start);
            $this->conn->commit();
        } else {
            $result = $this->createSequence($table, $start);
        }

        $sequence_name = $this->conn->formatter->getSequenceName($table);
        $trigger_name  = $this->conn->quoteIdentifier($table . '_AUTOINCREMENT_PK', true);

        $table = $this->conn->quoteIdentifier($table, true);
        $name  = $this->conn->quoteIdentifier($name,  true);

        $triggerSql = 'CREATE TRIGGER ' . $trigger_name . ' FOR ' . $table . '
                        ACTIVE BEFORE INSERT POSITION 0
                        AS
                        BEGIN
                        IF (NEW.' . $name . ' IS NULL OR NEW.' . $name . ' = 0) THEN
                            NEW.' . $name . ' = GEN_ID('.$sequence_name.', 1);
                        END';
        $result = $this->conn->exec($triggerSql);

        // TODO ? $this->_silentCommit();

        return $result;
    }
    /**
     * drop an existing autoincrement sequence + trigger
     *
     * @param string $table name of the table
     * @return void
     */
    public function _dropAutoincrement($table)
    {

        $result = $this->dropSequence($table);

        //remove autoincrement trigger associated with the table
        $table = $this->conn->quote(strtoupper($table));
        $triggerName = $this->conn->quote(strtoupper($table) . '_AUTOINCREMENT_PK');

        return $this->conn->exec("DELETE FROM RDB\$TRIGGERS WHERE UPPER(RDB\$RELATION_NAME)=" . $table . " AND UPPER(RDB\$TRIGGER_NAME)=" . $triggerName);
    }
    /**
     * create a new table
     *
     * @param string $name     Name of the database that should be created
     * @param array $fields Associative array that contains the definition of each field of the new table
     *                        The indexes of the array entries are the names of the fields of the table an
     *                        the array entry values are associative arrays like those that are meant to be
     *                         passed with the field definitions to get[Type]Declaration() functions.
     *
     *                        Example
     *                        array(
     *
     *                            'id' => array(
     *                                'type' => 'integer',
     *                                'unsigned' => 1,
     *                                'notnull' => 1,
     *                                'default' => 0,
     *                            ),
     *                            'name' => array(
     *                                'type' => 'text',
     *                                'length' => 12,
     *                            ),
     *                            'description' => array(
     *                                'type' => 'text',
     *                                'length' => 12,
     *                            )
     *                        );
     * @param array $options  An associative array of table options:
     *
     * @return void
     */
    public function createTable($name, array $fields, array $options = array()) {
        parent::createTable($name, $fields, $options);

        // TODO ? $this->_silentCommit();
        foreach ($fields as $field_name => $field) {
            if ( ! empty($field['autoincrement'])) {
                //create PK constraint
                $pk_definition = array(
                    'fields' => array($field_name => array()),
                    'primary' => true,
                );
                //$pk_name = $name.'_PK';
                $pk_name = null;
                $result = $this->createConstraint($name, $pk_name, $pk_definition);

                //create autoincrement sequence + trigger
                return $this->_makeAutoincrement($field_name, $name, 1);
            }
        }
    }
    /**
     * Check if planned changes are supported
     *
     * @param string $name name of the database that should be dropped
     * @return void
     */
    public function checkSupportedChanges(&$changes)
    {
        foreach ($changes as $change_name => $change) {
            switch ($change_name) {
                case 'notnull':
                    throw new Doctrine_DataDict_Exception('it is not supported changes to field not null constraint');
                case 'default':
                    throw new Doctrine_DataDict_Exception('it is not supported changes to field default value');
                case 'length':
                    /*
                    return throw new Doctrine_DataDict_Firebird_Exception('it is not supported changes to field default length');
                    */
                case 'unsigned':
                case 'type':
                case 'declaration':
                case 'definition':
                    break;
                default:
                    throw new Doctrine_DataDict_Exception('it is not supported change of type' . $change_name);
            }
        }
        return true;
    }
    /**
     * drop an existing table
     *
     * @param string $name name of the table that should be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function dropTable($name)
    {
        $result = $this->_dropAutoincrement($name);
        $result = parent::dropTable($name);

        //$this->_silentCommit();

        return $result;
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
     * @return void
     */
    public function alterTable($name, array $changes, $check)
    {
        foreach ($changes as $changeName => $change) {
            switch ($changeName) {
                case 'add':
                case 'remove':
                case 'rename':
                    break;
                case 'change':
                    foreach ($changes['change'] as $field) {
                        $this->checkSupportedChanges($field);
                    }
                    break;
                default:
                    throw new Doctrine_DataDict_Exception('change type ' . $changeName . ' not yet supported');
            }
        }
        if ($check) {
            return true;
        }
        $query = '';
        if (!empty($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $fieldName => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $query.= 'ADD ' . $this->getDeclaration($field['type'], $fieldName, $field, $name);
            }
        }

        if (!empty($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $field_name = $this->conn->quoteIdentifier($field_name, true);
                $query.= 'DROP ' . $field_name;
            }
        }

        if (!empty($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $field_name = $this->conn->quoteIdentifier($field_name, true);
                $query.= 'ALTER ' . $field_name . ' TO ' . $this->conn->quoteIdentifier($field['name'], true);
            }
        }

        if (!empty($changes['change']) && is_array($changes['change'])) {
            // missing support to change DEFAULT and NULLability
            foreach ($changes['change'] as $fieldName => $field) {
                $this->checkSupportedChanges($field);
                if ($query) {
                    $query.= ', ';
                }
                $this->conn->loadModule('Datatype', null, true);
                $field_name = $this->conn->quoteIdentifier($fieldName, true);
                $query.= 'ALTER ' . $field_name.' TYPE ' . $this->getTypeDeclaration($field['definition']);
            }
        }

        if (!strlen($query)) {
            return false;
        }

        $name = $this->conn->quoteIdentifier($name, true);
        $result = $this->conn->exec('ALTER TABLE ' . $name . ' ' . $query);
        $this->_silentCommit();
        return $result;
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
     * @return void
     */
    public function createIndexSql($table, $name, array $definition)
    {
        $query = 'CREATE';

        $query_sort = '';
        foreach ($definition['fields'] as $field) {
            if (!strcmp($query_sort, '') && isset($field['sorting'])) {
                switch ($field['sorting']) {
                    case 'ascending':
                        $query_sort = ' ASC';
                        break;
                    case 'descending':
                        $query_sort = ' DESC';
                        break;
                }
            }
        }
        $table = $this->conn->quoteIdentifier($table, true);
        $name  = $this->conn->quoteIdentifier($this->conn->formatter->getIndexName($name), true);
        $query .= $query_sort. ' INDEX ' . $name . ' ON ' . $table;
        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $this->conn->quoteIdentifier($field, true);
        }
        $query .= ' ('.implode(', ', $fields) . ')';

        return $query;
    }
    /**
     * create a constraint on a table
     *
     * @param string    $table      name of the table on which the constraint is to be created
     * @param string    $name       name of the constraint to be created
     * @param array     $definition associative array that defines properties of the constraint to be created.
     *                              Currently, only one property named FIELDS is supported. This property
     *                              is also an associative with the names of the constraint fields as array
     *                              constraints. Each entry of this array is set to another type of associative
     *                              array that specifies properties of the constraint that are specific to
     *                              each field.
     *
     *                              Example
     *                                  array(
     *                                      'fields' => array(
     *                                          'user_name' => array(),
     *                                          'last_login' => array(),
     *                                      )
     *                                  )
     * @return void
     */
    public function createConstraint($table, $name, $definition)
    {
        $table = $this->conn->quoteIdentifier($table, true);

        if (!empty($name)) {
            $name = $this->conn->quoteIdentifier($this->conn->formatter->getIndexName($name), true);
        }
        $query = "ALTER TABLE $table ADD";
        if (!empty($definition['primary'])) {
            if (!empty($name)) {
                $query.= ' CONSTRAINT '.$name;
            }
            $query.= ' PRIMARY KEY';
        } else {
            $query.= ' CONSTRAINT '. $name;
            if (!empty($definition['unique'])) {
               $query.= ' UNIQUE';
            }
        }
        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $this->conn->quoteIdentifier($field, true);
        }
        $query .= ' ('. implode(', ', $fields) . ')';
        $result = $this->conn->exec($query);
        // TODO ? $this->_silentCommit();
        return $result;
    }
    /**
     * A method to return the required SQL string that fits between CREATE ... TABLE
     * to create the table as a temporary table.
     *
     * @return string The string required to be placed between "CREATE" and "TABLE"
     *                to generate a temporary table, if possible.
     */
    public function getTemporaryTableQuery()
    {
        return 'GLOBAL TEMPORARY';
    }
    /**
     * create sequence
     *
     * @param string $seqName name of the sequence to be created
     * @param string $start start value of the sequence; default is 1
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
        $sequenceName = $this->conn->formatter->getSequenceName($seqName);

        $this->conn->exec('CREATE GENERATOR ' . $sequenceName);

        try {
            $this->conn->exec('SET GENERATOR ' . $sequenceName . ' TO ' . ($start-1));
            
            return true;
        } catch (Doctrine_Connection_Exception $e) {
            try {
                $this->dropSequence($seqName);
            } catch(Doctrine_Connection_Exception $e) {
                throw new Doctrine_Export_Exception('Could not drop inconsistent sequence table');
            }
        }
        throw new Doctrine_Export_Exception('could not create sequence table');
    }
    /**
     * drop existing sequence
     *
     * @param string $seqName name of the sequence to be dropped
     * @return void
     */
    public function dropSequenceSql($seqName)
    {
        $sequenceName = $this->conn->formatter->getSequenceName($seqName);
        $sequenceName = $this->conn->quote($sequenceName);
        $query = "DELETE FROM RDB\$GENERATORS WHERE UPPER(RDB\$GENERATOR_NAME)=" . $sequenceName;
        
        return $query;
    }
}
