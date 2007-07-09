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
Doctrine::autoload('Doctrine_Connection_Module');
/**
 * Doctrine_Expression_Driver
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Expression_Driver extends Doctrine_Connection_Module
{
    public function getIdentifier($column)
    {
        return $column;
    }
    public function getIdentifiers($columns)
    {
        return $columns;
    }
    /**
     * regexp
     * returns the regular expression operator
     *
     * @return string
     */
    public function regexp()
    {
        throw new Doctrine_Expression_Exception('Regular expression operator is not supported by this database driver.');
    }
    /**
     * Returns the average value of a column
     *
     * @param string $column    the column to use
     * @return string           generated sql including an AVG aggregate function
     */
    public function avg($column)
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
    public function count($column)
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
    public function max($column)
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
    public function min($column)
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
    public function sum($column)
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
    public function md5($column)
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
    public function length($column)
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
    public function round($column, $decimals = 0)
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
    public function mod($expression1, $expression2)
    {
        $expression1 = $this->getIdentifier($expression1);
        $expression2 = $this->getIdentifier($expression2);
        return 'MOD(' . $expression1 . ', ' . $expression2 . ')';
    }
    /**
     * ltrim
     * returns the string $str with leading space characters removed
     *
     * @param string $str       literal string or column name
     * @return string
     */
    public function ltrim($str)
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
    public function upper($str)
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
    public function lower($str)
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
    public function locate($str, $substr)
    {
        return 'LOCATE(' . $str . ', ' . $substr . ')';
    }
    /**
     * Returns the current system date.
     *
     * @return string
     */
    public function now()
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
    public function soundex($value)
    {
        throw new Doctrine_Expression_Exception('SQL soundex function not supported by this driver.');
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
    public function substring($value, $from, $len = null)
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
     * must contain an expression or an array with expressions.
     *
     * @param string|array(string) strings that will be concatinated.
     */
    public function concat()
    {
    	$args = func_get_args();

        return 'CONCAT(' . join(', ', (array) $args) . ')';
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
    public function not($expression)
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
    private function basicMath($type, array $args)
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
    public function add(array $args)
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
    public function sub(array $args)
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
    public function mul(array $args)
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
    public function div(array $args)
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
    public function eq($value1, $value2)
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
    public function neq($value1, $value2)
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
    public function gt($value1, $value2)
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
    public function gte($value1, $value2)
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
    public function lt($value1, $value2)
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
    public function lte($value1, $value2)
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
    public function in($column, $values)
    {
        if ( ! is_array($values)) {
            $values = array($values);
        }
        $values = $this->getIdentifiers($values);
        $column = $this->getIdentifier($column);

        if (count($values) == 0) {
            throw new Doctrine_Expression_Exception('Values array for IN operator should not be empty.');
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
    public function isNull($expression)
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
    public function isNotNull($expression)
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
    public function between($expression, $value1, $value2)
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
    public function guid()
    {
        throw new Doctrine_Expression_Exception('method not implemented');
    }
    /**
     * returns arcus cosine SQL string
     *
     * @return string
     */
    public function acos($value)
    {
        return 'ACOS(' . $value . ')';
    }
    /**
     * __call
     *
     * for all native RDBMS functions the function name itself is returned
     */
    public function __call($m, $a) 
    {
    	if ($this->conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_EXPR) {
            throw new Doctrine_Expression_Exception('Unknown expression ' . $m);
        }
        return $m . '(' . implode(', ', $a) . ')';
    }
}
