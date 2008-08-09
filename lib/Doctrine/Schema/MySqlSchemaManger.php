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
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine::DBAL::Schema;

/**
 * xxx
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision$
 * @since       2.0
 */
class Doctrine_Schema_MySqlSchemaManager extends Doctrine_Schema_SchemaManager
{
    protected $_sql  = array(
                            'showDatabases'   => 'SHOW DATABASES',
                            'listTableFields' => 'DESCRIBE %s',
                            'listSequences'   => 'SHOW TABLES',
                            'listTables'      => 'SHOW TABLES',
                            'listUsers'       => 'SELECT DISTINCT USER FROM USER',
                            'listViews'       => "SHOW FULL TABLES %s WHERE Table_type = 'VIEW'",
                            );
    
    public function __construct(Doctrine_Connection_MySql $conn)
    {
        $this->_conn = $conn;
    }

    /**
     * lists all database sequences
     *
     * @param string|null $database
     * @return array
     * @override
     */
    public function listSequences($database = null)
    {
        $query = 'SHOW TABLES';
        if ( ! is_null($database)) {
            $query .= ' FROM ' . $database;
        }
        $tableNames = $this->_conn->fetchColumn($query);

        return array_map(array($this->_conn->formatter, 'fixSequenceName'), $tableNames);
    }

    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     * @override
     */
    public function listTableConstraints($table)
    {
        $keyName = 'Key_name';
        $nonUnique = 'Non_unique';
        if ($this->_conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            if ($this->_conn->getAttribute(Doctrine::ATTR_FIELD_CASE) == CASE_LOWER) {
                $keyName = strtolower($keyName);
                $nonUnique = strtolower($nonUnique);
            } else {
                $keyName = strtoupper($keyName);
                $nonUnique = strtoupper($nonUnique);
            }
        }

        $table = $this->_conn->quoteIdentifier($table, true);
        $query = 'SHOW INDEX FROM ' . $table;
        $indexes = $this->_conn->fetchAssoc($query);

        $result = array();
        foreach ($indexes as $indexData) {
            if ( ! $indexData[$nonUnique]) {
                if ($indexData[$keyName] !== 'PRIMARY') {
                    $index = $this->_conn->formatter->fixIndexName($indexData[$keyName]);
                } else {
                    $index = 'PRIMARY';
                }
                if ( ! empty($index)) {
                    $result[] = $index;
                }
            }
        }
        return $result;
    }

    /**
     * lists table foreign keys
     *
     * @param string $table     database table name
     * @return array
     * @override
     */
    public function listTableForeignKeys($table)
    {
        $sql = 'SHOW CREATE TABLE ' . $this->_conn->quoteIdentifier($table, true);
        $definition = $this->_conn->fetchOne($sql);
        if (!empty($definition)) {
            $pattern = '/\bCONSTRAINT\s+([^\s]+)\s+FOREIGN KEY\b/i';
            if (preg_match_all($pattern, str_replace('`', '', $definition), $matches) > 1) {
                foreach ($matches[1] as $constraint) {
                    $result[$constraint] = true;
                }
            }
        }

        if ($this->_conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            $result = array_change_key_case($result, $this->_conn->getAttribute(Doctrine::ATTR_FIELD_CASE));
        }

        return $result;
    }

    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     * @override
     */
    public function listTableColumns($table)
    {
        $sql = 'DESCRIBE ' . $this->_conn->quoteIdentifier($table, true);
        $result = $this->_conn->fetchAssoc($sql);

        $description = array();
        $columns = array();
        foreach ($result as $key => $val) {

            $val = array_change_key_case($val, CASE_LOWER);

            $decl = $this->_conn->dataDict->getPortableDeclaration($val);

            $values = isset($decl['values']) ? $decl['values'] : array();

            $description = array(
                          'name'          => $val['field'],
                          'type'          => $decl['type'][0],
                          'alltypes'      => $decl['type'],
                          'ntype'         => $val['type'],
                          'length'        => $decl['length'],
                          'fixed'         => $decl['fixed'],
                          'unsigned'      => $decl['unsigned'],
                          'values'        => $values,
                          'primary'       => (strtolower($val['key']) == 'pri'),
                          'default'       => $val['default'],
                          'notnull'       => (bool) ($val['null'] != 'YES'),
                          'autoincrement' => (bool) (strpos($val['extra'], 'auto_increment') !== false),
                          );
            $columns[$val['field']] = $description;
        }

        return $columns;
    }

    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     * @override
     */
    public function listTableIndexes($table)
    {
        $keyName = 'Key_name';
        $nonUnique = 'Non_unique';
        if ($this->_conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            if ($this->_conn->getAttribute(Doctrine::ATTR_FIELD_CASE) == CASE_LOWER) {
                $keyName = strtolower($keyName);
                $nonUnique = strtolower($nonUnique);
            } else {
                $keyName = strtoupper($keyName);
                $nonUnique = strtoupper($nonUnique);
            }
        }

        $table = $this->_conn->quoteIdentifier($table, true);
        $query = 'SHOW INDEX FROM ' . $table;
        $indexes = $this->_conn->fetchAssoc($query);


        $result = array();
        foreach ($indexes as $indexData) {
            if ($indexData[$nonUnique] && ($index = $this->_conn->formatter->fixIndexName($indexData[$keyName]))) {
                $result[] = $index;
            }
        }
        return $result;
    }

