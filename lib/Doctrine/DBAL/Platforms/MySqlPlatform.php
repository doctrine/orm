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

use Doctrine\Common\DoctrineException,
    Doctrine\DBAL\Schema\TableDiff;

/**
 * The MySqlPlatform provides the behavior, features and SQL dialect of the
 * MySQL database platform. This platform represents a MySQL 5.0 or greater platform that
 * uses the InnoDB storage engine.
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
                    throw DoctrineException::operatorNotSupported($operator);
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

    public function getListDatabasesSql()
    {
        return 'SHOW DATABASES';
    }

    public function getListFunctionsSql()
    {
        return 'SELECT SPECIFIC_NAME FROM information_schema.ROUTINES';
    }

    public function getListSequencesSql($database)
    {
        $query = 'SHOW TABLES';
        if ( ! is_null($database)) {
            $query .= ' FROM ' . $database;
        }
        return $query;
    }

    public function getListTableConstraintsSql($table)
    {
        return 'SHOW INDEX FROM ' . $table;
    }

    public function getListTableIndexesSql($table)
    {
        return 'SHOW INDEX FROM ' . $table;
    }

    public function getListUsersSql()
    {
        return "SELECT * FROM mysql.user WHERE user != '' GROUP BY user";
    }

    public function getListTriggersSql($table = null)
    {
        $sql = "SELECT TRIGGER_NAME FROM information_schema.TRIGGERS";
        if($table !== null) {
            $sql .= " WHERE EVENT_OBJECT_TABLE = '".$table."'";
        }
        return $sql;
    }

    public function getListViewsSql($database = null)
    {
        $sql = 'SELECT * FROM information_schema.VIEWS';
        if($database !== null) {
            $sql .= " WHERE TABLE_SCHEMA = '".$database."'";
        }
        return $sql;
    }

    public function getListTableForeignKeysSql($table, $database = null)
    {
        $sql = "SELECT `CONSTRAINT_NAME`, `COLUMN_NAME`, `REFERENCED_TABLE_NAME`, ".
               "`REFERENCED_COLUMN_NAME` FROM information_schema.key_column_usage WHERE table_name = '" . $table . "'";

        if ( ! is_null($database)) {
            $sql .= " AND table_schema = '$database'";
        }

        $sql .= " AND `REFERENCED_COLUMN_NAME` is not NULL";

        return $sql;
    }

    public function getCreateViewSql($name, $sql)
    {
        return 'CREATE VIEW ' . $name . ' AS ' . $sql;
    }

    public function getDropViewSql($name)
    {
        return 'DROP VIEW '. $name;
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
                : ($length ? 'VARCHAR(' . $length . ')' : 'VARCHAR(255)');
    }

    /** @override */
    public function getClobTypeDeclarationSql(array $field)
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
     * @override
     */
    public function getDateTimeTypeDeclarationSql(array $fieldDeclaration)
    {
        if (isset($fieldDeclaration['version']) && $fieldDeclaration['version'] == true) {
            return 'TIMESTAMP';
        } else {
            return 'DATETIME';
        }
    }
    
    /**
     * @override
     */
    public function getDateTypeDeclarationSql(array $fieldDeclaration)
    {
        return 'DATE';
    }

    /**
     * @override
     */
    public function getTimeTypeDeclarationSql(array $fieldDeclaration) 
    {
        return 'TIME';
    }	

    /**
     * @override
     */
    public function getBooleanTypeDeclarationSql(array $field)
    {
        return 'TINYINT(1)';
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

    public function getShowDatabasesSql()
    {
        return 'SHOW DATABASES';
    }
    
    public function getListTablesSql()
    {
        return 'SHOW FULL TABLES WHERE Table_type = "BASE TABLE"';
    }

    public function getListTableColumnsSql($table)
    {
        return 'DESCRIBE ' . $table;
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
        return 'CREATE DATABASE ' . $name;
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
        return 'DROP DATABASE ' . $name;
    }
    
    /**
     * create a new table
     *
     * @param string $tableName   Name of the database that should be created
     * @param array $columns  Associative array that contains the definition of each field of the new table
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
    protected function _getCreateTableSql($tableName, array $columns, array $options = array())
    {
        if ( ! $tableName) {
            throw DoctrineException::missingTableName();
        }
        if (empty($columns)) {
            throw DoctrineException::missingFieldsArrayForTable($tableName);
        }
        $queryFields = $this->getColumnDeclarationListSql($columns);

        if (isset($options['uniqueConstraints']) && ! empty($options['uniqueConstraints'])) {
            foreach ($options['uniqueConstraints'] as $uniqueConstraint) {
                $queryFields .= ', UNIQUE(' . implode(', ', array_values($uniqueConstraint)) . ')';
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
            $queryFields .= ', PRIMARY KEY(' . implode(', ', $keyColumns) . ')';
        }

        $query = 'CREATE ';
        if (!empty($options['temporary'])) {
            $query .= 'TEMPORARY ';
        }
        $query.= 'TABLE ' . $tableName . ' (' . $queryFields . ')';

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

        // get the type of the table
        if (isset($options['engine'])) {
            $optionStrings[] = 'ENGINE = ' . $engine;
        } else {
            // default to innodb
            $optionStrings[] = 'ENGINE = InnoDB';
        }
        
        if ( ! empty($optionStrings)) {
            $query.= ' '.implode(' ', $optionStrings);
        }
        $sql[] = $query;

        if (isset($options['foreignKeys'])) {
            foreach ((array) $options['foreignKeys'] as $definition) {
                $sql[] = $this->getCreateForeignKeySql($definition, $tableName);
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
    public function getAlterTableSql(TableDiff $diff)
    {
        $queryParts = array();
        if ($diff->newName !== false) {
            $queryParts[] =  'RENAME TO ' . $diff->newName;
        }

        foreach ($diff->addedColumns AS $fieldName => $column) {
            $queryParts[] = 'ADD ' . $this->getColumnDeclarationSql($column->getName(), $column->toArray());
        }

        foreach ($diff->removedColumns AS $column) {
            $queryParts[] =  'DROP ' . $column->getName();
        }

        foreach ($diff->changedColumns AS $columnDiff) {
            /* @var $columnDiff Doctrine\DBAL\Schema\ColumnDiff */
            $column = $columnDiff->column;
            $queryParts[] =  'CHANGE ' . ($columnDiff->oldColumnName) . ' '
                    . $this->getColumnDeclarationSql($column->getName(), $column->toArray());
        }

        foreach ($diff->renamedColumns AS $oldColumnName => $column) {
            $queryParts[] =  'CHANGE ' . $oldColumnName . ' '
                    . $this->getColumnDeclarationSql($column->getName(), $column->toArray());
        }

        $sql = $this->_getAlterTableIndexForeignKeySql($diff);
        if (count($queryParts) > 0) {
            $sql[] = 'ALTER TABLE ' . $diff->name . ' ' . implode(", ", $queryParts);
        }
        return $sql;
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
     * @return string
     * @override
     */
    public function getIndexFieldDeclarationListSql(array $fields)
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
                            throw DoctrineException::unknownIndexSortingOption($sort);
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
     * Return the FOREIGN KEY query section dealing with non-standard options
     * as MATCH, INITIALLY DEFERRED, ON UPDATE, ...
     *
     * @param ForeignKeyConstraint $foreignKey
     * @return string
     * @override
     */
    public function getAdvancedForeignKeyOptionsSql(\Doctrine\DBAL\Schema\ForeignKeyConstraint $foreignKey)
    {
        $query = '';
        if ($foreignKey->hasOption('match')) {
            $query .= ' MATCH ' . $foreignKey->getOption('match');
        }
        $query .= parent::getAdvancedForeignKeyOptionsSql($foreignKey);
        return $query;
    }
    
    /**
     * Gets the SQL to drop an index of a table.
     *
     * @param Index $index           name of the index to be dropped
     * @param string|Table $table          name of table that should be used in method
     * @override
     */
    public function getDropIndexSql($index, $table=null)
    {
        if($index instanceof \Doctrine\DBAL\Schema\Index) {
            $index = $index->getName();
        } else if(!is_string($index)) {
            throw new \InvalidArgumentException('MysqlPlatform::getDropIndexSql() expects $index parameter to be string or \Doctrine\DBAL\Schema\Index.');
        }
        
        if($table instanceof \Doctrine\DBAL\Schema\Table) {
            $table = $table->getName();
        } else if(!is_string($table)) {
            throw new \InvalidArgumentException('MysqlPlatform::getDropIndexSql() expects $table parameter to be string or \Doctrine\DBAL\Schema\Table.');
        }

        return 'DROP INDEX ' . $index . ' ON ' . $table;
    }
    
    /**
     * Gets the SQL to drop a table.
     *
     * @param string $table The name of table to drop.
     * @override
     */
    public function getDropTableSql($table)
    {
        return 'DROP TABLE ' . $table;
    }

    public function getSetTransactionIsolationSql($level)
    {
        return 'SET SESSION TRANSACTION ISOLATION LEVEL ' . $this->_getTransactionIsolationLevelSql($level);
    }

    /**
     * Get the platform name for this instance.
     *
     * @return string
     */
    public function getName()
    {
        return 'mysql';
    }
}
