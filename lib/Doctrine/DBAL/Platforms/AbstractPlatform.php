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
    Doctrine\DBAL\Connection,
    Doctrine\DBAL\Types;

/**
 * Base class for all DatabasePlatforms. The DatabasePlatforms are the central
 * point of abstraction of platform-specific behaviors, features and SQL dialects.
 * They are a passive source of information.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision: 3938 $
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 */
abstract class AbstractPlatform
{
    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Gets the character used for identifier quoting.
     *
     * @return string
     */
    public function getIdentifierQuoteCharacter()
    {
        return '"';
    }

    /**
     * Gets the string portion that starts an SQL comment.
     *
     * @return string
     */
    public function getSqlCommentStartString()
    {
        return "--";
    }

    /**
     * Gets the string portion that ends an SQL comment.
     *
     * @return string
     */
    public function getSqlCommentEndString()
    {
        return "\n";
    }

    /**
     * Gets the maximum length of a varchar field.
     *
     * @return integer
     */
    public function getVarcharMaxLength()
    {
        return 255;
    }

    /**
     * Gets all SQL wildcard characters of the platform.
     *
     * @return array
     */
    public function getWildcards()
    {
        return array('%', '_');
    }

    /**
     * Returns the regular expression operator.
     *
     * @return string
     */
    public function getRegexpExpression()
    {
        throw DoctrineException::regularExpressionOperatorNotSupported($this);
    }

    /**
     * Returns the average value of a column
     *
     * @param string $column    the column to use
     * @return string           generated sql including an AVG aggregate function
     */
    public function getAvgExpression($column)
    {
        return 'AVG(' .  $column . ')';
    }

    /**
     * Returns the number of rows (without a NULL value) of a column
     *
     * If a '*' is used instead of a column the number of selected rows
     * is returned.
     *
     * @param string|integer $column    the column to use
     * @return string                   generated sql including a COUNT aggregate function
     */
    public function getCountExpression($column)
    {
        return 'COUNT(' . $column . ')';
    }

    /**
     * Returns the highest value of a column
     *
     * @param string $column    the column to use
     * @return string           generated sql including a MAX aggregate function
     */
    public function getMaxExpression($column)
    {
        return 'MAX(' . $column . ')';
    }

    /**
     * Returns the lowest value of a column
     *
     * @param string $column the column to use
     * @return string
     */
    public function getMinExpression($column)
    {
        return 'MIN(' . $column . ')';
    }

    /**
     * Returns the total sum of a column
     *
     * @param string $column the column to use
     * @return string
     */
    public function getSumExpression($column)
    {
        return 'SUM(' . $column . ')';
    }

    // scalar functions

    /**
     * Returns the md5 sum of a field.
     *
     * Note: Not SQL92, but common functionality
     *
     * @return string
     */
    public function getMd5Expression($column)
    {
        return 'MD5(' . $column . ')';
    }

    /**
     * Returns the length of a text field.
     *
     * @param string $expression1
     * @param string $expression2
     * @return string
     */
    public function getLengthExpression($column)
    {
        return 'LENGTH(' . $column . ')';
    }

    /**
     * Rounds a numeric field to the number of decimals specified.
     *
     * @param string $expression1
     * @param string $expression2
     * @return string
     */
    public function getRoundExpression($column, $decimals = 0)
    {
        return 'ROUND(' . $column . ', ' . $decimals . ')';
    }

    /**
     * Returns the remainder of the division operation
     * $expression1 / $expression2.
     *
     * @param string $expression1
     * @param string $expression2
     * @return string
     */
    public function getModExpression($expression1, $expression2)
    {
        return 'MOD(' . $expression1 . ', ' . $expression2 . ')';
    }

    /**
     * trim
     * returns the string $str with leading and proceeding space characters removed
     *
     * @param string $str       literal string or column name
     * @return string
     */
    public function getTrimExpression($str)
    {
        return 'TRIM(' . $str . ')';
    }

    /**
     * rtrim
     * returns the string $str with proceeding space characters removed
     *
     * @param string $str       literal string or column name
     * @return string
     */
    public function getRtrimExpression($str)
    {
        return 'RTRIM(' . $str . ')';
    }

    /**
     * ltrim
     * returns the string $str with leading space characters removed
     *
     * @param string $str       literal string or column name
     * @return string
     */
    public function getLtrimExpression($str)
    {
        return 'LTRIM(' . $str . ')';
    }

    /**
     * upper
     * Returns the string $str with all characters changed to
     * uppercase according to the current character set mapping.
     *
     * @param string $str       literal string or column name
     * @return string
     */
    public function getUpperExpression($str)
    {
        return 'UPPER(' . $str . ')';
    }