    /**
     * lists tables
     *
     * @param string|null $database
     * @return array
     * @override
     */
    public function listTables($database = null)
    {
        return $this->_conn->fetchColumn($this->sql['listTables']);
    }

    /**
     * lists database views
     *
     * @param string|null $database
     * @return array
     * @override
     */
    public function listViews($database = null)
    {
        if ( ! is_null($database)) {
            $query = sprintf($this->sql['listViews'], ' FROM ' . $database);
        }

        return $this->_conn->fetchColumn($query);
    }
    
    /**
     * create a new database
     *
     * @param string $name name of the database that should be created
     * @return string
     * @override
     */
    public function createDatabaseSql($name)
    {
        return 'CREATE DATABASE ' . $this->_conn->quoteIdentifier($name, true);
    }

    /**
     * drop an existing database
     *
     * @param string $name name of the database that should be dropped
     * @return string
     * @override
     */
    public function dropDatabaseSql($name)
    {
        return 'DROP DATABASE ' . $this->_conn->quoteIdentifier($name);
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
     * @override
     */
    public function createTableSql($name, array $fields, array $options = array())
    {
        if ( ! $name) {
            throw new Doctrine_Export_Exception('no valid table name specified');
        }

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
            $keyColumns = array_values($options['primary']);
            $keyColumns = array_map(array($this->_conn, 'quoteIdentifier'), $keyColumns);
            $queryFields .= ', PRIMARY KEY(' . implode(', ', $keyColumns) . ')';
        }

        $query = 'CREATE ';
        if (!empty($options['temporary'])) {
            $query .= 'TEMPORARY ';
        }
        $query.= 'TABLE ' . $this->_conn->quoteIdentifier($name, true) . ' (' . $queryFields . ')';

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
            $type = $this->_conn->getAttribute(Doctrine::ATTR_DEFAULT_TABLE_TYPE);
        }

        if ($type) {
            $optionStrings[] = 'ENGINE = ' . $type;
        }

        if ( ! empty($optionStrings)) {
            $query.= ' '.implode(' ', $optionStrings);
        }
        $sql[] = $query;

