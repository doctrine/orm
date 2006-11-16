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
 * @version     $Revision$
 */
class Doctrine_Export_Firebird extends Doctrine_Export {
    /**
     * create a new database
     *
     * @param string $name  name of the database that should be created
     * @return void
     */
    public function createDatabase($name) {
        throw new Doctrine_Export_Firebird_Exception(
                'PHP Interbase API does not support direct queries. You have to ' .
                'create the db manually by using isql command or a similar program');
    }
    /**
     * drop an existing database
     *
     * @param string $name  name of the database that should be dropped
     * @return void
     */
    public  function dropDatabase($name) {
        throw new Doctrine_Export_Firebird_Exception(
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
    public function _makeAutoincrement($name, $table, $start = null) {
        if (is_null($start)) {
            $db->beginTransaction();
            $query = 'SELECT MAX(' . $db->quoteIdentifier($name, true) . ') FROM ' . $db->quoteIdentifier($table, true);
            $start = $this->db->queryOne($query, 'integer');
            if (PEAR::isError($start)) {
                return $start;
            }
            ++$start;
            $result = $db->manager->createSequence($table, $start);
            $db->commit();
        } else {
            $result = $db->manager->createSequence($table, $start);
        }

        $sequence_name = $db->getSequenceName($table);
        $trigger_name  = $db->quoteIdentifier($table . '_AUTOINCREMENT_PK', true);

        $table = $db->quoteIdentifier($table, true);
        $name  = $db->quoteIdentifier($name,  true);
        
        $trigger_sql = 'CREATE TRIGGER ' . $trigger_name . ' FOR ' . $table . '
                        ACTIVE BEFORE INSERT POSITION 0
                        AS
                        BEGIN
                        IF (NEW.' . $name . ' IS NULL OR NEW.' . $name . ' = 0) THEN
                            NEW.' . $name . ' = GEN_ID('.$sequence_name.', 1);
                        END';
        $result = $db->exec($trigger_sql);
        $this->_silentCommit();
        return $result;
    }
    /**
     * drop an existing autoincrement sequence + trigger
     *
     * @param string $table name of the table
     * @return void
     */
    public function _dropAutoincrement($table) {

        $result = $db->manager->dropSequence($table);
        
        if (PEAR::isError($result)) {
            return $db->raiseError(null, null, null,
                'sequence for autoincrement PK could not be dropped', __FUNCTION__);
        }
        //remove autoincrement trigger associated with the table
        $table = $db->quote(strtoupper($table), 'text');
        $trigger_name = $db->quote(strtoupper($table) . '_AUTOINCREMENT_PK', 'text');
        $result = $db->exec("DELETE FROM RDB\$TRIGGERS WHERE UPPER(RDB\$RELATION_NAME)=$table AND UPPER(RDB\$TRIGGER_NAME)=$trigger_name");
        if (PEAR::isError($result)) {
            return $db->raiseError(null, null, null,
                'trigger for autoincrement PK could not be dropped', __FUNCTION__);
        }
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
    public function createTable($name, $fields, $options = array()) {
        parent::createTable($name, $fields, $options);

        $this->_silentCommit();
        foreach($fields as $field_name => $field) {
            if( ! empty($field['autoincrement'])) {
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
    public function checkSupportedChanges(&$changes) {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        foreach ($changes as $change_name => $change) {
            switch ($change_name) {
            case 'notnull':
                return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                    'it is not supported changes to field not null constraint', __FUNCTION__);
            case 'default':
                return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                    'it is not supported changes to field default value', __FUNCTION__);
            case 'length':
                /*
                return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                    'it is not supported changes to field default length', __FUNCTION__);
                */
            case 'unsigned':
            case 'type':
            case 'declaration':
            case 'definition':
                break;
            default:
                return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                    'it is not supported change of type' . $change_name, __FUNCTION__);
            }
        }
        return MDB2_OK;
    }
    /**
     * drop an existing table
     *
     * @param string $name name of the table that should be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function dropTable($name) {
        $result = $this->_dropAutoincrement($name);
        if (PEAR::isError($result)) {
            return $result;
        }
        $result = parent::dropTable($name);
        $this->_silentCommit();
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
    public function alterTable($name, $changes, $check) {
        foreach ($changes as $change_name => $change) {
            switch ($change_name) {
            case 'add':
            case 'remove':
            case 'rename':
                break;
            case 'change':
                foreach ($changes['change'] as $field) {
                    if (PEAR::isError($err = $this->checkSupportedChanges($field))) {
                        return $err;
                    }
                }
                break;
            default:
                return $db->raiseError(MDB2_ERROR_CANNOT_ALTER, null, null,
                    'change type ' . $change_name . ' not yet supported', __FUNCTION__);
            }
        }
        if ($check) {
            return MDB2_OK;
        }
        $query = '';
        if (!empty($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $query.= 'ADD ' . $db->getDeclaration($field['type'], $field_name, $field, $name);
            }
        }

        if (!empty($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $field_name = $db->quoteIdentifier($field_name, true);
                $query.= 'DROP ' . $field_name;
            }
        }

        if (!empty($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $field_name = $db->quoteIdentifier($field_name, true);
                $query.= 'ALTER ' . $field_name . ' TO ' . $db->quoteIdentifier($field['name'], true);
            }
        }

        if (!empty($changes['change']) && is_array($changes['change'])) {
            // missing support to change DEFAULT and NULLability
            foreach ($changes['change'] as $field_name => $field) {
                if (PEAR::isError($err = $this->checkSupportedChanges($field))) {
                    return $err;
                }
                if ($query) {
                    $query.= ', ';
                }
                $db->loadModule('Datatype', null, true);
                $field_name = $db->quoteIdentifier($field_name, true);
                $query.= 'ALTER ' . $field_name.' TYPE ' . $db->datatype->getTypeDeclaration($field['definition']);
            }
        }

        if (!strlen($query)) {
            return MDB2_OK;
        }

        $name = $db->quoteIdentifier($name, true);
        $result = $db->exec("ALTER TABLE $name $query");
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
    public function createIndex($table, $name, $definition) {
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
        $table = $db->quoteIdentifier($table, true);
        $name  = $db->quoteIdentifier($db->getIndexName($name), true);
        $query .= $query_sort. " INDEX $name ON $table";
        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $db->quoteIdentifier($field, true);
        }
        $query .= ' ('.implode(', ', $fields) . ')';
        $result = $db->exec($query);
        $this->_silentCommit();
        return $result;
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
    public function createConstraint($table, $name, $definition) {
        $table = $db->quoteIdentifier($table, true);

        if (!empty($name)) {
            $name = $db->quoteIdentifier($db->getIndexName($name), true);
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
            $fields[] = $db->quoteIdentifier($field, true);
        }
        $query .= ' ('. implode(', ', $fields) . ')';
        $result = $db->exec($query);
        $this->_silentCommit();
        return $result;
    }
    /**
     * create sequence
     *
     * @param string $seq_name name of the sequence to be created
     * @param string $start start value of the sequence; default is 1
     * @return void
     */
    public function createSequence($seq_name, $start = 1) {
        $sequence_name = $db->getSequenceName($seq_name);
        if (PEAR::isError($result = $db->exec('CREATE GENERATOR '.$sequence_name))) {
            return $result;
        }
        if (PEAR::isError($result = $db->exec('SET GENERATOR '.$sequence_name.' TO '.($start-1)))) {
            if (PEAR::isError($err = $db->dropSequence($seq_name))) {
                return $db->raiseError($result, null, null,
                    'Could not setup sequence start value and then it was not possible to drop it', __FUNCTION__);
            }
        }
        return $result;
    }
    /**
     * drop existing sequence
     *
     * @param string $seq_name name of the sequence to be dropped
     * @return void
     */
    public function dropSequence($seq_name) {
        $sequence_name = $db->getSequenceName($seq_name);
        $sequence_name = $db->quote($sequence_name, 'text');
        $query = "DELETE FROM RDB\$GENERATORS WHERE UPPER(RDB\$GENERATOR_NAME)=$sequence_name";
        return $db->exec($query);
    }
}