    /**
     * lower
     * Returns the string $str with all characters changed to
     * lowercase according to the current character set mapping.
     *
     * @param string $str       literal string or column name
     * @return string
     */
    public function getLowerExpression($str)
    {
        return 'LOWER(' . $str . ')';
    }

    /**
     * locate
     * returns the position of the first occurrence of substring $substr in string $str
     *
     * @param string $substr    literal string to find
     * @param string $str       literal string
     * @return integer
     */
    public function getLocateExpression($str, $substr)
    {
        return 'LOCATE(' . $str . ', ' . $substr . ')';
    }

    /**
     * Returns the current system date.
     *
     * @return string
     */
    public function getNowExpression()
    {
        return 'NOW()';
    }

    /**
     * return string to call a function to get a substring inside an SQL statement
     *
     * Note: Not SQL92, but common functionality.
     *
     * SQLite only supports the 2 parameter variant of this function
     *
     * @param string $value         an sql string literal or column name/alias
     * @param integer $position     where to start the substring portion
     * @param integer $length       the substring portion length
     * @return string               SQL substring function with given parameters
     */
    public function getSubstringExpression($value, $from, $len = null)
    {
        if ($len === null)
            return 'SUBSTRING(' . $value . ' FROM ' . $from . ')';
        else {
            return 'SUBSTRING(' . $value . ' FROM ' . $from . ' FOR ' . $len . ')';
        }
    }

    /**
     * Returns a series of strings concatinated
     *
     * concat() accepts an arbitrary number of parameters. Each parameter
     * must contain an expression
     *
     * @param string $arg1, $arg2 ... $argN     strings that will be concatinated.
     * @return string
     */
    public function getConcatExpression()
    {
        return join(' || ' , func_get_args());
    }

    /**
     * Returns the SQL for a logical not.
     *
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $e = $q->expr;
     * $q->select('*')->from('table')
     *   ->where($e->eq('id', $e->not('null'));
     * </code>
     *
     * @return string a logical expression
     */
    public function getNotExpression($expression)
    {
        return 'NOT(' . $expression . ')';
    }

    /**
     * Returns the SQL to check if a value is one in a set of
     * given values.
     *
     * in() accepts an arbitrary number of parameters. The first parameter
     * must always specify the value that should be matched against. Successive
     * must contain a logical expression or an array with logical expressions.
     * These expressions will be matched against the first parameter.
     *
     * @param string $column        the value that should be matched against
     * @param string|array(string)  values that will be matched against $column
     * @return string logical expression
     */
    public function getInExpression($column, $values)
    {
        if ( ! is_array($values)) {
            $values = array($values);
        }
        $values = $this->getIdentifiers($values);

        if (count($values) == 0) {
            throw DoctrineException::valuesArrayForInOperatorInvalid();
        }
        return $column . ' IN (' . implode(', ', $values) . ')';
    }

    /**
     * Returns SQL that checks if a expression is null.
     *
     * @param string $expression the expression that should be compared to null
     * @return string logical expression
     */
    public function getIsNullExpression($expression)
    {
        return $expression . ' IS NULL';
    }

    /**
     * Returns SQL that checks if a expression is not null.
     *
     * @param string $expression the expression that should be compared to null
     * @return string logical expression
     */
    public function getIsNotNullExpression($expression)
    {
        return $expression . ' IS NOT NULL';
    }

    /**
     * Returns SQL that checks if an expression evaluates to a value between
     * two values.
     *
     * The parameter $expression is checked if it is between $value1 and $value2.
     *
     * Note: There is a slight difference in the way BETWEEN works on some databases.
     * http://www.w3schools.com/sql/sql_between.asp. If you want complete database
     * independence you should avoid using between().
     *
     * @param string $expression the value to compare to
     * @param string $value1 the lower value to compare with
     * @param string $value2 the higher value to compare with
     * @return string logical expression
     */
    public function getBetweenExpression($expression, $value1, $value2)
    {
        return $expression . ' BETWEEN ' .$value1 . ' AND ' . $value2;
    }

    public function getAcosExpression($value)
    {
        return 'ACOS(' . $value . ')';
    }

    public function getSinExpression($value)
    {
        return 'SIN(' . $value . ')';
    }

    public function getPiExpression()
    {
        return 'PI()';
    }

    public function getCosExpression($value)
    {
        return 'COS(' . $value . ')';
    }

    public function getForUpdateSql()
    {
        return 'FOR UPDATE';
    }

    public function getDropDatabaseSql($database)
    {
        return 'DROP DATABASE ' . $database;
    }

    public function getDropTableSql($table)
    {
        return 'DROP TABLE ' . $table;
    }

    public function getDropIndexSql($table, $name)
    {
        return 'DROP INDEX ' . $name;
    }

