<?php

#namespace Doctrine::DBAL::Platforms;

/**
 * Base class for all DatabasePlatforms. The DatabasePlatforms are the central
 * point of abstraction of platform-specific behaviors, features and SQL dialects.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
abstract class Doctrine_DatabasePlatform
{
    /**
     * An array containing all features this platform supports, keys representing feature
     * names and values as one of the following (true, false, 'emulated').
     *
     * @var array
     */
    protected $_supported = array();
    
    /**
     * Platform specific properties. Subclasses can override these in the
     * constructor.
     * 
     * @var array $properties               
     */
    protected $_properties = array(
            'sql_comments' => array(
                array(
                    'start' => '--',
                    'end' => "\n",
                    'escape' => false
                ),
                array(
                    'start' => '/*',
                    'end' => '*/',
                    'escape' => false
                )
            ),
            'identifier_quoting' => array(
                'start' => '"',
                'end' => '"',
                'escape' => '"'
            ),
            'string_quoting' => array(
                'start' => "'",
                'end' => "'",
                'escape' => false,
                'escape_pattern' => false
            ),
            'wildcards' => array('%', '_'),
            'varchar_max_length' => 255,
            );
    
    public function __construct() {}
    
    /**
     * Checks whether a certain feature is supported.
     *
     * @param string $feature   the name of the feature
     * @return boolean          whether or not this drivers supports given feature
     */
    public function supports($feature)
    {
        return (isset($this->_supported[$feature]) &&
                ($this->_supported[$feature] === 'emulated' || $this->_supported[$feature]));
    }
    
    /**
     * regexp
     * returns the regular expression operator
     *
     * @return string
     */
    public function getRegexpExpression()
    {
        throw new Doctrine_Expression_Exception('Regular expression operator is not supported by this database driver.');
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
        throw new Doctrine_Expression_Exception('method not implemented');
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
        throw new Doctrine_Expression_Exception("Method not implemented.");
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
    abstract public function getNativeDeclaration($field);
    
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
     */
    public function writeLimitClauseInSubquery(Doctrine_ClassMetadata $rootClass, $query,
            $limit = false, $offset = false)
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
            throw Doctrine_Connection_Exception::unknownProperty($name);
        }
        return $this->_properties[$name];
    }
}


?>