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
 * Doctrine_Export_Mysql
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Export_Mysql extends Doctrine_Export
{
   /**
     * create a new database
     *
     * @param string $name name of the database that should be created
     * @throws PDOException
     * @return void
     */
    public function createDatabase($name)
    {
        $query  = 'CREATE DATABASE ' . $this->conn->quoteIdentifier($name, true);
        $result = $this->conn->exec($query);
    }
    /**
     * drop an existing database
     *
     * @param string $name name of the database that should be dropped
     * @throws PDOException
     * @access public
     */
    public function dropDatabase($name)
    {
        $query  = 'DROP DATABASE ' . $this->conn->quoteIdentifier($name);
        $this->conn->exec($query);
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
     *                          array(
     *                              'comment' => 'Foo',
     *                              'character_set' => 'utf8',
     *                              'collate' => 'utf8_unicode_ci',
     *                              'collate' => 'utf8_unicode_ci',
     *                              'type'    => 'innodb',
     *                          );
     *
     * @return void
     */
    public function createTable($name, array $fields, array $options = array()) {
        if ( ! $name)
            throw new Doctrine_Export_Mysql_Exception('no valid table name specified');

        if (empty($fields)) {
            throw new Doctrine_Export_Mysql_Exception('no fields specified for table "'.$name.'"');
        }
        $query_fields = $this->getFieldDeclarationList($fields);

        if (isset($options['primary']) && ! empty($options['primary'])) {
            $query_fields.= ', PRIMARY KEY(' . implode(', ', array_values($options['primary'])) . ')';
        }
        $name  = $this->conn->quoteIdentifier($name, true);
        $query = 'CREATE TABLE ' . $name . ' (' . $query_fields . ')';

        $optionStrings = array();

        if (isset($options['comment'])) {
            $optionStrings['comment'] = 'COMMENT = ' . $this->dbh->quote($options['comment'], 'text');
        }
        if (isset($options['charset'])) {
            $optionsSting['charset'] = 'DEFAULT CHARACTER SET ' . $options['charset'];
            if (isset($options['collate'])) {
                $optionStrings['charset'].= ' COLLATE ' . $options['collate'];
            }
        }

        $type = false;

        if (!empty($options['type'])) {
            $type = $options['type'];
        } else {
            $type = $this->conn->getAttribute(Doctrine::ATTR_DEFAULT_TABLE_TYPE);
        }

        if ($type) {
            $optionStrings[] = 'ENGINE = ' . $type;
        }

        if (!empty($optionStrings)) {
            $query.= ' '.implode(' ', $optionStrings);
        }
        return $this->conn->exec($query);
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
     *                           can perform the requested table alterations if the value is true or
     *                           actually perform them otherwise.
     * @return boolean
     */
    public function alterTable($name, array $changes, $check)
    {
        if ( ! $name)
            throw new Doctrine_Export_Mysql_Exception('no valid table name specified');

        foreach ($changes as $changeName => $change) {
            switch ($changeName) {
                case 'add':
                case 'remove':
                case 'change':
                case 'rename':
                case 'name':
                    break;
                default:
                    throw new Doctrine_Export_Mysql_Exception('change type "'.$changeName.'" not yet supported');
            }
        }

        if ($check) {
            return true;
        }

        $query = '';
        if ( ! empty($changes['name'])) {
            $change_name = $this->conn->quoteIdentifier($changes['name'], true);
            $query .= 'RENAME TO ' . $change_name;
        }

        if ( ! empty($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $query.= 'ADD ' . $this->dbh->getDeclaration($field['type'], $field_name, $field);
            }
        }

        if ( ! empty($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $field_name = $this->conn->quoteIdentifier($field_name, true);
                $query.= 'DROP ' . $field_name;
            }
        }

        $rename = array();
        if ( ! empty($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $field_name => $field) {
                $rename[$field['name']] = $field_name;
            }
        }

        if ( ! empty($changes['change']) && is_array($changes['change'])) {
            foreach ($changes['change'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                if (isset($rename[$field_name])) {
                    $old_field_name = $rename[$field_name];
                    unset($rename[$field_name]);
                } else {
                    $old_field_name = $field_name;
                }
                $old_field_name = $this->conn->quoteIdentifier($old_field_name, true);
                $query.= "CHANGE $old_field_name " . $this->dbh->getDeclaration($field['definition']['type'], $field_name, $field['definition']);
            }
        }

        if ( ! empty($rename) && is_array($rename)) {
            foreach ($rename as $rename_name => $renamed_field) {
                if ($query) {
                    $query.= ', ';
                }
                $field = $changes['rename'][$renamed_field];
                $renamed_field = $this->conn->quoteIdentifier($renamed_field, true);
                $query.= 'CHANGE ' . $renamed_field . ' ' . $this->dbh->getDeclaration($field['definition']['type'], $field['name'], $field['definition']);
            }
        }

        if ( ! $query) {
            return false;
        }

        $name = $this->conn->quoteIdentifier($name, true);
        return $this->conn->exec("ALTER TABLE $name $query");
    }
    /**
     * create sequence
     *
     * @param string    $sequenceName name of the sequence to be created
     * @param string    $seqcol_name  the name of the sequence column
     * @param string    $start        start value of the sequence; default is 1
     * @return boolean
     */
    public function createSequence($sequenceName, $seqcolName, $start = 1)
    {
        $query  = 'CREATE TABLE ' . $sequenceName
                . ' (' . $seqcolName . ' INT NOT NULL AUTO_INCREMENT, PRIMARY KEY ('
                . $seqcol_name . '))'
                . strlen($this->conn->default_table_type) ? ' TYPE = '
                . $this->conn->default_table_type : '';

        $res    = $this->conn->exec($query);

        if ($start == 1)
            return true;

        $query  = 'INSERT INTO ' . $sequenceName
                . ' (' . $seqcol_name . ') VALUES (' . ($start - 1) . ')';

        $res    = $this->conn->exec($query);

        // Handle error
        try {
            $result = $this->conn->exec('DROP TABLE ' . $sequenceName);
        } catch(Exception $e) {
            throw new Doctrine_Export_Mysql_Exception('could not drop inconsistent sequence table');
        }

        throw new Doctrine_Mysql_Export_Exception('could not create sequence table');
    }
    /**
     * Get the stucture of a field into an array
     *
     * @author Leoncx
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
     *                                                'length' => 10
     *                                            ),
     *                                            'last_login' => array()
     *                                        )
     *                                    )
     * @throws PDOException
     * @return void
     */
    public function createIndex($table, $name, array $definition)
    {
        $table  = $table;
        $name   = $this->conn->getIndexName($name);
        $query  = 'CREATE INDEX ' . $name . ' ON ' . $table;
        $fields = array();
        foreach ($definition['fields'] as $field => $fieldinfo) {
            if (!empty($fieldinfo['length'])) {
                $fields[] = $field . '(' . $fieldinfo['length'] . ')';
            } else {
                $fields[] = $field;
            }
        }
        $query .= ' ('. implode(', ', $fields) . ')';

        return $this->conn->exec($query);
    }
    /**
     * drop existing index
     *
     * @param string    $table          name of table that should be used in method
     * @param string    $name           name of the index to be dropped
     * @return void
     */
    public function dropIndex($table, $name)
    {
        $table  = $this->conn->quoteIdentifier($table, true);
        $name   = $this->conn->quoteIdentifier($this->conn->getIndexName($name), true);
        return $this->conn->exec('DROP INDEX ' . $name . ' ON ' . $table);
    }
    /**
     * dropTable
     *
     * @param string    $table          name of table that should be dropped from the database
     * @throws PDOException
     * @return void
     */
    public function dropTable($table)
    {
        $table  = $this->conn->quoteIdentifier($table, true);
        $this->conn->exec('DROP TABLE ' . $table);
    }
}