        if (isset($options['foreignKeys'])) {

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
     * @override
     */
    public function alterTableSql($name, array $changes, $check = false)
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

        if ( ! empty($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $fieldName => $field) {
                if ($query) {
                    $query .= ', ';
                }
                $fieldName = $this->_conn->quoteIdentifier($fieldName);
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
     * @override
     */
    public function createSequence($sequenceName, $start = 1, array $options = array())
    {
        $sequenceName   = $this->_conn->quoteIdentifier($this->_conn->getSequenceName($sequenceName), true);
        $seqcolName     = $this->_conn->quoteIdentifier($this->_conn->getAttribute(Doctrine::ATTR_SEQCOL_NAME), true);

        $optionsStrings = array();

        if (isset($options['comment']) && ! empty($options['comment'])) {
            $optionsStrings['comment'] = 'COMMENT = ' . $this->_conn->quote($options['comment'], 'string');
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
            $type = $this->_conn->default_table_type;
        }
        if ($type) {
            $optionsStrings[] = 'ENGINE = ' . $type;
        }

        try {
            $query  = 'CREATE TABLE ' . $sequenceName
                    . ' (' . $seqcolName . ' INT NOT NULL AUTO_INCREMENT, PRIMARY KEY ('
                    . $seqcolName . '))';

            if (!empty($options_strings)) {
                $query .= ' '.implode(' ', $options_strings);
            }

            $res    = $this->_conn->exec($query);
        } catch(Doctrine_Connection_Exception $e) {
            throw new Doctrine_Export_Exception('could not create sequence table');
        }

        if ($start == 1) {
            return true;
       }

        $query  = 'INSERT INTO ' . $sequenceName
                . ' (' . $seqcolName . ') VALUES (' . ($start - 1) . ')';

        $res    = $this->_conn->exec($query);

      // Handle error
      try {
          $res = $this->_conn->exec('DROP TABLE ' . $sequenceName);
      } catch(Doctrine_Connection_Exception $e) {
          throw new Doctrine_Export_Exception('could not drop inconsistent sequence table');
      }

      return $res;
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
     * @override
     */
    public function createIndexSql($table, $name, array $definition)
    {
        $table  = $table;
        $name   = $this->_conn->formatter->getIndexName($name);
        $name   = $this->_conn->quoteIdentifier($name);
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
        $query .= ' (' . $this->getIndexFieldDeclarationList($definition['fields']) . ')';

        return $query;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
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
     * @override
     */
    public function getIntegerDeclaration($name, $field)
    {
        $default = $autoinc = '';
        if ( ! empty($field['autoincrement'])) {
            $autoinc = ' AUTO_INCREMENT';
        } elseif (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull']) ? null : 0;
            }
            if (is_null($field['default'])) {
                $default = ' DEFAULT NULL';
            } else {
                $default = ' DEFAULT '.$this->_conn->quote($field['default']);
            }
        } elseif (empty($field['notnull'])) {
            $default = ' DEFAULT NULL';
        }

        $notnull  = (isset($field['notnull'])  && $field['notnull'])  ? ' NOT NULL' : '';
        $unsigned = (isset($field['unsigned']) && $field['unsigned']) ? ' UNSIGNED' : '';

        $name = $this->_conn->quoteIdentifier($name, true);

        return $name . ' ' . $this->_conn->dataDict->getNativeDeclaration($field) . $unsigned . $default . $notnull . $autoinc;
    }

    /**
     * getDefaultDeclaration
     * Obtain DBMS specific SQL code portion needed to set a default value
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param array $field      field definition array
     * @return string           DBMS specific SQL code portion needed to set a default value
     * @override
     */
    public function getDefaultFieldDeclaration($field)
    {
        $default = empty($field['notnull']) && !in_array($field['type'], array('clob', 'blob'))
            ? ' DEFAULT NULL' : '';

        if (isset($field['default']) && ( ! isset($field['length']) || $field['length'] <= 255)) {
            if ($field['default'] === '') {
                $field['default'] = null;
                if (! empty($field['notnull']) && array_key_exists($field['type'], $this->valid_default_values)) {
                   $field['default'] = $this->valid_default_values[$field['type']];
                }

                if ($field['default'] === ''
                    && ($this->_conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_EMPTY_TO_NULL)
                ) {
                    $field['default'] = ' ';
                }
            }

            if ($field['type'] == 'enum' && $this->_conn->getAttribute(Doctrine::ATTR_USE_NATIVE_ENUM)) {
                $fieldType = 'varchar';
            } else {
               if ($field['type'] === 'boolean') {
                   $fields['default'] = $this->_conn->convertBooleans($field['default']);
               }
                $fieldType = $field['type'];
            }

            $default = ' DEFAULT ' . $this->_conn->quote($field['default'], $fieldType);
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
     * @override
     */
    public function getIndexDeclaration($name, array $definition)
    {
        $name   = $this->_conn->formatter->getIndexName($name);
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

        if ( ! isset($definition['fields'])) {
            throw new Doctrine_Export_Exception('No index columns given.');
        }
        if ( ! is_array($definition['fields'])) {
            $definition['fields'] = array($definition['fields']);
        }

        $query = $type . 'INDEX ' . $this->_conn->quoteIdentifier($name);

        $query .= ' (' . $this->getIndexFieldDeclarationList($definition['fields']) . ')';

        return $query;
    }

    /**
     * getIndexFieldDeclarationList
     * Obtain DBMS specific SQL code portion needed to set an index
     * declaration to be used in statements like CREATE TABLE.
     *
     * @return string
     * @override
     */
    public function getIndexFieldDeclarationList(array $fields)
    {
        $declFields = array();

        foreach ($fields as $fieldName => $field) {
            $fieldString = $this->_conn->quoteIdentifier($fieldName);

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
     * @param array $definition
     * @return string
     * @override
     */
    public function getAdvancedForeignKeyOptions(array $definition)
    {
        $query = '';
        if ( ! empty($definition['match'])) {
            $query .= ' MATCH ' . $definition['match'];
        }
        if ( ! empty($definition['onUpdate'])) {
            $query .= ' ON UPDATE ' . $this->getForeignKeyReferentialAction($definition['onUpdate']);
        }
        if ( ! empty($definition['onDelete'])) {
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
     * @override
     */
    public function dropIndexSql($table, $name)
    {
        $table  = $this->_conn->quoteIdentifier($table, true);
        $name   = $this->_conn->quoteIdentifier($this->_conn->formatter->getIndexName($name), true);
        return 'DROP INDEX ' . $name . ' ON ' . $table;
    }

    /**
     * dropTable
     *
     * @param string    $table          name of table that should be dropped from the database
     * @throws PDOException
     * @return void
     * @override
     */
    public function dropTableSql($table)
    {
        $table  = $this->_conn->quoteIdentifier($table, true);
        return 'DROP TABLE ' . $table;
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $table
     * @param unknown_type $name
     * @return unknown
     * @override
     */
    public function dropForeignKey($table, $name)
    {
        $table = $this->_conn->quoteIdentifier($table);
        $name  = $this->_conn->quoteIdentifier($name);
        return $this->_conn->exec('ALTER TABLE ' . $table . ' DROP FOREIGN KEY ' . $name);
    }
    
}

?>