<?php
/*
 *  $Id: Mysql.php 2277 2007-08-27 14:43:52Z Jonathan.Wage $
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
 * @version     $Revision: 2277 $
 */
class Doctrine_Export_Mysql extends Doctrine_Export
{
   /**
     * create a new database
     *
     * @param string $name name of the database that should be created
     * @return string
     */
    public function createDatabaseSql($name)
    {
        return 'CREATE DATABASE ' . $this->conn->quoteIdentifier($name, true);
    }
    /**
     * drop an existing database
     *
     * @param string $name name of the database that should be dropped
     * @return string
     */
    public function dropDatabaseSql($name)
    {
        return 'DROP DATABASE ' . $this->conn->quoteIdentifier($name);
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
     *                              'charset' => 'utf8',
     *                              'collate' => 'utf8_unicode_ci',
     *                              'type'    => 'innodb',
     *                          );
     *
     * @return void
     */
    public function createTableSql($name, array $fields, array $options = array(), $exportForeignKeySql = true) 
    {
        if ( ! $name)
            throw new Doctrine_Export_Exception('no valid table name specified');

        if (empty($fields)) {
            throw new Doctrine_Export_Exception('no fields specified for table "'.$name.'"');
        }
        $queryFields = $this->getFieldDeclarationList($fields);
        
        // build indexes for all foreign key fields (needed in MySQL!!)
        if (isset($options['foreignKeys'])) {
            foreach ($options['foreignKeys'] as $fk) {
                $local = $fk['local'];
                $found = false;
                if (isset($options['indexes'])) {
                    foreach ($options['indexes'] as $definition) {
                        if (is_string($definition['fields'])) {
                            // Check if index already exists on the column                            
                            $found = ($local == $definition['fields']);                        
                        } else if (in_array($local, $definition['fields']) && count($definition['fields']) === 1) {
                            // Index already exists on the column
                            $found = true;
                        }
                    }
                }
                if (isset($options['primary']) && !empty($options['primary']) &&
                        in_array($local, $options['primary'])) {
                    // field is part of the PK and therefore already indexed
                    $found = true;
                }
                
                if ( ! $found) {
                    $options['indexes'][$local] = array('fields' => array($local => array()));
                }
            }
        }

        // add all indexes
        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach($options['indexes'] as $index => $definition) {
                $queryFields .= ', ' . $this->getIndexDeclaration($index, $definition);
            }
        }

        // attach all primary keys
        if (isset($options['primary']) && ! empty($options['primary'])) {
            $queryFields .= ', PRIMARY KEY(' . implode(', ', array_values($options['primary'])) . ')';
        }

        $query = 'CREATE TABLE ' . $this->conn->quoteIdentifier($name, true) . ' (' . $queryFields . ')';

        $optionStrings = array();

        if (isset($options['comment'])) {
            $optionStrings['comment'] = 'COMMENT = ' . $this->dbh->quote($options['comment'], 'text');
        }
        if (isset($options['charset'])) {
            $optionStrings['charset'] = 'DEFAULT CHARACTER SET ' . $options['charset'];
            if (isset($options['collate'])) {
                $optionStrings['charset'] .= ' COLLATE ' . $options['collate'];
            }
        }

        $type = false;

