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

namespace Doctrine\DBAL\Platforms;

use Doctrine\Common\DoctrineException;

/**
 * The MySqlPlatform provides the behavior, features and SQL dialect of the
 * MySQL database platform.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
class MySqlPlatform extends AbstractPlatform
{    
    /**
     * Creates a new MySqlPlatform instance.
     */
    public function __construct()
    {
        parent::__construct();      
    }
    
    /**
     * Gets the character used for identifier quoting.
     *
     * @return string
     * @override
     */
    public function getIdentifierQuoteCharacter()
    {
        return '`';
    }
    
    /**
     * Returns the regular expression operator.
     *
     * @return string
     * @override
     */
    public function getRegexpExpression()
    {
        return 'RLIKE';
    }

    /**
     * return string to call a function to get random value inside an SQL statement
     *
     * @return string to generate float between 0 and 1
     */
    public function getRandomExpression()
    {
        return 'RAND()';
    }

    /**
     * Builds a pattern matching string.
     *
     * EXPERIMENTAL
     *
     * WARNING: this function is experimental and may change signature at
     * any time until labelled as non-experimental.
     *
     * @param array $pattern even keys are strings, odd are patterns (% and _)
     * @param string $operator optional pattern operator (LIKE, ILIKE and maybe others in the future)
     * @param string $field optional field name that is being matched against
     *                  (might be required when emulating ILIKE)
     *
     * @return string SQL pattern
     * @override
     */
    public function getMatchPatternExpression($pattern, $operator = null, $field = null)
    {
        $match = '';
        if ( ! is_null($operator)) {
            $field = is_null($field) ? '' : $field.' ';
            $operator = strtoupper($operator);
            switch ($operator) {
                // case insensitive
                case 'ILIKE':
                    $match = $field.'LIKE ';
                    break;
                // case sensitive
                case 'LIKE':
                    $match = $field.'LIKE BINARY ';
                    break;
                default:
                    throw DoctrineException::updateMe('not a supported operator type:'. $operator);
            }
        }
        $match.= "'";
        foreach ($pattern as $key => $value) {
            if ($key % 2) {
                $match .= $value;
            } else {
                $match .= $this->conn->escapePattern($this->conn->escape($value));
            }
        }
        $match.= "'";
        $match.= $this->patternEscapeString();
        
        return $match;
    }

    /**
     * Returns global unique identifier
     *
     * @return string to get global unique identifier
     * @override
     */
    public function getGuidExpression()
    {
        return 'UUID()';
    }

    /**
     * Returns a series of strings concatinated
     *
     * concat() accepts an arbitrary number of parameters. Each parameter
     * must contain an expression or an array with expressions.
     *
     * @param string|array(string) strings that will be concatinated.
     * @override
     */
    public function getConcatExpression()
    {
        $args = func_get_args();
        return 'CONCAT(' . join(', ', (array) $args) . ')';
    }
    
    /**
     * Gets the SQL snippet used to declare a VARCHAR column on the MySql platform.
     *
     * @params array $field
     */
    public function getVarcharTypeDeclarationSql(array $field)
    {
        if ( ! isset($field['length'])) {
            if (array_key_exists('default', $field)) {
                $field['length'] = $this->getVarcharMaxLength();
            } else {
                $field['length'] = false;
            }
        }

        $length = ($field['length'] <= $this->getVarcharMaxLength()) ? $field['length'] : false;
        $fixed = (isset($field['fixed'])) ? $field['fixed'] : false;

        return $fixed ? ($length ? 'CHAR(' . $length . ')' : 'CHAR(255)')
                : ($length ? 'VARCHAR(' . $length . ')' : 'TEXT');
    }
    
    /**
     * Enter description here...
     *
     * @param array $field
     */
    public function getClobDeclarationSql(array $field)
    {
        if ( ! empty($field['length'])) {
            $length = $field['length'];
            if ($length <= 255) {
                return 'TINYTEXT';
            } else if ($length <= 65532) {
                return 'TEXT';
            } else if ($length <= 16777215) {
                return 'MEDIUMTEXT';
            }
        }
        return 'LONGTEXT';
    }
    
    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param array $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      default
     *          Text value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     *
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @override
     */
    public function getNativeDeclaration(array $field)
    {
        /*if ( ! isset($field['type'])) {
            throw DoctrineException::updateMe('Missing column type.');
        }

        switch ($field['type']) {
            case 'char':
                $length = ( ! empty($field['length'])) ? $field['length'] : false;

                return $length ? 'CHAR('.$length.')' : 'CHAR(255)';
            case 'varchar':
            case 'array':
            case 'object':
            case 'string':
            case 'gzip':
                return $this->getVarcharTypeDeclarationSql($field);
            case 'clob':
                return $this->getClobDeclarationSql($field);
            case 'blob':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 255) {
                        return 'TINYBLOB';
                    } elseif ($length <= 65532) {
                        return 'BLOB';
                    } elseif ($length <= 16777215) {
                        return 'MEDIUMBLOB';
                    }
                }
                return 'LONGBLOB';
            case 'enum':
                if ($this->conn->getAttribute(Doctrine::ATTR_USE_NATIVE_ENUM)) {
                    $values = array();
                    foreach ($field['values'] as $value) {
                      $values[] = $this->conn->quote($value, 'varchar');
                    }
                    return 'ENUM('.implode(', ', $values).')';
                }
                // fall back to integer
            case 'integer':
            case 'int':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 1) {
                        return 'TINYINT';
                    } elseif ($length == 2) {
                        return 'SMALLINT';
                    } elseif ($length == 3) {
                        return 'MEDIUMINT';
                    } elseif ($length == 4) {
                        return 'INT';
                    } elseif ($length > 4) {
                        return 'BIGINT';
                    }
                }
                return 'INT';
            case 'boolean':
                return 'TINYINT(1)';
            case 'date':
                return 'DATE';
            case 'time':
                return 'TIME';
            case 'timestamp':
                return 'DATETIME';
            case 'float':
            case 'double':
                return 'DOUBLE';
            case 'decimal':
                $length = !empty($field['length']) ? $field['length'] : 18;
                $scale = !empty($field['scale']) ? $field['scale'] : $this->conn->getAttribute(Doctrine::ATTR_DECIMAL_PLACES);
                return 'DECIMAL('.$length.','.$scale.')';
        }*/
        throw DoctrineException::updateMe('Unknown field type \'' . $field['type'] .  '\'.');
    }

    /**
     * Maps a native array description of a field to a Doctrine datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     * @override
     */
    public function getPortableDeclaration(array $field)
    {
        /*$dbType = strtolower($field['type']);
        $dbType = strtok($dbType, '(), ');
        if ($dbType == 'national') {
            $dbType = strtok('(), ');
        }
        if (isset($field['length'])) {
            $length = $field['length'];
            $decimal = '';
        } else {
            $length = strtok('(), ');
            $decimal = strtok('(), ');
        }
        $type = array();
        $unsigned = $fixed = null;

        if ( ! isset($field['name'])) {
            $field['name'] = '';
        }

        $values = null;

        switch ($dbType) {
            case 'tinyint':
                $type[] = 'integer';
                $type[] = 'boolean';
                if (preg_match('/^(is|has)/', $field['name'])) {
                    $type = array_reverse($type);
                }
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 1;
            break;
            case 'smallint':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 2;
            break;
            case 'mediumint':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 3;
            break;
            case 'int':
            case 'integer':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 4;
            break;
            case 'bigint':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 8;
            break;
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
            case 'text':
            case 'text':
            case 'varchar':
                $fixed = false;
            case 'string':
            case 'char':
                $type[] = 'string';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                } elseif (strstr($dbType, 'text')) {
                    $type[] = 'clob';
                    if ($decimal == 'binary') {
                        $type[] = 'blob';
                    }
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
            break;
            case 'enum':
                $type[] = 'enum';
                preg_match_all('/\'((?:\'\'|[^\'])*)\'/', $field['type'], $matches);
                $length = 0;
                $fixed = false;
                if (is_array($matches)) {
                    foreach ($matches[1] as &$value) {
                        $value = str_replace('\'\'', '\'', $value);
                        $length = max($length, strlen($value));
                    }
                    if ($length == '1' && count($matches[1]) == 2) {
                        $type[] = 'boolean';
                        if (preg_match('/^(is|has)/', $field['name'])) {
                            $type = array_reverse($type);
                        }
                    } else {
                        $values = $matches[1];
                    }
                }
                $type[] = 'integer';
                break;
            case 'set':
                $fixed = false;
                $type[] = 'text';
                $type[] = 'integer';
            break;
            case 'date':
                $type[] = 'date';
                $length = null;
            break;
            case 'datetime':
            case 'timestamp':
                $type[] = 'timestamp';
                $length = null;
            break;
            case 'time':
                $type[] = 'time';
                $length = null;
            break;
            case 'float':
            case 'double':
            case 'real':
                $type[] = 'float';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
            break;
            case 'unknown':
            case 'decimal':
            case 'numeric':
                $type[] = 'decimal';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
            break;
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'blob':
                $type[] = 'blob';
                $length = null;
            break;
            case 'year':
                $type[] = 'integer';
                $type[] = 'date';
                $length = null;
            break;
            default:
                throw DoctrineException::updateMe('unknown database attribute type: ' . $dbType);
        }

        $length = ((int) $length == 0) ? null : (int) $length;

        if ($values === null) {
            return array('type' => $type, 'length' => $length, 'unsigned' => $unsigned, 'fixed' => $fixed);
        } else {
            return array('type' => $type, 'length' => $length, 'unsigned' => $unsigned, 'fixed' => $fixed, 'values' => $values);
        }*/
    }
    
    /**
     * Obtain DBMS specific SQL code portion needed to set the CHARACTER SET
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $charset   name of the charset
     * @return string  DBMS specific SQL code portion needed to set the CHARACTER SET
     *                 of a field declaration.
     */
    public function getCharsetFieldDeclaration($charset)
    {
        return 'CHARACTER SET ' . $charset;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the COLLATION
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $collation   name of the collation
     * @return string  DBMS specific SQL code portion needed to set the COLLATION
     *                 of a field declaration.
     */
    public function getCollationFieldDeclaration($collation)
    {
        return 'COLLATE ' . $collation;
    }
    
    /**
     * Whether the platform prefers identity columns for ID generation.
     * MySql prefers "autoincrement" identity columns since sequences can only
     * be emulated with a table.
     *
     * @return boolean
     * @override
     */
    public function prefersIdentityColumns()
    {
        return true;
    }
    
    /**
     * Whether the platform supports identity columns.
     * MySql supports this through AUTO_INCREMENT columns.
     *
     * @return boolean
     * @override
     */
    public function supportsIdentityColumns()
    {
        return true;
    }
    
    /**
     * Whether the platform supports savepoints. MySql does not.
     *
     * @return boolean
     * @override
     */
    public function supportsSavepoints()
    {
        return false;
    }
    
    /**
     * Enter description here...
     *
     * @return unknown
     * @override
     */
    public function getShowDatabasesSql()
    {
        return 'SHOW DATABASES';
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     * @override
     */
    public function getListTablesSql()
    {
        return 'SHOW TABLES';
    }
    
    /**
     * create a new database
     *
     * @param string $name name of the database that should be created
     * @return string
     * @override
     */
    public function getCreateDatabaseSql($name)
    {
        return 'CREATE DATABASE ' . $this->quoteIdentifier($name);
    }
    
    /**
     * drop an existing database
     *
     * @param string $name name of the database that should be dropped
     * @return string
     * @override
     */
    public function getDropDatabaseSql($name)
    {
        return 'DROP DATABASE ' . $this->quoteIdentifier($name);
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
    public function getCreateTableSql($name, array $fields, array $options = array())
    {
        if ( ! $name) {
            throw DoctrineException::updateMe('no valid table name specified');
        }
        if (empty($fields)) {
            throw DoctrineException::updateMe('no fields specified for table "'.$name.'"');
        }
        $queryFields = $this->getColumnDeclarationListSql($fields);

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
                $queryFields .= ', ' . $this->getIndexDeclarationSql($index, $definition);
            }
        }

        // attach all primary keys
        if (isset($options['primary']) && ! empty($options['primary'])) {
            $keyColumns = array_unique(array_values($options['primary']));
            $keyColumns = array_map(array($this, 'quoteIdentifier'), $keyColumns);
            $queryFields .= ', PRIMARY KEY(' . implode(', ', $keyColumns) . ')';
        }

        $query = 'CREATE ';
        if (!empty($options['temporary'])) {
            $query .= 'TEMPORARY ';
        }
        $query.= 'TABLE ' . $this->quoteIdentifier($name, true) . ' (' . $queryFields . ')';

        $optionStrings = array();

        if (isset($options['comment'])) {
            $optionStrings['comment'] = 'COMMENT = ' . $this->quote($options['comment'], 'text');
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
        }/* else {
            $type = $this->getAttribute(Doctrine::ATTR_DEFAULT_TABLE_TYPE);
        }*/

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
                    $sql[] = $this->getCreateForeignKeySql($name, $definition);
                }
            }
        }
        
        return $sql;
    }
    
    /**
     * Gets the SQL to alter an existing table.
     *
     * @param string $name The name of the table that is intended to be changed.
     * @param array $changes Associative array that contains the details of each type
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
    public function getAlterTableSql($name, array $changes, $check = false)
    {
        if ( ! $name) {
            throw DoctrineException::updateMe('no valid table name specified');
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
                    throw DoctrineException::updateMe('change type "' . $changeName . '" not yet supported');
            }
        }

        if ($check) {
            return true;
        }

        $query = '';
        if ( ! empty($changes['name'])) {
            $change_name = $this->quoteIdentifier($changes['name']);
            $query .= 'RENAME TO ' . $change_name;
        }

        if ( ! empty($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $fieldName => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $query.= 'ADD ' . $this->getColumnDeclarationSql($fieldName, $field);
            }
        }

        if ( ! empty($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $fieldName => $field) {
                if ($query) {
                    $query .= ', ';
                }
                $fieldName = $this->quoteIdentifier($fieldName);
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
                $oldFieldName = $this->quoteIdentifier($oldFieldName, true);
                $query .= 'CHANGE ' . $oldFieldName . ' '
                        . $this->getColumnDeclarationSql($fieldName, $field['definition']);
            }
        }

        if ( ! empty($rename) && is_array($rename)) {
            foreach ($rename as $renameName => $renamedField) {
                if ($query) {
                    $query.= ', ';
                }
                $field = $changes['rename'][$renamedField];
                $renamedField = $this->quoteIdentifier($renamedField, true);
                $query .= 'CHANGE ' . $renamedField . ' '
                        . $this->getColumnDeclarationSql($field['name'], $field['definition']);
            }
        }

        if ( ! $query) {
            return false;
        }

        $name = $this->quoteIdentifier($name, true);

        return 'ALTER TABLE ' . $name . ' ' . $query;
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
    public function getCreateIndexSql($table, $name, array $definition)
    {
        $table = $table;
        $name = $this->quoteIdentifier($name);
        $type = '';
        if (isset($definition['type'])) {
            switch (strtolower($definition['type'])) {
                case 'fulltext':
                case 'unique':
                    $type = strtoupper($definition['type']) . ' ';
                break;
                default:
                    throw DoctrineException::updateMe('Unknown index type ' . $definition['type']);
            }
        }
        $query  = 'CREATE ' . $type . 'INDEX ' . $name . ' ON ' . $table;
        $query .= ' (' . $this->getIndexFieldDeclarationListSql($definition['fields']) . ')';

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
    public function getIntegerTypeDeclarationSql(array $field)
    {
        return 'INT' . $this->_getCommonIntegerTypeDeclarationSql($field);
    }

    /** @override */
    public function getBigIntTypeDeclarationSql(array $field)
    {
        return 'BIGINT' . $this->_getCommonIntegerTypeDeclarationSql($field);
    }

    /** @override */
    public function getSmallIntTypeDeclarationSql(array $field)
    {
        return 'SMALLINT' . $this->_getCommonIntegerTypeDeclarationSql($field);
    }

    /** @override */
    protected function _getCommonIntegerTypeDeclarationSql(array $columnDef)
    {
        $autoinc = '';
        if ( ! empty($columnDef['autoincrement'])) {
            $autoinc = ' AUTO_INCREMENT';
        }
        $unsigned = (isset($columnDef['unsigned']) && $columnDef['unsigned']) ? ' UNSIGNED' : '';

        return $unsigned . $autoinc;
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
    public function getIndexDeclarationSql($name, array $definition)
    {
        $name   = $this->formatter->getIndexName($name);
        $type   = '';
        if (isset($definition['type'])) {
            switch (strtolower($definition['type'])) {
                case 'fulltext':
                case 'unique':
                    $type = strtoupper($definition['type']) . ' ';
                break;
                default:
                    throw DoctrineException::updateMe('Unknown index type ' . $definition['type']);
            }
        }

        if ( ! isset($definition['fields'])) {
            throw DoctrineException::updateMe('No index columns given.');
        }
        if ( ! is_array($definition['fields'])) {
            $definition['fields'] = array($definition['fields']);
        }

        $query = $type . 'INDEX ' . $this->quoteIdentifier($name);

        $query .= ' (' . $this->getIndexFieldDeclarationListSql($definition['fields']) . ')';

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
    public function getIndexFieldDeclarationListSql(array $fields)
    {
        $declFields = array();

        foreach ($fields as $fieldName => $field) {
            $fieldString = $this->quoteIdentifier($fieldName);

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
                            throw DoctrineException::updateMe('Unknown index sorting option given.');
                    }
                }
            } else {
                $fieldString = $this->quoteIdentifier($field);
            }
            $declFields[] = $fieldString;
        }
        return implode(', ', $declFields);
    }
    
    /**
     * Return the FOREIGN KEY query section dealing with non-standard options
     * as MATCH, INITIALLY DEFERRED, ON UPDATE, ...
     *
     * @param array $definition
     * @return string
     * @override
     */
    public function getAdvancedForeignKeyOptionsSql(array $definition)
    {
        $query = '';
        if ( ! empty($definition['match'])) {
            $query .= ' MATCH ' . $definition['match'];
        }
        if ( ! empty($definition['onUpdate'])) {
            $query .= ' ON UPDATE ' . $this->getForeignKeyReferentialActionSql($definition['onUpdate']);
        }
        if ( ! empty($definition['onDelete'])) {
            $query .= ' ON DELETE ' . $this->getForeignKeyReferentialActionSql($definition['onDelete']);
        }
        return $query;
    }
    
    /**
     * Gets the SQL to drop an index of a table.
     *
     * @param string    $table          name of table that should be used in method
     * @param string    $name           name of the index to be dropped
     * @override
     */
    public function getDropIndexSql($table, $name)
    {
        $table = $this->quoteIdentifier($table);
        $name = $this->quoteIdentifier($name);
        return 'DROP INDEX ' . $name . ' ON ' . $table;
    }
    
    /**
     * Gets the SQL to drop a table.
     *
     * @param string $table The name of table to drop.
     * @override
     */
    public function getDropTableSql($table)
    {
        return 'DROP TABLE ' . $this->quoteIdentifier($table);
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $level
     * @override
     */
    public function getSetTransactionIsolationSql($level)
    {
        return 'SET SESSION TRANSACTION ISOLATION LEVEL ' . $this->_getTransactionIsolationLevelSql($level);
    }
}