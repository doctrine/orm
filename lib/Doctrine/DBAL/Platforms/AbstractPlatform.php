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

use Doctrine\DBAL\Connection;

/**
 * Base class for all DatabasePlatforms. The DatabasePlatforms are the central
 * point of abstraction of platform-specific behaviors, features and SQL dialects.
 * They are a passive source of information.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 * @author Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 */
abstract class AbstractPlatform
{
    protected $_quoteIdentifiers = false;
    
    protected $valid_default_values = array(
        'text'      => '',
        'boolean'   => true,
        'integer'   => 0,
        'decimal'   => 0.0,
        'float'     => 0.0,
        'timestamp' => '1970-01-01 00:00:00',
        'time'      => '00:00:00',
        'date'      => '1970-01-01',
        'clob'      => '',
        'blob'      => '',
        'string'    => ''
    );
    
    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Sets whether to quote identifiers.
     */
    public function setQuoteIdentifiers($bool)
    {
        $this->_quoteIdentifiers = (bool)$bool;
    }

    /**
     * Gets whether to quote identifiers.
     *
     * @return boolean
     */
    public function getQuoteIdentifiers()
    {
        return $this->_quoteIdentifiers;
    }
    
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
     * Gets the string portion that starts an SQL comment.
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
        throw \Doctrine\Common\DoctrineException::updateMe('Regular expression operator is not supported by this database driver.');
    }

    /**
     * Returns the average value of a column
     *
     * @param string $column    the column to use
     * @return string           generated sql including an AVG aggregate function
     */
    public function getAvgExpression($column)
    {
        $column = $this->getIdentifier($column);
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
        $column = $this->getIdentifier($column);
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
        $column = $this->getIdentifier($column);
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
        $column = $this->getIdentifier($column);
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
        $column = $this->getIdentifier($column);
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
        $column = $this->getIdentifier($column);
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
        $column = $this->getIdentifier($column);
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
        $column = $this->getIdentifier($column);

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
        $expression1 = $this->getIdentifier($expression1);
        $expression2 = $this->getIdentifier($expression2);
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
     * soundex
     * Returns a string to call a function to compute the
     * soundex encoding of a string
     *
     * The string "?000" is returned if the argument is NULL.
     *
     * @param string $value
     * @return string   SQL soundex function with given parameter
     */
    public function getSoundexExpression($value)
    {
        throw \Doctrine\Common\DoctrineException::updateMe('SQL soundex function not supported by this driver.');
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
        $value = $this->getIdentifier($value);
        if ($len === null)
            return 'SUBSTRING(' . $value . ' FROM ' . $from . ')';
        else {
            $len = $this->getIdentifier($len);
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
        $args = func_get_args();

        return join(' || ' , $args);
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
        $expression = $this->getIdentifier($expression);
        return 'NOT(' . $expression . ')';
    }

    /**
     * Returns the SQL to perform the same mathematical operation over an array
     * of values or expressions.
     *
     * basicMath() accepts an arbitrary number of parameters. Each parameter
     * must contain a value or an expression or an array with values or
     * expressions.
     *
     * @param string $type the type of operation, can be '+', '-', '*' or '/'.
     * @param string|array(string)
     * @return string an expression
     */
    private function getBasicMathExpression($type, array $args)
    {
        $elements = $this->getIdentifiers($args);
        if (count($elements) < 1) {
            return '';
        }
        if (count($elements) == 1) {
            return $elements[0];
        } else {
            return '(' . implode(' ' . $type . ' ', $elements) . ')';
        }
    }

    /**
     * Returns the SQL to add values or expressions together.
     *
     * add() accepts an arbitrary number of parameters. Each parameter
     * must contain a value or an expression or an array with values or
     * expressions.
     *
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $e = $q->expr;
     *
     * $q->select('u.*')
     *   ->from('User u')
     *   ->where($e->eq($e->add('id', 2), 12));
     * </code>
     *
     * @param string|array(string)
     * @return string an expression
     */
    public function getAddExpression(array $args)
    {
        return $this->basicMath('+', $args);
    }

    /**
     * Returns the SQL to subtract values or expressions from eachother.
     *
     * subtract() accepts an arbitrary number of parameters. Each parameter
     * must contain a value or an expression or an array with values or
     * expressions.
     *
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $e = $q->expr;
     *
     * $q->select('u.*')
     *   ->from('User u')
     *   ->where($e->eq($e->sub('id', 2), 12));
     * </code>
     *
     * @param string|array(string)
     * @return string an expression
     */
    public function getSubExpression(array $args)
    {
        return $this->basicMath('-', $args );
    }

    /**
     * Returns the SQL to multiply values or expressions by eachother.
     *
     * multiply() accepts an arbitrary number of parameters. Each parameter
     * must contain a value or an expression or an array with values or
     * expressions.
     *
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $e = $q->expr;
     *
     * $q->select('u.*')
     *   ->from('User u')
     *   ->where($e->eq($e->mul('id', 2), 12));
     * </code>
     *
     * @param string|array(string)
     * @return string an expression
     */
    public function getMulExpression(array $args)
    {
        return $this->basicMath('*', $args);
    }

    /**
     * Returns the SQL to divide values or expressions by eachother.
     *
     * divide() accepts an arbitrary number of parameters. Each parameter
     * must contain a value or an expression or an array with values or
     * expressions.
     *
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $e = $q->expr;
     *
     * $q->select('u.*')
     *   ->from('User u')
     *   ->where($e->eq($e->div('id', 2), 12));
     * </code>
     *
     * @param string|array(string)
     * @return string an expression
     */
    public function getDivExpression(array $args)
    {
        return $this->basicMath('/', $args);
    }

    /**
     * Returns the SQL to check if two values are equal.
     *
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $q->select('u.*')
     *   ->from('User u')
     *   ->where($q->expr->eq('id', 1));
     * </code>
     *
     * @param string $value1 logical expression to compare
     * @param string $value2 logical expression to compare with
     * @return string logical expression
     */
    public function getEqExpression($value1, $value2)
    {
        $value1 = $this->getIdentifier($value1);
        $value2 = $this->getIdentifier($value2);
        return $value1 . ' = ' . $value2;
    }

    /**
     * Returns the SQL to check if two values are unequal.
     *
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $q->select('u.*')
     *   ->from('User u')
     *   ->where($q->expr->neq('id', 1));
     * </code>
     *
     * @param string $value1 logical expression to compare
     * @param string $value2 logical expression to compare with
     * @return string logical expression
     */
    public function getNeqExpression($value1, $value2)
    {
        $value1 = $this->getIdentifier($value1);
        $value2 = $this->getIdentifier($value2);
        return $value1 . ' <> ' . $value2;
    }

    /**
     * Returns the SQL to check if one value is greater than another value.
     *
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $q->select('u.*')
     *   ->from('User u')
     *   ->where($q->expr->gt('id', 1));
     * </code>
     *
     * @param string $value1 logical expression to compare
     * @param string $value2 logical expression to compare with
     * @return string logical expression
     */
    public function getGtExpression($value1, $value2)
    {
        $value1 = $this->getIdentifier($value1);
        $value2 = $this->getIdentifier($value2);
        return $value1 . ' > ' . $value2;
    }

    /**
     * Returns the SQL to check if one value is greater than or equal to
     * another value.
     *
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $q->select('u.*')
     *   ->from('User u')
     *   ->where($q->expr->gte('id', 1));
     * </code>
     *
     * @param string $value1 logical expression to compare
     * @param string $value2 logical expression to compare with
     * @return string logical expression
     */
    public function getGteExpression($value1, $value2)
    {
        $value1 = $this->getIdentifier($value1);
        $value2 = $this->getIdentifier($value2);
        return $value1 . ' >= ' . $value2;
    }

    /**
     * Returns the SQL to check if one value is less than another value.
     *
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $q->select('u.*')
     *   ->from('User u')
     *   ->where($q->expr->lt('id', 1));
     * </code>
     *
     * @param string $value1        logical expression to compare
     * @param string $value2        logical expression to compare with
     * @return string logical expression
     */
    public function getLtExpression($value1, $value2)
    {
        $value1 = $this->getIdentifier($value1);
        $value2 = $this->getIdentifier($value2);
        return $value1 . ' < ' . $value2;
    }

    /**
     * Returns the SQL to check if one value is less than or equal to
     * another value.
     *
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $q->select('u.*')
     *   ->from('User u')
     *   ->where($q->expr->lte('id', 1));
     * </code>
     *
     * @param string $value1        logical expression to compare
     * @param string $value2        logical expression to compare with
     * @return string logical expression
     */
    public function getLteExpression($value1, $value2)
    {
        $value1 = $this->getIdentifier($value1);
        $value2 = $this->getIdentifier($value2);
        return $value1 . ' <= ' . $value2;
    }

    /**
     * Returns the SQL to check if a value is one in a set of
     * given values..
     *
     * in() accepts an arbitrary number of parameters. The first parameter
     * must always specify the value that should be matched against. Successive
     * must contain a logical expression or an array with logical expressions.
     * These expressions will be matched against the first parameter.
     *
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $q->select('u.*')
     *   ->from('User u')
     *   ->where($q->expr->in( 'id', array(1,2,3)));
     * </code>
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
        $column = $this->getIdentifier($column);

        if (count($values) == 0) {
            throw \Doctrine\Common\DoctrineException::updateMe('Values array for IN operator should not be empty.');
        }
        return $column . ' IN (' . implode(', ', $values) . ')';
    }

    /**
     * Returns SQL that checks if a expression is null.
     *
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $q->select('u.*')
     *   ->from('User u')
     *   ->where($q->expr->isNull('id'));
     * </code>
     *
     * @param string $expression the expression that should be compared to null
     * @return string logical expression
     */
    public function getIsNullExpression($expression)
    {
        $expression = $this->getIdentifier($expression);
        return $expression . ' IS NULL';
    }

    /**
     * Returns SQL that checks if a expression is not null.
     *
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $q->select('u.*')
     *   ->from('User u')
     *   ->where($q->expr->isNotNull('id'));
     * </code>
     *
     * @param string $expression the expression that should be compared to null
     * @return string logical expression
     */
    public function getIsNotNullExpression($expression)
    {
        $expression = $this->getIdentifier($expression);
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
     * Example:
     * <code>
     * $q = new Doctrine_Query();
     * $q->select('u.*')
     *   ->from('User u')
     *   ->where($q->expr->between('id', 1, 5));
     * </code>
     *
     * @param string $expression the value to compare to
     * @param string $value1 the lower value to compare with
     * @param string $value2 the higher value to compare with
     * @return string logical expression
     */
    public function getBetweenExpression($expression, $value1, $value2)
    {
        $expression = $this->getIdentifier($expression);
        $value1 = $this->getIdentifier($value1);
        $value2 = $this->getIdentifier($value2);
        return $expression . ' BETWEEN ' .$value1 . ' AND ' . $value2;
    }

    /**
     * Returns global unique identifier
     *
     * @return string to get global unique identifier
     */
    public function getGuidExpression()
    {
        throw \Doctrine\Common\DoctrineException::updateMe('method not implemented');
    }

    /**
     * returns arcus cosine SQL string
     *
     * @return string
     */
    public function getAcosExpression($value)
    {
        return 'ACOS(' . $value . ')';
    }

    /**
     * sin
     *
     * @param string $value
     * @return void
     */
    public function getSinExpression($value)
    {
        return 'SIN(' . $value . ')';
    }

    /**
     * pi
     *
     * @return void
     */
    public function getPiExpression()
    {
        return 'PI()';
    }

    /**
     * cos
     *
     * @param string $value
     * @return void
     * @author Jonathan H. Wage
     */
    public function getCosExpression($value)
    {
        return 'COS(' . $value . ')';
    }
    
    /**
     * Enter description here...
     *
     * @return string
     */
    public function getForUpdateSql()
    {
        return 'FOR UPDATE';
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getListDatabasesSql()
    {
        throw \Doctrine\Common\DoctrineException::updateMe('List databases not supported by this driver.');
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getListFunctionsSql()
    {
        throw \Doctrine\Common\DoctrineException::updateMe('List functions not supported by this driver.');
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getListTriggersSql()
    {
        throw \Doctrine\Common\DoctrineException::updateMe('List triggers not supported by this driver.');
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getListSequencesSql()
    {
        throw \Doctrine\Common\DoctrineException::updateMe('List sequences not supported by this driver.');
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getListTableConstraintsSql()
    {
        throw \Doctrine\Common\DoctrineException::updateMe('List table constraints not supported by this driver.');
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getListTableColumnsSql()
    {
        throw \Doctrine\Common\DoctrineException::updateMe('List table columns not supported by this driver.');
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getListTablesSql()
    {
        throw \Doctrine\Common\DoctrineException::updateMe('List tables not supported by this driver.');
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getListUsersSql()
    {
        throw \Doctrine\Common\DoctrineException::updateMe('List users not supported by this driver.');
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getListViewsSql()
    {
        throw \Doctrine\Common\DoctrineException::updateMe('List views not supported by this driver.');
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getDropDatabaseSql($database)
    {
        return 'DROP DATABASE ' . $database;
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getDropTableSql($table)
    {
        return 'DROP TABLE ' . $table;
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getDropIndexSql($index, $name)
    {
        return 'DROP INDEX ' . $index;
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getDropSequenceSql()
    {
        throw \Doctrine\Common\DoctrineException::updateMe('Drop sequence not supported by this driver.');
    }
    
    /**
     * Gets the SQL for acquiring the next value from a sequence.
     */
    public function getSequenceNextValSql($sequenceName)
    {
        throw \Doctrine\Common\DoctrineException::updateMe('Sequences not supported by this driver.');
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getCreateDatabaseSql($database)
    {
        throw \Doctrine\Common\DoctrineException::updateMe('Create database not supported by this driver.');
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getCreateTableSql($table, array $columns, array $options = array())
    {
        if ( ! $table) {
            throw \Doctrine\Common\DoctrineException::updateMe('no valid table name specified');
        }
        if (empty($columns)) {
            throw \Doctrine\Common\DoctrineException::updateMe('no fields specified for table ' . $name);
        }

        $queryFields = $this->getFieldDeclarationListSql($columns);

        if (isset($options['primary']) && ! empty($options['primary'])) {
            $queryFields .= ', PRIMARY KEY(' . implode(', ', array_values($options['primary'])) . ')';
        }

        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach($options['indexes'] as $index => $definition) {
                $queryFields .= ', ' . $this->getIndexDeclaration($index, $definition);
            }
        }

        $query = 'CREATE TABLE ' . $this->quoteIdentifier($table, true) . ' (' . $queryFields;
        
        /*$check = $this->getCheckDeclaration($columns);

        if ( ! empty($check)) {
            $query .= ', ' . $check;
        }*/

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
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     */
    public function getCreateSequenceSql($sequenceName, $start = 1, array $options)
    {
        throw \Doctrine\Common\DoctrineException::updateMe('Create sequence not supported by this driver.');
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
     * Get the stucture of a field into an array
     *
     * @param string    $table         name of the table on which the index is to be created
     * @param string    $name          name of the index to be created
     * @param array     $definition    associative array that defines properties of the index to be created.
     * @see Doctrine_Export::createIndex()
     * @return string
     */
    public function getCreateIndexSql($table, $name, array $definition)
    {
        $type = '';
        if (isset($definition['type'])) {
            switch (strtolower($definition['type'])) {
                case 'unique':
                    $type = strtoupper($definition['type']) . ' ';
                break;
                default:
                    throw \Doctrine\Common\DoctrineException::updateMe('Unknown index type ' . $definition['type']);
            }
        }

        $query = 'CREATE ' . $type . 'INDEX ' . $name . ' ON ' . $table;

        $fields = array();
        foreach ($definition['fields'] as $field) {
            $fields[] = $field;
        }
        $query .= ' (' . implode(', ', $fields) . ')';

        return $query;
    }
    
    /**
     * Quote a string so it can be safely used as a table or column name.
     *
     * Delimiting style depends on which database driver is being used.
     *
     * NOTE: just because you CAN use delimited identifiers doesn't mean
     * you SHOULD use them.  In general, they end up causing way more
     * problems than they solve.
     *
     * Portability is broken by using the following characters inside
     * delimited identifiers:
     *   + backtick (<kbd>`</kbd>) -- due to MySQL
     *   + double quote (<kbd>"</kbd>) -- due to Oracle
     *   + brackets (<kbd>[</kbd> or <kbd>]</kbd>) -- due to Access
     *
     * Delimited identifiers are known to generally work correctly under
     * the following drivers:
     *   + mssql
     *   + mysql
     *   + mysqli
     *   + oci8
     *   + pgsql
     *   + sqlite
     *
     * InterBase doesn't seem to be able to use delimited identifiers
     * via PHP 4.  They work fine under PHP 5.
     *
     * @param string $str           identifier name to be quoted
     * @param bool $checkOption     check the 'quote_identifier' option
     *
     * @return string               quoted identifier string
     */
    public function quoteIdentifier($str)
    {
        if ( ! $this->_quoteIdentifiers) {
            return $str;
        }

        // quick fix for the identifiers that contain a dot
        if (strpos($str, '.')) {
            $e = explode('.', $str);
            return $this->quoteIdentifier($e[0])
                    . '.'
                    . $this->quoteIdentifier($e[1]);
        }

        $c = $this->getIdentifierQuoteCharacter();
        $str = str_replace($c, $c . $c, $str);

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
        $table = $this->quoteIdentifier($table);
        $query = 'ALTER TABLE ' . $table . ' ADD ' . $this->getForeignKeyDeclarationSql($definition);

        return $query;
    }
    
    /**
     * generates the sql for altering an existing table
     * (this method is implemented by the drivers)
     *
     * @param string $name          name of the table that is intended to be changed.
     * @param array $changes        associative array that contains the details of each type      *
     * @param boolean $check        indicates whether the function should just check if the DBMS driver
     *                              can perform the requested table alterations if the value is true or
     *                              actually perform them otherwise.
     * @see Doctrine_Export::alterTable()
     * @return string
     */
    public function getAlterTableSql($name, array $changes, $check = false)
    {
        throw \Doctrine\Common\DoctrineException::updateMe('Alter table not supported by this driver.');
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
    public function getFieldDeclarationListSql(array $fields)
    {
        $queryFields = array();
        foreach ($fields as $fieldName => $field) {
            $query = $this->getDeclarationSql($fieldName, $field);
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
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     */
    public function getDeclarationSql($name, array $field)
    {
        $default = $this->getDefaultFieldDeclarationSql($field);
        $charset = (isset($field['charset']) && $field['charset']) ?
                ' ' . $this->getCharsetFieldDeclarationSql($field['charset']) : '';
        $collation = (isset($field['collation']) && $field['collation']) ?
                ' ' . $this->getCollationFieldDeclarationSql($field['collation']) : '';
        $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';
        $unique = (isset($field['unique']) && $field['unique']) ?
                ' ' . $this->getUniqueFieldDeclarationSql() : '';
        $check = (isset($field['check']) && $field['check']) ?
                ' ' . $field['check'] : '';

        $typeDecl = $field['type']->getSqlDeclaration($field, $this);
 
        return $this->quoteIdentifier($name, true) . ' ' . $typeDecl . $charset . $default . $notnull . $unique . $check . $collation;
    }

    /**
     *
     * @param <type> $name
     * @param <type> $field
     */
    abstract public function getIntegerTypeDeclarationSql(array $columnDef);

    /**
     * Gets the SQL snippet that declares a BIGINT column.
     *
     * @return string
     */
    abstract public function getBigIntTypeDeclarationSql(array $columnDef);

    /**
     * Gets the SQL snippet that declares a TINYINT column.
     *
     * @return string
     */
    abstract public function getTinyIntTypeDeclarationSql(array $columnDef);

    /**
     * Gets the SQL snippet that declares a SMALLINT column.
     *
     * @return string
     */
    abstract public function getSmallIntTypeDeclarationSql(array $columnDef);

    /**
     * Gets the SQL snippet that declares a MEDIUMINT column.
     *
     * @return string
     */
    abstract public function getMediumIntTypeDeclarationSql(array $columnDef);

    /**
     * Gets the SQL snippet that declares common properties of an integer column.
     *
     * @return string
     */
    abstract protected function _getCommonIntegerTypeDeclarationSql(array $columnDef);

    /**
     * getDefaultDeclaration
     * Obtain DBMS specific SQL code portion needed to set a default value
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param array $field      field definition array
     * @return string           DBMS specific SQL code portion needed to set a default value
     */
    public function getDefaultFieldDeclarationSql($field)
    {
        $default = '';
        if (isset($field['default'])) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull'])
                    ? null : $this->valid_default_values[$field['type']];

                if ($field['default'] === '' &&
                   ($this->_conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_EMPTY_TO_NULL)) {
                    $field['default'] = null;
                }
            }

            if ($field['type'] === 'boolean') {
                $field['default'] = $this->convertBooleans($field['default']);
            }
            $default = ' DEFAULT ' . $this->quote($field['default'], $field['type']);
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
        $name   = $this->quoteIdentifier($name);
        $type   = '';

        if (isset($definition['type'])) {
            if (strtolower($definition['type']) == 'unique') {
                $type = strtoupper($definition['type']) . ' ';
            } else {
                throw \Doctrine\Common\DoctrineException::updateMe('Unknown index type ' . $definition['type']);
            }
        }

        if ( ! isset($definition['fields']) || ! is_array($definition['fields'])) {
            throw \Doctrine\Common\DoctrineException::updateMe('No index columns given.');
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
                $ret[] = $this->quoteIdentifier($field);
            } else {
                $ret[] = $this->quoteIdentifier($definition);
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
     * Enter description here...
     *
     * @return unknown
     */
    public function getShowDatabasesSql()
    {
        throw \Doctrine\Common\DoctrineException::updateMe('Show databases not supported by this driver.');
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
     * getAdvancedForeignKeyOptions
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
                throw \Doctrine\Common\DoctrineException::updateMe('Unknown foreign key referential action \'' . $upper . '\' given.');
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
            $sql .= ' CONSTRAINT ' . $this->quoteIdentifier($definition['name']) . ' ';
        }
        $sql .= 'FOREIGN KEY (';

        if ( ! isset($definition['local'])) {
            throw \Doctrine\Common\DoctrineException::updateMe('Local reference field missing from definition.');
        }
        if ( ! isset($definition['foreign'])) {
            throw \Doctrine\Common\DoctrineException::updateMe('Foreign reference field missing from definition.');
        }
        if ( ! isset($definition['foreignTable'])) {
            throw \Doctrine\Common\DoctrineException::updateMe('Foreign reference table missing from definition.');
        }

        if ( ! is_array($definition['local'])) {
            $definition['local'] = array($definition['local']);
        }
        if ( ! is_array($definition['foreign'])) {
            $definition['foreign'] = array($definition['foreign']);
        }

        $sql .= implode(', ', array_map(array($this, 'quoteIdentifier'), $definition['local']))
              . ') REFERENCES '
              . $this->quoteIdentifier($definition['foreignTable']) . '('
              . implode(', ', array_map(array($this, 'quoteIdentifier'), $definition['foreign'])) . ')';

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
    public function getCharsetFieldDeclarationSql($charset)
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
    public function getCollationFieldDeclarationSql($collation)
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
        throw \Doctrine\Common\DoctrineException::updateMe("Method not implemented.");
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
     */
    abstract public function getNativeDeclaration(array $field);
    
    /**
     * Maps a native array description of a field to a Doctrine datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     */
    abstract public function getPortableDeclaration(array $field);
    
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
     * Creates DBMS specific LIMIT/OFFSET SQL for the subqueries that are used in the
     * context of the limit-subquery construction.
     * This default implementation uses the normal LIMIT/OFFSET creation of the
     * platform as provided by {@see modifyLimitQuery()}. This means LIMIT/OFFSET
     * in subqueries don't get any special treatment. Most of the time this is not
     * sufficient (eg. MySql does not allow LIMIT in subqueries) and the concrete
     * platforms should provide their own implementation.
     *
     * @param string $query  The SQL string to write to / append to.
     * @return string
     * @todo Remove the ORM dependency
     */
    public function writeLimitClauseInSubquery(\Doctrine\ORM\Mapping\ClassMetadata $rootClass,
            $query, $limit = false, $offset = false)
    {
        return $this->modifyLimitQuery($query, $limit, $offset);
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $name
     * @return unknown
     * @todo Remove. Move properties to DatabasePlatform.
     */
    public function getProperty($name)
    {
        if ( ! isset($this->_properties[$name])) {
            throw DoctrineException::unknownProperty($name);
        }
        return $this->_properties[$name];
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
        } else {
            if (is_bool($item)) {
                $item = (int) $item;
            }
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
     * Enter description here...
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
                throw new Doctrine_Common_Exceptions_DoctrineException('isolation level is not supported: ' . $isolation);
        } 
    }
    
    /**
     * Enter description here...
     *
     * @param integer $level
     */
    public function getSetTransactionIsolationSql($level)
    {
        throw new DoctrineException('Set transaction isolation not supported by this platform.');
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
     * Whether the platform supports getting the affected rows or a recent
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
     * Gets the SQL snippet used to declare a VARCHAR column on the MySql platform.
     *
     * @params array $field
     */
    abstract public function getVarcharDeclarationSql(array $field);
}