    public function getDropConstraintSql($table, $name, $primary = false)
    {
        return 'ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $name;
    }

    public function getDropForeignKeySql($table, $name)
    {
        return 'ALTER TABLE ' . $table . ' DROP FOREIGN KEY ' . $name;
    }

    /**
     * Gets the SQL statement(s) to create a table with the specified name, columns and constraints
     * on this platform.
     *
     * @param string $table The name of the table.
     * @param array $columns The column definitions for the table.
     * @param array $options The table constraints.
     * @return array The sequence of SQL statements.
     */
    public function getCreateTableSql($table, array $columns, array $options = array())
    {
        $columnListSql = $this->getColumnDeclarationListSql($columns);

        if (isset($options['uniqueConstraints']) && ! empty($options['uniqueConstraints'])) {
            foreach ($options['uniqueConstraints'] as $uniqueConstraint) {
                $columnListSql .= ', UNIQUE(' . implode(', ', array_values($uniqueConstraint)) . ')';
            }
        }
        
        if (isset($options['primary']) && ! empty($options['primary'])) {
            $columnListSql .= ', PRIMARY KEY(' . implode(', ', array_unique(array_values($options['primary']))) . ')';
        }

        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach($options['indexes'] as $index => $definition) {
                $columnListSql .= ', ' . $this->getIndexDeclarationSql($index, $definition);
            }
        }

        $query = 'CREATE TABLE ' . $table . ' (' . $columnListSql;

        $check = $this->getCheckDeclarationSql($columns);
        if ( ! empty($check)) {
            $query .= ', ' . $check;
        }
        $query .= ')';

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
    
    public function getCreateTemporaryTableSnippetSql()
    {
        return "CREATE TEMPORARY TABLE";
    }

    /**
     * Gets the SQL to create a sequence on this platform.
     *
     * @param string $sequenceName
     * @param integer $start
     * @param integer $allocationSize
     * @return string
     * @throws DoctrineException
     */
    public function getCreateSequenceSql($sequenceName, $start = 1, $allocationSize = 1)
    {
        throw DoctrineException::createSequenceNotSupported($this);
    }

    /**
     * Gets the SQL to create a constraint on a table on this platform.
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
     * @return string
     */
    public function getCreateConstraintSql($table, $name, $definition)
    {
        $query = 'ALTER TABLE ' . $table . ' ADD CONSTRAINT ' . $name;

        if (isset($definition['primary']) && $definition['primary']) {
            $query .= ' PRIMARY KEY';
        } elseif (isset($definition['unique']) && $definition['unique']) {
            $query .= ' UNIQUE';
        }

        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $field;
        }
        $query .= ' ('. implode(', ', $fields) . ')';

