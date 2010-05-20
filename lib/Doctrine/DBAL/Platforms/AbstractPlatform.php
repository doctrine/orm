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

use Doctrine\DBAL\DBALException,
    Doctrine\DBAL\Connection,
    Doctrine\DBAL\Types,
    Doctrine\DBAL\Schema\Table,
    Doctrine\DBAL\Schema\Index,
    Doctrine\DBAL\Schema\ForeignKeyConstraint,
    Doctrine\DBAL\Schema\TableDiff;

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
 * @todo Remove any unnecessary methods.
 */
abstract class AbstractPlatform
{
    /**
     * @var int
     */
    const CREATE_INDEXES = 1;

    /**
     * @var int
     */
    const CREATE_FOREIGNKEYS = 2;

    /**
     * @var int
     */
    const TRIM_UNSPECIFIED = 0;

    /**
     * @var int
     */
    const TRIM_LEADING = 1;

    /**
     * @var int
     */
    const TRIM_TRAILING = 2;

    /**
     * @var int
     */
    const TRIM_BOTH = 3;

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
        throw DBALException::notSupported(__METHOD__);
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
     * Trim a string, leading/trailing/both and with a given char which defaults to space.
     *
     * @param string $str
     * @param int $pos
     * @param string $char has to be quoted already
     * @return string
     */
    public function getTrimExpression($str, $pos = self::TRIM_UNSPECIFIED, $char = false)
    {
        $posStr = '';
        $trimChar = ($char != false) ? $char . ' FROM ' : '';
        
        if ($pos == self::TRIM_LEADING) {
            $posStr = 'LEADING '.$trimChar;
        } else if($pos == self::TRIM_TRAILING) {
            $posStr = 'TRAILING '.$trimChar;
        } else if($pos == self::TRIM_BOTH) {
            $posStr = 'BOTH '.$trimChar;
        }

        return 'TRIM(' . $posStr . $str . ')';
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
     * returns the position of the first occurrence of substring $substr in string $str
     *
     * @param string $substr    literal string to find
     * @param string $str       literal string
     * @param int    $pos       position to start at, beginning of string by default
     * @return integer
     */
    public function getLocateExpression($str, $substr, $startPos = false)
    {
        throw DBALException::notSupported(__METHOD__);
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
     * @param  string $value         an sql string literal or column name/alias
     * @param  integer $from     where to start the substring portion
     * @param  integer $len       the substring portion length
     * @return string
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
            throw \InvalidArgumentException('Values must not be empty.');
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

    public function getDropDatabaseSQL($database)
    {
        return 'DROP DATABASE ' . $database;
    }

    /**
     * Drop a Table
     * 
     * @param  Table|string $table
     * @return string
     */
    public function getDropTableSQL($table)
    {
        if ($table instanceof \Doctrine\DBAL\Schema\Table) {
            $table = $table->getName();
        }

        return 'DROP TABLE ' . $table;
    }

    /**
     * Drop index from a table
     *
     * @param Index|string $name
     * @param string|Table $table
     * @return string
     */
    public function getDropIndexSQL($index, $table=null)
    {
        if($index instanceof \Doctrine\DBAL\Schema\Index) {
            $index = $index->getName();
        } else if(!is_string($index)) {
            throw new \InvalidArgumentException('AbstractPlatform::getDropIndexSQL() expects $index parameter to be string or \Doctrine\DBAL\Schema\Index.');
        }

        return 'DROP INDEX ' . $index;
    }

    /**
     * Get drop constraint sql
     * 
     * @param  \Doctrine\DBAL\Schema\Constraint $constraint
     * @param  string|Table $table
     * @return string
     */
    public function getDropConstraintSQL($constraint, $table)
    {
        if ($constraint instanceof \Doctrine\DBAL\Schema\Constraint) {
            $constraint = $constraint->getName();
        }

        if ($table instanceof \Doctrine\DBAL\Schema\Table) {
            $table = $table->getName();
        }

        return 'ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $constraint;
    }

    /**
     * @param  ForeignKeyConstraint|string $foreignKey
     * @param  Table|string $table
     * @return string
     */
    public function getDropForeignKeySQL($foreignKey, $table)
    {
        if ($foreignKey instanceof \Doctrine\DBAL\Schema\ForeignKeyConstraint) {
            $foreignKey = $foreignKey->getName();
        }

        if ($table instanceof \Doctrine\DBAL\Schema\Table) {
            $table = $table->getName();
        }

        return 'ALTER TABLE ' . $table . ' DROP FOREIGN KEY ' . $foreignKey;
    }

    /**
     * Gets the SQL statement(s) to create a table with the specified name, columns and constraints
     * on this platform.
     *
     * @param string $table The name of the table.
     * @param int $createFlags
     * @return array The sequence of SQL statements.
     */
    public function getCreateTableSQL(Table $table, $createFlags=self::CREATE_INDEXES)
    {
        if ( ! is_int($createFlags)) {
            throw new \InvalidArgumentException("Second argument of AbstractPlatform::getCreateTableSQL() has to be integer.");
        }

        if (count($table->getColumns()) == 0) {
            throw DBALException::noColumnsSpecifiedForTable($table->getName());
        }

        $tableName = $table->getName();
        $options = $table->getOptions();
        $options['uniqueConstraints'] = array();
        $options['indexes'] = array();
        $options['primary'] = array();

        if (($createFlags&self::CREATE_INDEXES) > 0) {
            foreach ($table->getIndexes() AS $index) {
                /* @var $index Index */
                if ($index->isPrimary()) {
                    $options['primary'] = $index->getColumns();
                } else {
                    $options['indexes'][$index->getName()] = $index;
                }
            }
        }

        $columns = array();
        foreach ($table->getColumns() AS $column) {
            /* @var \Doctrine\DBAL\Schema\Column $column */
            $columnData = array();
            $columnData['name'] = $column->getName();
            $columnData['type'] = $column->getType();
            $columnData['length'] = $column->getLength();
            $columnData['notnull'] = $column->getNotNull();
            $columnData['unique'] = ($column->hasPlatformOption("unique"))?$column->getPlatformOption('unique'):false;
            $columnData['version'] = ($column->hasPlatformOption("version"))?$column->getPlatformOption('version'):false;
            if(strtolower($columnData['type']) == "string" && $columnData['length'] === null) {
                $columnData['length'] = 255;
            }
            $columnData['precision'] = $column->getPrecision();
            $columnData['scale'] = $column->getScale();
            $columnData['default'] = $column->getDefault();
            $columnData['columnDefinition'] = $column->getColumnDefinition();

            if(in_array($column->getName(), $options['primary'])) {
                $columnData['primary'] = true;

                if($table->isIdGeneratorIdentity()) {
                    $columnData['autoincrement'] = true;
                }
            }

            $columns[$columnData['name']] = $columnData;
        }

        if (($createFlags&self::CREATE_FOREIGNKEYS) > 0) {
            $options['foreignKeys'] = array();
            foreach ($table->getForeignKeys() AS $fkConstraint) {
                $options['foreignKeys'][] = $fkConstraint;
            }
        }

        return $this->_getCreateTableSQL($tableName, $columns, $options);
    }

    /**
     * @param string $tableName
     * @param array $columns
     * @param array $options
     * @return array
     */
    protected function _getCreateTableSQL($tableName, array $columns, array $options = array())
    {
        $columnListSql = $this->getColumnDeclarationListSQL($columns);
        
        if (isset($options['uniqueConstraints']) && ! empty($options['uniqueConstraints'])) {
            foreach ($options['uniqueConstraints'] as $name => $definition) {
                $columnListSql .= ', ' . $this->getUniqueConstraintDeclarationSQL($name, $definition);
            }
        }
        
        if (isset($options['primary']) && ! empty($options['primary'])) {
            $columnListSql .= ', PRIMARY KEY(' . implode(', ', array_unique(array_values($options['primary']))) . ')';
        }

        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach($options['indexes'] as $index => $definition) {
                $columnListSql .= ', ' . $this->getIndexDeclarationSQL($index, $definition);
            }
        }

        $query = 'CREATE TABLE ' . $tableName . ' (' . $columnListSql;

        $check = $this->getCheckDeclarationSQL($columns);
        if ( ! empty($check)) {
            $query .= ', ' . $check;
        }
        $query .= ')';

        $sql[] = $query;

        if (isset($options['foreignKeys'])) {
            foreach ((array) $options['foreignKeys'] AS $definition) {
                $sql[] = $this->getCreateForeignKeySQL($definition, $tableName);
            }
        }

        return $sql;
    }
    
    public function getCreateTemporaryTableSnippetSQL()
    {
        return "CREATE TEMPORARY TABLE";
    }

    /**
     * Gets the SQL to create a sequence on this platform.
     *
     * @param \Doctrine\DBAL\Schema\Sequence $sequence
     * @throws DBALException
     */
    public function getCreateSequenceSQL(\Doctrine\DBAL\Schema\Sequence $sequence)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * Gets the SQL to create a constraint on a table on this platform.
     *
     * @param Constraint $constraint
     * @param string|Table $table
     * @return string
     */
    public function getCreateConstraintSQL(\Doctrine\DBAL\Schema\Constraint $constraint, $table)
    {
        if ($table instanceof \Doctrine\DBAL\Schema\Table) {
            $table = $table->getName();
        }

        $query = 'ALTER TABLE ' . $table . ' ADD CONSTRAINT ' . $constraint->getName();

        $columns = array();
        foreach ($constraint->getColumns() as $column) {
            $columns[] = $column;
        }
        $columnList = '('. implode(', ', $columns) . ')';

        $referencesClause = '';
        if ($constraint instanceof \Doctrine\DBAL\Schema\Index) {
            if($constraint->isPrimary()) {
                $query .= ' PRIMARY KEY';
            } elseif ($constraint->isUnique()) {
                $query .= ' UNIQUE';
            } else {
                throw new \InvalidArgumentException(
                    'Can only create primary or unique constraints, no common indexes with getCreateConstraintSQL().'
                );
            }
        } else if ($constraint instanceof \Doctrine\DBAL\Schema\ForeignKeyConstraint) {
            $query .= ' FOREIGN KEY';

            $foreignColumns = array();
            foreach ($constraint->getForeignColumns() AS $column) {
                $foreignColumns[] = $column;
            }
            
            $referencesClause = ' REFERENCES '.$constraint->getForeignTableName(). ' ('.implode(', ', $foreignColumns).')';
        }
        $query .= ' '.$columnList.$referencesClause;

        return $query;
    }

    /**
     * Gets the SQL to create an index on a table on this platform.
     *
     * @param Index $index
     * @param string|Table $table name of the table on which the index is to be created
     * @return string
     */
    public function getCreateIndexSQL(Index $index, $table)
    {
        if ($table instanceof Table) {
            $table = $table->getName();
        }
        $name = $index->getName();
        $columns = $index->getColumns();

        if (count($columns) == 0) {
            throw new \InvalidArgumentException("Incomplete definition. 'columns' required.");
        }

        $type = '';
        if ($index->isUnique()) {
            $type = 'UNIQUE ';
        }

        $query = 'CREATE ' . $type . 'INDEX ' . $name . ' ON ' . $table;

        $query .= ' (' . $this->getIndexFieldDeclarationListSQL($columns) . ')';

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
     * Create a new foreign key
     *
     * @param ForeignKeyConstraint  $foreignKey    ForeignKey instance
     * @param string|Table          $table         name of the table on which the foreign key is to be created
     * @return string
     */
    public function getCreateForeignKeySQL(ForeignKeyConstraint $foreignKey, $table)
    {
        if ($table instanceof \Doctrine\DBAL\Schema\Table) {
            $table = $table->getName();
        }

        $query = 'ALTER TABLE ' . $table . ' ADD ' . $this->getForeignKeyDeclarationSQL($foreignKey);

        return $query;
    }

    /**
     * Gets the sql statements for altering an existing table.
     *
     * The method returns an array of sql statements, since some platforms need several statements.
     *
     * @param TableDiff $diff
     * @return array
     */
    public function getAlterTableSQL(TableDiff $diff)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * Common code for alter table statement generation that updates the changed Index and Foreign Key definitions.
     *
     * @param TableDiff $diff
     * @return array
     */
    protected function _getAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        if ($diff->newName !== false) {
            $tableName = $diff->newName;
        } else {
            $tableName = $diff->name;
        }

        $sql = array();
        if ($this->supportsForeignKeyConstraints()) {
            foreach ($diff->removedForeignKeys AS $foreignKey) {
                $sql[] = $this->getDropForeignKeySQL($foreignKey, $tableName);
            }
            foreach ($diff->addedForeignKeys AS $foreignKey) {
                $sql[] = $this->getCreateForeignKeySQL($foreignKey, $tableName);
            }
            foreach ($diff->changedForeignKeys AS $foreignKey) {
                $sql[] = $this->getDropForeignKeySQL($foreignKey, $tableName);
                $sql[] = $this->getCreateForeignKeySQL($foreignKey, $tableName);
            }
        }

        foreach ($diff->addedIndexes AS $index) {
            $sql[] = $this->getCreateIndexSQL($index, $tableName);
        }
        foreach ($diff->removedIndexes AS $index) {
            $sql[] = $this->getDropIndexSQL($index, $tableName);
        }
        foreach ($diff->changedIndexes AS $index) {
            $sql[] = $this->getDropIndexSQL($index, $tableName);
            $sql[] = $this->getCreateIndexSQL($index, $tableName);
        }

        return $sql;
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
    public function getColumnDeclarationListSQL(array $fields)
    {
        $queryFields = array();
        foreach ($fields as $fieldName => $field) {
            $query = $this->getColumnDeclarationSQL($fieldName, $field);
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
     *      columnDefinition
     *          a string that defines the complete column
     *
     * @return string  DBMS specific SQL code portion that should be used to declare the column.
     */
    public function getColumnDeclarationSQL($name, array $field)
    {
        if (isset($field['columnDefinition'])) {
            $columnDef = $this->getCustomTypeDeclarationSQL($field);
        } else {
            $default = $this->getDefaultValueDeclarationSQL($field);

            $charset = (isset($field['charset']) && $field['charset']) ?
                    ' ' . $this->getColumnCharsetDeclarationSQL($field['charset']) : '';

            $collation = (isset($field['collation']) && $field['collation']) ?
                    ' ' . $this->getColumnCollationDeclarationSQL($field['collation']) : '';

            $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';

            $unique = (isset($field['unique']) && $field['unique']) ?
                    ' ' . $this->getUniqueFieldDeclarationSQL() : '';

            $check = (isset($field['check']) && $field['check']) ?
                    ' ' . $field['check'] : '';

            $typeDecl = $field['type']->getSqlDeclaration($field, $this);
            $columnDef = $typeDecl . $charset . $default . $notnull . $unique . $check . $collation;
        }

        return $name . ' ' . $columnDef;
    }
    
    /**
     * Gets the SQL snippet that declares a floating point column of arbitrary precision.
     *
     * @param array $columnDef
     * @return string
     */
    public function getDecimalTypeDeclarationSQL(array $columnDef) 
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
    abstract public function getBooleanTypeDeclarationSQL(array $columnDef);

    /**
     * Gets the SQL snippet that declares a 4 byte integer column.
     *
     * @param array $columnDef
     * @return string
     */
    abstract public function getIntegerTypeDeclarationSQL(array $columnDef);

    /**
     * Gets the SQL snippet that declares an 8 byte integer column.
     *
     * @param array $columnDef
     * @return string
     */
    abstract public function getBigIntTypeDeclarationSQL(array $columnDef);

    /**
     * Gets the SQL snippet that declares a 2 byte integer column.
     *
     * @param array $columnDef
     * @return string
     */
    abstract public function getSmallIntTypeDeclarationSQL(array $columnDef);

    /**
     * Gets the SQL snippet that declares common properties of an integer column.
     *
     * @param array $columnDef
     * @return string
     */
    abstract protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef);

    /**
     * Obtain DBMS specific SQL code portion needed to set a default value
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param array $field      field definition array
     * @return string           DBMS specific SQL code portion needed to set a default value
     */
    public function getDefaultValueDeclarationSQL($field)
    {
        $default = empty($field['notnull']) ? ' DEFAULT NULL' : '';

        if (isset($field['default'])) {
            $default = " DEFAULT '".$field['default']."'";
            if (isset($field['type'])) {
                if (in_array((string)$field['type'], array("Integer", "BigInteger", "SmallInteger"))) {
                    $default = " DEFAULT ".$field['default'];
                } else if ((string)$field['type'] == 'DateTime' && $field['default'] == $this->getCurrentTimestampSQL()) {
                    $default = " DEFAULT ".$this->getCurrentTimestampSQL();
                }
            }
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
    public function getCheckDeclarationSQL(array $definition)
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
     * Obtain DBMS specific SQL code portion needed to set a unique
     * constraint declaration to be used in statements like CREATE TABLE.
     *
     * @param string $name          name of the unique constraint
     * @param Index $index          index definition
     * @return string               DBMS specific SQL code portion needed 
     *                              to set a constraint
     */
    public function getUniqueConstraintDeclarationSQL($name, Index $index)
    {
        if (count($index->getColumns()) == 0) {
            throw \InvalidArgumentException("Incomplete definition. 'columns' required.");
        }
        
        return 'CONSTRAINT ' . $name . ' UNIQUE ('
             . $this->getIndexFieldDeclarationListSQL($index->getColumns()) 
             . ')';
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set an index
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param string $name          name of the index
     * @param Index $index          index definition
     * @return string               DBMS specific SQL code portion needed to set an index
     */
    public function getIndexDeclarationSQL($name, Index $index)
    {
        $type = '';

        if($index->isUnique()) {
            $type = 'UNIQUE ';
        }

        if (count($index->getColumns()) == 0) {
            throw \InvalidArgumentException("Incomplete definition. 'columns' required.");
        }

        return $type . 'INDEX ' . $name . ' ('
             . $this->getIndexFieldDeclarationListSQL($index->getColumns()) 
             . ')';
    }

    /**
     * getCustomTypeDeclarationSql
     * Obtail SQL code portion needed to create a custom column,
     * e.g. when a field has the "columnDefinition" keyword.
     * Only "AUTOINCREMENT" and "PRIMARY KEY" are added if appropriate.
     *
     * @return string
     */
    public function getCustomTypeDeclarationSQL(array $columnDef)
    {
        return $columnDef['columnDefinition'];
    }

    /**
     * getIndexFieldDeclarationList
     * Obtain DBMS specific SQL code portion needed to set an index
     * declaration to be used in statements like CREATE TABLE.
     *
     * @return string
     */
    public function getIndexFieldDeclarationListSQL(array $fields)
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
    public function getTemporaryTableSQL()
    {
        return 'TEMPORARY';
    }

    /**
     * Some vendors require temporary table names to be qualified specially.
     *
     * @param  string $tableName
     * @return string
     */
    public function getTemporaryTableName($tableName)
    {
        return $tableName;
    }

    /**
     * Get sql query to show a list of database.
     *
     * @return string
     */
    public function getShowDatabasesSQL()
    {
        throw DBALException::notSupported(__METHOD__);
    }

    /**
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
    public function getForeignKeyDeclarationSQL(ForeignKeyConstraint $foreignKey)
    {
        $sql  = $this->getForeignKeyBaseDeclarationSQL($foreignKey);
        $sql .= $this->getAdvancedForeignKeyOptionsSQL($foreignKey);

        return $sql;
    }

    /**
     * Return the FOREIGN KEY query section dealing with non-standard options
     * as MATCH, INITIALLY DEFERRED, ON UPDATE, ...
     *
     * @param ForeignKeyConstraint $foreignKey     foreign key definition
     * @return string
     */
    public function getAdvancedForeignKeyOptionsSQL(ForeignKeyConstraint $foreignKey)
    {
        $query = '';
        if ($this->supportsForeignKeyOnUpdate() && $foreignKey->hasOption('onUpdate')) {
            $query .= ' ON UPDATE ' . $this->getForeignKeyReferentialActionSQL($foreignKey->getOption('onUpdate'));
        }
        if ($foreignKey->hasOption('onDelete')) {
            $query .= ' ON DELETE ' . $this->getForeignKeyReferentialActionSQL($foreignKey->getOption('onDelete'));
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
    public function getForeignKeyReferentialActionSQL($action)
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
                throw \InvalidArgumentException('Invalid foreign key action: ' . $upper);
        }
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the FOREIGN KEY constraint
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param ForeignKeyConstraint $foreignKey
     * @return string
     */
    public function getForeignKeyBaseDeclarationSQL(ForeignKeyConstraint $foreignKey)
    {
        $sql = '';
        if (strlen($foreignKey->getName())) {
            $sql .= 'CONSTRAINT ' . $foreignKey->getName() . ' ';
        }
        $sql .= 'FOREIGN KEY (';

        if (count($foreignKey->getLocalColumns()) == 0) {
            throw new \InvalidArgumentException("Incomplete definition. 'local' required.");
        }
        if (count($foreignKey->getForeignColumns()) == 0) {
            throw new \InvalidArgumentException("Incomplete definition. 'foreign' required.");
        }
        if (strlen($foreignKey->getForeignTableName()) == 0) {
            throw new \InvalidArgumentException("Incomplete definition. 'foreignTable' required.");
        }

        $sql .= implode(', ', $foreignKey->getLocalColumns())
              . ') REFERENCES '
              . $foreignKey->getForeignTableName() . '('
              . implode(', ', $foreignKey->getForeignColumns()) . ')';

        return $sql;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the UNIQUE constraint
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @return string  DBMS specific SQL code portion needed to set the UNIQUE constraint
     *                 of a field declaration.
     */
    public function getUniqueFieldDeclarationSQL()
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
    public function getColumnCharsetDeclarationSQL($charset)
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
    public function getColumnCollationDeclarationSQL($collation)
    {
        return '';
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
     * Some platforms need the boolean values to be converted.
     * 
     * The default conversion in this implementation converts to integers (false => 0, true => 1).
     *
     * @param mixed $item
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
     * This function is MySQL specific and required by
     * {@see \Doctrine\DBAL\Connection::setCharset($charset)}
     *
     * @param string $charset
     * @return string
     */
    public function getSetCharsetSQL($charset)
    {
        return "SET NAMES '".$charset."'";
    }

    /**
     * Gets the SQL specific for the platform to get the current date.
     *
     * @return string
     */
    public function getCurrentDateSQL()
    {
        return 'CURRENT_DATE';
    }

    /**
     * Gets the SQL specific for the platform to get the current time.
     *
     * @return string
     */
    public function getCurrentTimeSQL()
    {
        return 'CURRENT_TIME';
    }

    /**
     * Gets the SQL specific for the platform to get the current timestamp
     *
     * @return string
     */
    public function getCurrentTimestampSQL()
    {
        return 'CURRENT_TIMESTAMP';
    }

    /**
     * Get sql for transaction isolation level Connection constant
     *
     * @param integer $level
     */
    protected function _getTransactionIsolationLevelSQL($level)
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
                throw new \InvalidArgumentException('Invalid isolation level:' . $level);
        }
    }

    public function getListDatabasesSQL()
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getListSequencesSQL($database)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getListTableConstraintsSQL($table)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getListTableColumnsSQL($table)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getListTablesSQL()
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getListUsersSQL()
    {
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * Get the SQL to list all views of a database or user.
     *
     * @param string $database
     * @return string
     */
    public function getListViewsSQL($database)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getListTableIndexesSQL($table)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getListTableForeignKeysSQL($table)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getCreateViewSQL($name, $sql)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getDropViewSQL($name)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getDropSequenceSQL($sequence)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getSequenceNextValSQL($sequenceName)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getCreateDatabaseSQL($database)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * Get sql to set the transaction isolation level
     *
     * @param integer $level
     */
    public function getSetTransactionIsolationSQL($level)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * Obtain DBMS specific SQL to be used to create datetime fields in 
     * statements like CREATE TABLE
     *
     * @param array $fieldDeclaration 
     * @return string
     */
    public function getDateTimeTypeDeclarationSQL(array $fieldDeclaration)
    {
        throw DBALException::notSupported(__METHOD__);
    }
    
    /**
     * Obtain DBMS specific SQL to be used to create date fields in statements
     * like CREATE TABLE.
     * 
     * @param array $fieldDeclaration
     * @return string
     */
    public function getDateTypeDeclarationSQL(array $fieldDeclaration)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * Obtain DBMS specific SQL to be used to create time fields in statements
     * like CREATE TABLE.
     *
     * @param array $fieldDeclaration
     * @return string
     */
    public function getTimeTypeDeclarationSQL(array $fieldDeclaration)
    {
        throw DBALException::notSupported(__METHOD__);
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

    public function supportsAlterTable()
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
     * Does the platform supports foreign key constraints?
     *
     * @return boolean
     */
    public function supportsForeignKeyConstraints()
    {
        return true;
    }

    /**
     * Does this platform supports onUpdate in foreign key constraints?
     * 
     * @return bool
     */
    public function supportsForeignKeyOnUpdate()
    {
        return ($this->supportsForeignKeyConstraints() && true);
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
     * Some databases don't allow to create and drop databases at all or only with certain tools.
     *
     * @return bool
     */
    public function supportsCreateDropDatabase()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function createsExplicitIndexForForeignKeys()
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

    public function getIdentityColumnNullInsertSQL()
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
        if ( ! is_null($limit)) {
            $query .= ' LIMIT ' . $limit;
        }

        if ( ! is_null($offset)) {
            $query .= ' OFFSET ' . $offset;
        }

        return $query;
    }

    /**
     * Gets the SQL snippet used to declare a VARCHAR column type.
     *
     * @param array $field
     */
    abstract public function getVarcharTypeDeclarationSQL(array $field);
    
    /**
     * Gets the SQL snippet used to declare a CLOB column type.
     *
     * @param array $field
     */
    abstract public function getClobTypeDeclarationSQL(array $field);

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
    public function getSQLResultCasing($column)
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
     * Maximum length of any given databse identifier, like tables or column names.
     * 
     * @return int
     */
    public function getMaxIdentifierLength()
    {
        return 63;
    }

    /**
     * Get the insert sql for an empty insert statement
     *
     * @param string $tableName 
     * @param string $identifierColumnName 
     * @return string $sql
     */
    public function getEmptyIdentityInsertSQL($tableName, $identifierColumnName)
    {
        return 'INSERT INTO ' . $tableName . ' (' . $identifierColumnName . ') VALUES (null)';
    }

    /**
     * Generate a Truncate Table SQL statement for a given table.
     *
     * Cascade is not supported on many platforms but would optionally cascade the truncate by
     * following the foreign keys.
     *
     * @param  string $tableName
     * @param  bool $cascade
     * @return string
     */
    public function getTruncateTableSQL($tableName, $cascade = false)
    {
        return 'TRUNCATE '.$tableName;
    }
}