        // get the type of the table
        if (isset($options['type'])) {
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
        $sql[] = $query;

        if (isset($options['foreignKeys']) && $exportForeignKeySql) {

            foreach ((array) $options['foreignKeys'] as $k => $definition) {
                if (is_array($definition)) {
                    $sql[] = $this->createForeignKeySql($name, $definition);
                }
            }
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
     *                           can perform the requested table alterations if the value is true or
     *                           actually perform them otherwise.
     * @return boolean
     */
    public function alterTableSql($name, array $changes, $check)
    {
        if ( ! $name) {
            throw new Doctrine_Export_Exception('no valid table name specified');
        }
        foreach ($changes as $changeName => $change) {
            switch ($changeName) {
                case 'add':
                case 'remove':
                case 'change':
                case 'rename':
                case 'name':
                    break;
                default:
                    throw new Doctrine_Export_Exception('change type "' . $changeName . '" not yet supported');
            }
        }

        if ($check) {
            return true;
        }

        $query = '';
        if ( ! empty($changes['name'])) {
            $change_name = $this->conn->quoteIdentifier($changes['name']);
            $query .= 'RENAME TO ' . $change_name;
        }

        if ( ! empty($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $fieldName => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $query.= 'ADD ' . $this->getDeclaration($field['type'], $fieldName, $field);
            }
        }

        if ( ! empty($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $fieldName => $field) {
                if ($query) {
                    $query .= ', ';
                }
                $fieldName = $this->conn->quoteIdentifier($fieldName);
                $query .= 'DROP ' . $fieldName;
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
                $oldFieldName = $this->conn->quoteIdentifier($oldFieldName, true);
                $query .= 'CHANGE ' . $oldFieldName . ' ' 
                        . $this->getDeclaration($field['definition']['type'], $fieldName, $field['definition']);
            }
        }

        if ( ! empty($rename) && is_array($rename)) {
            foreach ($rename as $renameName => $renamedField) {
                if ($query) {
                    $query.= ', ';
                }
                $field = $changes['rename'][$renamedField];
                $renamedField = $this->conn->quoteIdentifier($renamedField, true);
                $query .= 'CHANGE ' . $renamedField . ' '
                        . $this->getDeclaration($field['definition']['type'], $field['name'], $field['definition']);
            }
        }

        if ( ! $query) {
            return false;
        }

        $name = $this->conn->quoteIdentifier($name, true);
        return $this->conn->exec('ALTER TABLE ' . $name . ' ' . $query);
    }
    /**
     * create sequence
     *
     * @param string    $sequenceName name of the sequence to be created
     * @param string    $start        start value of the sequence; default is 1
     * @param array     $options  An associative array of table options:
     *                          array(
     *                              'comment' => 'Foo',
     *                              'charset' => 'utf8',
     *                              'collate' => 'utf8_unicode_ci',
     *                              'type'    => 'innodb',
     *                          );
     * @return boolean
     */
    public function createSequence($sequenceName, $start = 1, array $options = array())
    {
        $sequenceName   = $this->conn->quoteIdentifier($this->conn->getSequenceName($sequenceName), true);
        $seqcolName     = $this->conn->quoteIdentifier($this->conn->getAttribute(Doctrine::ATTR_SEQCOL_NAME), true);

        $optionsStrings = array();

        if (isset($options['comment']) && ! empty($options['comment'])) {
            $optionsStrings['comment'] = 'COMMENT = ' . $this->conn->quote($options['comment'], 'string');
        }

        if (isset($options['charset']) && ! empty($options['charset'])) {
            $optionsStrings['charset'] = 'DEFAULT CHARACTER SET ' . $options['charset'];

            if (isset($options['collate'])) {
                $optionsStrings['collate'] .= ' COLLATE ' . $options['collate'];
            }
        }

        $type = false;

        if (isset($options['type'])) {
            $type = $options['type'];
        } else {
            $type = $this->conn->default_table_type;
        }
        if ($type) {
            $optionsStrings[] = 'ENGINE = ' . $type;
        }


        try {
            $query  = 'CREATE TABLE ' . $sequenceName
                    . ' (' . $seqcolName . ' INT NOT NULL AUTO_INCREMENT, PRIMARY KEY ('
                    . $seqcolName . '))'
                    . strlen($this->conn->default_table_type) ? ' TYPE = '
                    . $this->conn->default_table_type : '';

            $res    = $this->conn->exec($query);
        } catch(Doctrine_Connection_Exception $e) {
            throw new Doctrine_Export_Exception('could not create sequence table');
        }

        if ($start == 1)
            return true;

        $query  = 'INSERT INTO ' . $sequenceName
                . ' (' . $seqcolName . ') VALUES (' . ($start - 1) . ')';

        $res    = $this->conn->exec($query);

        // Handle error
        try {
            $result = $this->conn->exec('DROP TABLE ' . $sequenceName);
        } catch(Doctrine_Connection_Exception $e) {
            throw new Doctrine_Export_Exception('could not drop inconsistent sequence table');
        }


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
     *                                                'sorting' => 'ASC'
     *                                                'length' => 10
     *                                            ),
     *                                            'last_login' => array()
     *                                        )
     *                                    )
     * @throws PDOException
     * @return void
     */
    public function createIndexSql($table, $name, array $definition)
    {
        $table  = $table;
        $name   = $this->conn->getIndexName($name);
        $type   = '';
        if (isset($definition['type'])) {
            switch (strtolower($definition['type'])) {
                case 'fulltext':
                case 'unique':
                    $type = strtoupper($definition['type']) . ' ';
                break;
                default:
                    throw new Doctrine_Export_Exception('Unknown index type ' . $definition['type']);
            }
        }
        $query  = 'CREATE ' . $type . 'INDEX ' . $name . ' ON ' . $table;
        $query .= ' (' . $this->getIndexFieldDeclarationList() . ')';

        return $query;
    }
    /** 
     * getDefaultDeclaration
     * Obtain DBMS specific SQL code portion needed to set a default value
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param array $field      field definition array
     * @return string           DBMS specific SQL code portion needed to set a default value
     */
    public function getDefaultFieldDeclaration($field)
    {
        $default = '';
        if (isset($field['default']) && $field['length'] <= 255) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull'])
                    ? null : $this->valid_default_values[$field['type']];

                if ($field['default'] === ''
                    && ($conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_EMPTY_TO_NULL)
                ) {
                    $field['default'] = ' ';
                }
            }
    