        return $query;
    }

    /**
     * Gets the SQL to create an index on a table on this platform.
     *
     * @param string    $table         name of the table on which the index is to be created
     * @param string    $name          name of the index to be created
     * @param array     $definition    associative array that defines properties of the index to be created.
     * @return string
     */
    public function getCreateIndexSql($table, $name, array $definition)
    {
        if ( ! isset($definition['fields'])) {
            throw DoctrineException::indexFieldsArrayRequired();
        }

        $type = '';
        if (isset($definition['type'])) {
            switch (strtolower($definition['type'])) {
                case 'unique':
                    $type = strtoupper($definition['type']) . ' ';
                break;
                default:
                    throw DoctrineException::unknownIndexType($definition['type']);
            }
        }

        $query = 'CREATE ' . $type . 'INDEX ' . $name . ' ON ' . $table;

        $query .= ' (' . $this->getIndexFieldDeclarationListSql($definition['fields']) . ')';

        return $query;
    }

    /**
     * Quotes a string so that it can be safely used as a table or column name,
     * even if it is a reserved word of the platform.
     *
     * NOTE: Just because you CAN use quoted identifiers doesn't mean
     * you SHOULD use them.  In general, they end up causing way more
     * problems than they solve.
     *
     * @param string $str           identifier name to be quoted
     * @return string               quoted identifier string
     */
    public function quoteIdentifier($str)
    {
        $c = $this->getIdentifierQuoteCharacter();

        return $c . $str . $c;
    }

    /**
     * createForeignKeySql
     *
     * @param string    $table         name of the table on which the foreign key is to be created
     * @param array     $definition    associative array that defines properties of the foreign key to be created.
     * @return string
     */
    public function getCreateForeignKeySql($table, array $definition)
    {
        $query = 'ALTER TABLE ' . $table . ' ADD ' . $this->getForeignKeyDeclarationSql($definition);

        return $query;
    }

    /**
     * Gets the sql for altering an existing table.
     * (this method is implemented by the drivers)
     *
     * @param string $name          name of the table that is intended to be changed.
     * @param array $changes        associative array that contains the details of each type      *
     * @param boolean $check        indicates whether the function should just check if the DBMS driver
     *                              can perform the requested table alterations if the value is true or
     *                              actually perform them otherwise.
     * @return string
     */
    public function getAlterTableSql($name, array $changes, $check = false)
    {
        throw DoctrineException::alterTableNotSupported($this);
    }

    /**
     * Get declaration of a number of fields in bulk
     *
     * @param array $fields  a multidimensional associative array.
     *      The first dimension determines the field name, while the second
     *      dimension is keyed with the name of the properties
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
     *      charset
     *          Text value with the default CHARACTER SET for this field.
     *      collation
     *          Text value with the default COLLATION for this field.
     *      unique
     *          unique constraint
     *
     * @return string
     */
    public function getColumnDeclarationListSql(array $fields)
    {
        $queryFields = array();
        foreach ($fields as $fieldName => $field) {
            $query = $this->getColumnDeclarationSql($fieldName, $field);
            $queryFields[] = $query;
        }
        return implode(', ', $queryFields);
    }

    /**
     * Obtain DBMS specific SQL code portion needed to declare a generic type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param array  $field  associative array with the name of the properties
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
     *      charset
     *          Text value with the default CHARACTER SET for this field.
     *      collation
     *          Text value with the default COLLATION for this field.
     *      unique
     *          unique constraint
     *      check
     *          column check constraint
     *
     * @return string  DBMS specific SQL code portion that should be used to declare the column.
     */
    public function getColumnDeclarationSql($name, array $field)
    {
        $default = $this->getDefaultValueDeclarationSql($field);

        $charset = (isset($field['charset']) && $field['charset']) ?
                ' ' . $this->getColumnCharsetDeclarationSql($field['charset']) : '';

        $collation = (isset($field['collation']) && $field['collation']) ?
                ' ' . $this->getColumnCollationDeclarationSql($field['collation']) : '';

        $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';

        $unique = (isset($field['unique']) && $field['unique']) ?
                ' ' . $this->getUniqueFieldDeclarationSql() : '';

        $check = (isset($field['check']) && $field['check']) ?
                ' ' . $field['check'] : '';

        $typeDecl = $field['type']->getSqlDeclaration($field, $this);

        return $name . ' ' . $typeDecl . $charset . $default . $notnull . $unique . $check . $collation;
    }
    
    /**
     * Gets the SQL snippet that declares a floating point column of arbitrary precision.
     *
     * @param array $columnDef
     * @return string
     */
    public function getDecimalTypeDeclarationSql(array $columnDef) 
    {
        $columnDef['precision'] = ( ! isset($columnDef['precision']) || empty($columnDef['precision']))
            ? 10 : $columnDef['precision'];
        $columnDef['scale'] = ( ! isset($columnDef['scale']) || empty($columnDef['scale']))
            ? 0 : $columnDef['scale'];
        
        return 'NUMERIC(' . $columnDef['precision'] . ', ' . $columnDef['scale'] . ')';
    }

    /**
     * Gets the SQL snippet that declares a boolean column.
     *
     * @param array $columnDef
     * @return string
     */
    abstract public function getBooleanTypeDeclarationSql(array $columnDef);

    /**
     * Gets the SQL snippet that declares a 4 byte integer column.
     *
     * @param array $columnDef
     * @return string
     */
    abstract public function getIntegerTypeDeclarationSql(array $columnDef);

    /**
     * Gets the SQL snippet that declares an 8 byte integer column.
     *
     * @param array $columnDef
     * @return string
     */
    abstract public function getBigIntTypeDeclarationSql(array $columnDef);

    /**
     * Gets the SQL snippet that declares a 2 byte integer column.
     *
     * @param array $columnDef
     * @return string
     */
    abstract public function getSmallIntTypeDeclarationSql(array $columnDef);

    /**
     * Gets the SQL snippet that declares common properties of an integer column.
     *
     * @param array $columnDef
     * @return string
     */
    abstract protected function _getCommonIntegerTypeDeclarationSql(array $columnDef);

    /**
     * Obtain DBMS specific SQL code portion needed to set a default value
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param array $field      field definition array
     * @return string           DBMS specific SQL code portion needed to set a default value
     */
    public function getDefaultValueDeclarationSql($field)
    {
        $default = empty($field['notnull']) ? ' DEFAULT NULL' : '';

        if (isset($field['default'])) {
            $default = ' DEFAULT ' . $field['default'];
        }
        return $default;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set a CHECK constraint
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param array $definition     check definition
     * @return string               DBMS specific SQL code portion needed to set a CHECK constraint
     */
    public function getCheckDeclarationSql(array $definition)
    {
        $constraints = array();
        foreach ($definition as $field => $def) {
            if (is_string($def)) {
                $constraints[] = 'CHECK (' . $def . ')';
            } else {
                if (isset($def['min'])) {
                    $constraints[] = 'CHECK (' . $field . ' >= ' . $def['min'] . ')';
                }

                if (isset($def['max'])) {
                    $constraints[] = 'CHECK (' . $field . ' <= ' . $def['max'] . ')';
                }
            }
        }

        return implode(', ', $constraints);
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set an index
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param string $name          name of the index
     * @param array $definition     index definition
     * @return string               DBMS specific SQL code portion needed to set an index
     */
    public function getIndexDeclarationSql($name, array $definition)
    {
        $type   = '';

        if (isset($definition['type'])) {
            if (strtolower($definition['type']) == 'unique') {
                $type = strtoupper($definition['type']) . ' ';
            } else {
                throw DoctrineException::unknownIndexType($definition['type']);
            }
        }

        if ( ! isset($definition['fields']) || ! is_array($definition['fields'])) {
            throw DoctrineException::indexFieldsArrayRequired();
        }

        $query = $type . 'INDEX ' . $name;

        $query .= ' (' . $this->getIndexFieldDeclarationListSql($definition['fields']) . ')';

        return $query;
    }

    /**
     * getIndexFieldDeclarationList
     * Obtain DBMS specific SQL code portion needed to set an index
     * declaration to be used in statements like CREATE TABLE.
     *
     * @return string
     */
    public function getIndexFieldDeclarationListSql(array $fields)
    {
        $ret = array();
        foreach ($fields as $field => $definition) {
            if (is_array($definition)) {
                $ret[] = $field;
            } else {
                $ret[] = $definition;
            }
        }
        return implode(', ', $ret);
    }

    /**
     * A method to return the required SQL string that fits between CREATE ... TABLE
     * to create the table as a temporary table.
     *
     * Should be overridden in driver classes to return the correct string for the
     * specific database type.
     *
     * The default is to return the string "TEMPORARY" - this will result in a
     * SQL error for any database that does not support temporary tables, or that
     * requires a different SQL command from "CREATE TEMPORARY TABLE".
     *
     * @return string The string required to be placed between "CREATE" and "TABLE"
     *                to generate a temporary table, if possible.
     */
    public function getTemporaryTableSql()
    {
        return 'TEMPORARY';
    }

    /**
     * Get sql query to show a list of database
     *
     * @return unknown
     */
    public function getShowDatabasesSql()
    {
        throw DoctrineException::showDatabasesNotSupported($this);
    }

    /**
     * getForeignKeyDeclaration
     * Obtain DBMS specific SQL code portion needed to set the FOREIGN KEY constraint
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param array $definition         an associative array with the following structure:
     *          name                    optional constraint name
     *
     *          local                   the local field(s)
     *
     *          foreign                 the foreign reference field(s)
     *
     *          foreignTable            the name of the foreign table
     *
     *          onDelete                referential delete action
     *
     *          onUpdate                referential update action
     *
     *          deferred                deferred constraint checking
     *
     * The onDelete and onUpdate keys accept the following values:
     *
     * CASCADE: Delete or update the row from the parent table and automatically delete or
     *          update the matching rows in the child table. Both ON DELETE CASCADE and ON UPDATE CASCADE are supported.
     *          Between two tables, you should not define several ON UPDATE CASCADE clauses that act on the same column
     *          in the parent table or in the child table.
     *
     * SET NULL: Delete or update the row from the parent table and set the foreign key column or columns in the
     *          child table to NULL. This is valid only if the foreign key columns do not have the NOT NULL qualifier
     *          specified. Both ON DELETE SET NULL and ON UPDATE SET NULL clauses are supported.
     *
     * NO ACTION: In standard SQL, NO ACTION means no action in the sense that an attempt to delete or update a primary
     *           key value is not allowed to proceed if there is a related foreign key value in the referenced table.
     *
     * RESTRICT: Rejects the delete or update operation for the parent table. NO ACTION and RESTRICT are the same as
     *           omitting the ON DELETE or ON UPDATE clause.
     *
     * SET DEFAULT
     *
     * @return string  DBMS specific SQL code portion needed to set the FOREIGN KEY constraint
     *                 of a field declaration.
     */
    public function getForeignKeyDeclarationSql(array $definition)
    {
        $sql  = $this->getForeignKeyBaseDeclarationSql($definition);
        $sql .= $this->getAdvancedForeignKeyOptionsSql($definition);

        return $sql;
    }

    /**
     * Return the FOREIGN KEY query section dealing with non-standard options
     * as MATCH, INITIALLY DEFERRED, ON UPDATE, ...
     *
     * @param array $definition     foreign key definition
     * @return string
     */
    public function getAdvancedForeignKeyOptionsSql(array $definition)
    {
        $query = '';
        if ( ! empty($definition['onUpdate'])) {
            $query .= ' ON UPDATE ' . $this->getForeignKeyReferentialActionSql($definition['onUpdate']);
        }
        if ( ! empty($definition['onDelete'])) {
            $query .= ' ON DELETE ' . $this->getForeignKeyReferentialActionSql($definition['onDelete']);
        }
        return $query;
    }

    /**
     * returns given referential action in uppercase if valid, otherwise throws
     * an exception
     *
     * @throws Doctrine_Exception_Exception     if unknown referential action given
     * @param string $action    foreign key referential action
     * @param string            foreign key referential action in uppercase
     */
    public function getForeignKeyReferentialActionSql($action)
    {
        $upper = strtoupper($action);
        switch ($upper) {
            case 'CASCADE':
            case 'SET NULL':
            case 'NO ACTION':
            case 'RESTRICT':
            case 'SET DEFAULT':
                return $upper;
            break;
            default:
                throw DoctrineException::unknownForeignKeyReferentialAction($upper);
        }
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the FOREIGN KEY constraint
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param array $definition
     * @return string
     */
    public function getForeignKeyBaseDeclarationSql(array $definition)
    {
        $sql = '';
        if (isset($definition['name'])) {
            $sql .= ' CONSTRAINT ' . $definition['name'] . ' ';
        }
        $sql .= 'FOREIGN KEY (';

        if ( ! isset($definition['local'])) {
            throw DoctrineException::localReferenceFieldMissing();
        }
        if ( ! isset($definition['foreign'])) {
            throw DoctrineException::foreignReferenceFieldMissing();
        }
        if ( ! isset($definition['foreignTable'])) {
            throw DoctrineException::foreignReferenceTableMissing();
        }

        if ( ! is_array($definition['local'])) {
            $definition['local'] = array($definition['local']);
        }
        if ( ! is_array($definition['foreign'])) {
            $definition['foreign'] = array($definition['foreign']);
        }

        $sql .= implode(', ', $definition['local'])
              . ') REFERENCES '
              . $definition['foreignTable'] . '('
              . implode(', ', $definition['foreign']) . ')';

        return $sql;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the UNIQUE constraint
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @return string  DBMS specific SQL code portion needed to set the UNIQUE constraint
     *                 of a field declaration.
     */
    public function getUniqueFieldDeclarationSql()
    {
        return 'UNIQUE';
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the CHARACTER SET
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $charset   name of the charset
     * @return string  DBMS specific SQL code portion needed to set the CHARACTER SET
     *                 of a field declaration.
     */
    public function getColumnCharsetDeclarationSql($charset)
    {
        return '';
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the COLLATION
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $collation   name of the collation
     * @return string  DBMS specific SQL code portion needed to set the COLLATION
     *                 of a field declaration.
     */
    public function getColumnCollationDeclarationSql($collation)
    {
        return '';
    }

    /**
     * build a pattern matching string
     *
     * EXPERIMENTAL
     *
     * WARNING: this function is experimental and may change signature at
     * any time until labelled as non-experimental
     *
     * @access public
     *
     * @param array $pattern even keys are strings, odd are patterns (% and _)
     * @param string $operator optional pattern operator (LIKE, ILIKE and maybe others in the future)
     * @param string $field optional field name that is being matched against
     *                  (might be required when emulating ILIKE)
     *
     * @return string SQL pattern
     */
    public function getMatchPatternExpression($pattern, $operator = null, $field = null)
    {
        throw DoctrineException::matchPatternExpressionNotSupported($this);
    }

    /**
     * Whether the platform prefers sequences for ID generation.
     * Subclasses should override this method to return TRUE if they prefer sequences.
     *
     * @return boolean
     */
    public function prefersSequences()
    {
        return false;
    }

    /**
     * Whether the platform prefers identity columns (eg. autoincrement) for ID generation.
     * Subclasses should override this method to return TRUE if they prefer identity columns.
     *
     * @return boolean
     */
    public function prefersIdentityColumns()
    {
        return false;
    }

    /**
     * Adds a LIMIT/OFFSET clause to the query.
     * This default implementation writes the syntax "LIMIT x OFFSET y" to the
     * query which is supported by MySql, PostgreSql and Sqlite.
     * Any database platforms that do not support this syntax should override
     * this implementation and provide their own.
     *
     * @param string $query  The SQL string to write to / append to.
     * @param mixed $limit
     * @param mixed $offset
     */
    public function writeLimitClause($query, $limit = false, $offset = false)
    {
        $limit = (int) $limit;
        $offset = (int) $offset;

        if ($limit && $offset) {
            $query .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        } elseif ($limit && ! $offset) {
            $query .= ' LIMIT ' . $limit;
        } elseif ( ! $limit && $offset) {
            $query .= ' LIMIT 999999999999 OFFSET ' . $offset;
        }

        return $query;
    }

    /**
     * Some platforms need the boolean values to be converted.
     * Default conversion defined here converts to integers.
     *
     * @param array $item
     */
    public function convertBooleans($item)
    {
        if (is_array($item)) {
            foreach ($item as $k => $value) {
                if (is_bool($value)) {
                    $item[$k] = (int) $value;
                }
            }
        } else if (is_bool($item)) {
            $item = (int) $item;
        }
        return $item;
    }

    /**
     * Gets the SQL statement specific for the platform to set the charset.
     *
     * @param string $charset
     * @return string
     */
    public function getSetCharsetSql($charset)
    {
        return 'SET NAMES ' . $this->quote($charset);
    }

    /**
     * Gets the SQL specific for the platform to get the current date.
     *
     * @return string
     */
    public function getCurrentDateSql()
    {
        return 'CURRENT_DATE';
    }

    /**
     * Gets the SQL specific for the platform to get the current time.
     *
     * @return string
     */
    public function getCurrentTimeSql()
    {
        return 'CURRENT_TIME';
    }

    /**
     * Gets the SQL specific for the platform to get the current timestamp
     *
     * @return string
     */
    public function getCurrentTimestampSql()
    {
        return 'CURRENT_TIMESTAMP';
    }

    /**
     * Get sql for transaction isolation level Connection constant
     *
     * @param integer $level
     */
    protected function _getTransactionIsolationLevelSql($level)
    {
        switch ($level) {
            case Connection::TRANSACTION_READ_UNCOMMITTED:
                return 'READ UNCOMMITTED';
            case Connection::TRANSACTION_READ_COMMITTED:
                return 'READ COMMITTED';
            case Connection::TRANSACTION_REPEATABLE_READ:
                return 'REPEATABLE READ';
            case Connection::TRANSACTION_SERIALIZABLE:
                return 'SERIALIZABLE';
            default:
                throw DoctrineException::isolationLevelNotSupported($isolation);
        }
    }

    public function getListDatabasesSql()
    {
        throw DoctrineException::listDatabasesNotSupported($this);
    }

    public function getListFunctionsSql()
    {
        throw DoctrineException::listFunctionsNotSupported($this);
    }

    public function getListTriggersSql($table = null)
    {
        throw DoctrineException::listTriggersNotSupported($this);
    }

    public function getListSequencesSql($database)
    {
        throw DoctrineException::listSequencesNotSupported($this);
    }

    public function getListTableConstraintsSql($table)
    {
        throw DoctrineException::listTableConstraintsNotSupported($this);
    }

    public function getListTableColumnsSql($table)
    {
        throw DoctrineException::listTableColumnsNotSupported($this);
    }

    public function getListTablesSql()
    {
        throw DoctrineException::listTablesNotSupported($this);
    }

    public function getListUsersSql()
    {
        throw DoctrineException::listUsersNotSupported($this);
    }

    public function getListViewsSql()
    {
        throw DoctrineException::listViewsNotSupported($this);
    }

    public function getListTableIndexesSql($table)
    {
        throw DoctrineException::listTableIndexesNotSupported($this);
    }

    public function getListTableForeignKeysSql($table)
    {
        throw DoctrineException::listTableForeignKeysNotSupported($this);
    }

    public function getCreateViewSql($name, $sql)
    {
        throw DoctrineException::createViewNotSupported($this);
    }

    public function getDropViewSql($name)
    {
        throw DoctrineException::dropViewNotSupported($this);
    }

    public function getDropSequenceSql($sequenceName)
    {
        throw DoctrineException::dropSequenceNotSupported($this);
    }

    public function getSequenceNextValSql($sequenceName)
    {
        throw DoctrineException::sequenceNotSupported($this);
    }

    public function getCreateDatabaseSql($database)
    {
        throw DoctrineException::createDatabaseNotSupported($this);
    }

    /**
     * Get sql to set the transaction isolation level
     *
     * @param integer $level
     */
    public function getSetTransactionIsolationSql($level)
    {
        throw DoctrineException::setTransactionIsolationLevelNotSupported($this);
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
        throw DoctrineException::getCharsetFieldDeclarationNotSupported($this);
    }

    /**
     * Obtain DBMS specific SQL to be used to create datetime fields in 
     * statements like CREATE TABLE
     *
     * @param array $fieldDeclaration 
     * @return string
     */
    public function getDateTimeTypeDeclarationSql(array $fieldDeclaration)
    {
        throw DoctrineException::getDateTimeTypeDeclarationNotSupported($this);
    }
    
    /**
     * Obtain DBMS specific SQL to be used to create date fields in statements
     * like CREATE TABLE.
     * 
     * @param array $fieldDeclaration
     * @return string
     */
    public function getDateTypeDeclarationSql(array $fieldDeclaration)
    {
        throw DoctrineException::getDateTypeDeclarationNotSupported($this);
    }

    /**
     * Gets the default transaction isolation level of the platform.
     *
     * @return integer The default isolation level.
     * @see Doctrine\DBAL\Connection\TRANSACTION_* constants.
     */
    public function getDefaultTransactionIsolationLevel()
    {
        return Connection::TRANSACTION_READ_COMMITTED;
    }

    /* supports*() metods */

    /**
     * Whether the platform supports sequences.
     *
     * @return boolean
     */
    public function supportsSequences()
    {
        return false;
    }

    /**
     * Whether the platform supports identity columns.
     * Identity columns are columns that recieve an auto-generated value from the
     * database on insert of a row.
     *
     * @return boolean
     */
    public function supportsIdentityColumns()
    {
        return false;
    }

    /**
     * Whether the platform supports indexes.
     *
     * @return boolean
     */
    public function supportsIndexes()
    {
        return true;
    }

    /**
     * Whether the platform supports transactions.
     *
     * @return boolean
     */
    public function supportsTransactions()
    {
        return true;
    }

    /**
     * Whether the platform supports savepoints.
     *
     * @return boolean
     */
    public function supportsSavepoints()
    {
        return true;
    }

    /**
     * Whether the platform supports primary key constraints.
     *
     * @return boolean
     */
    public function supportsPrimaryConstraints()
    {
        return true;
    }

    /**
     * Whether the platform supports foreign key constraints.
     *
     * @return boolean
     */
    public function supportsForeignKeyConstraints()
    {
        return true;
    }
    
    /**
     * Whether the platform supports database schemas.
     * 
     * @return boolean
     */
    public function supportsSchemas()
    {
        return false;
    }

    /**
     * Whether the platform supports getting the affected rows of a recent
     * update/delete type query.
     *
     * @return boolean
     */
    public function supportsGettingAffectedRows()
    {
        return true;
    }

    public function getIdentityColumnNullInsertSql()
    {
        return "";
    }

    /**
     * Gets the format string, as accepted by the date() function, that describes
     * the format of a stored datetime value of this platform.
     * 
     * @return string The format string.
     * 
     * @todo We need to get the specific format for each dbms and override this
     * function for each platform
     */
    public function getDateTimeFormatString()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * Gets the format string, as accepted by the date() function, that describes
     * the format of a stored date value of this platform.
     * 
     * @return string The format string.
     */
    public function getDateFormatString()
    {
        return 'Y-m-d';
    }
    
    /**
     * Gets the format string, as accepted by the date() function, that describes
     * the format of a stored time value of this platform.
     * 
     * @return string The format string.
     */
    public function getTimeFormatString()
    {
        return 'H:i:s';
    }

    public function modifyLimitQuery($query, $limit, $offset = null)
    {
        if ( ! is_null($offset)) {
            $query .= ' OFFSET ' . $offset;
        }

        if ( ! is_null($limit)) {
            $query .= ' LIMIT ' . $limit;
        }

        return $query;
    }

    /**
     * Gets the SQL snippet used to declare a VARCHAR column type.
     *
     * @param array $field
     */
    abstract public function getVarcharTypeDeclarationSql(array $field);

    /**
     * Gets the name of the platform.
     *
     * @return string
     */
    abstract public function getName();
    
    /**
     * Gets the character casing of a column in an SQL result set of this platform.
     * 
     * @param string $column The column name for which to get the correct character casing.
     * @return string The column name in the character casing used in SQL result sets.
     */
    public function getSqlResultCasing($column)
    {
        return $column;
    }
    
    /**
     * Makes any fixes to a name of a schema element (table, sequence, ...) that are required
     * by restrictions of the platform, like a maximum length.
     * 
     * @param string $schemaName
     * @return string
     */
    public function fixSchemaElementName($schemaElementName)
    {
        return $schemaElementName;
    }

    /**
     * Get the insert sql for an empty insert statement
     *
     * @param string $tableName 
     * @param string $identifierColumnName 
     * @return string $sql
     */
    public function getEmptyIdentityInsertSql($tableName, $identifierColumnName)
    {
        return 'INSERT INTO ' . $tableName . ' (' . $identifierColumnName . ') VALUES (null)';
    }
}