            $default = ' DEFAULT ' . $this->conn->quote($field['default'], $field['type']);
        }
        return $default;
    }
    /**
     * Obtain DBMS specific SQL code portion needed to set an index 
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param string $charset       name of the index
     * @param array $definition     index definition
     * @return string  DBMS specific SQL code portion needed to set an index
     */
    public function getIndexDeclaration($name, array $definition)
    {
        $name   = $this->conn->quoteIdentifier($name);
        $type   = '';
        if(isset($definition['type'])) {
            switch (strtolower($definition['type'])) {
                case 'fulltext':
                case 'unique':
                    $type = strtoupper($definition['type']) . ' ';
                break;
                default:
                    throw new Doctrine_Export_Exception('Unknown index type ' . $definition['type']);
            }
        }
        
        if ( ! isset($definition['fields'])) {
            throw new Doctrine_Export_Exception('No index columns given.');
        }
        if ( ! is_array($definition['fields'])) {
            $definition['fields'] = array($definition['fields']);
        }

        $query = $type . 'INDEX ' . $this->conn->formatter->getIndexName($name);

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
            $fieldString = $fieldName;

            if (is_array($field)) {
                if (isset($field['length'])) {
                    $fieldString .= '(' . $field['length'] . ')';
                }

                if (isset($field['sorting'])) {
                    $sort = strtoupper($field['sorting']);
                    switch ($sort) {
                        case 'ASC':
                        case 'DESC':
                            $fieldString .= ' ' . $sort;
                            break;
                        default:
                            throw new Doctrine_Export_Exception('Unknown index sorting option given.');
                    }
                }
            } else {
                $fieldString = $field;
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
     * @param array $definition
     * @return string
     */
    public function getAdvancedForeignKeyOptions(array $definition)
    {
        $query = '';
        if (!empty($definition['match'])) {
            $query .= ' MATCH ' . $definition['match'];
        }
        if (!empty($definition['onUpdate'])) {
            $query .= ' ON UPDATE ' . $this->getForeignKeyReferentialAction($definition['onUpdate']);
        }
        if (!empty($definition['onDelete'])) {
            $query .= ' ON DELETE ' . $this->getForeignKeyReferentialAction($definition['onDelete']);
        }
        return $query;
    }
    /**
     * drop existing index
     *
     * @param string    $table          name of table that should be used in method
     * @param string    $name           name of the index to be dropped
     * @return void
     */
    public function dropIndexSql($table, $name)
    {
        $table  = $this->conn->quoteIdentifier($table, true);
        $name   = $this->conn->quoteIdentifier($this->conn->formatter->getIndexName($name), true);
        return 'DROP INDEX ' . $name . ' ON ' . $table;
    }
    /**
     * dropTable
     *
     * @param string    $table          name of table that should be dropped from the database
     * @throws PDOException
     * @return void
     */
    public function dropTableSql($table)
    {
        $table  = $this->conn->quoteIdentifier($table, true);
        return 'DROP TABLE ' . $table;
    }
}

