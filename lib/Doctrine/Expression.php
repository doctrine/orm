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
 * Doctrine_Expression
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Expression extends Doctrine_Connection_Module {
    /**
     * regexp
     * returns the regular expression operator 
     *
     * @return string
     */
    public function regexp() {
        throw new Doctrine_Connection_Exception('Regular expression operator is not supported by this database driver.');                                    	
    }
    /**
     * Returns the average value of a column
     *
     * @param string $column    the column to use
     * @return string           generated sql including an AVG aggregate function
     */
    public function avg($column) {
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
    public function count($column) {
        $column = $this->getIdentifier($column);
        return 'COUNT(' . $column . ')';
    }

    /**
     * Returns the highest value of a column
     *
     * @param string $column    the column to use
     * @return string           generated sql including a MAX aggregate function
     */
    public function max($column) {
        $column = $this->getIdentifier($column);
        return 'MAX(' . $column . ')';
    }

    /**
     * Returns the lowest value of a column
     *
     * @param string $column the column to use
     * @return string
     */
    public function min($column) {
        $column = $this->getIdentifier($column);
        return 'MIN(' . $column . ')';
    }

    /**
     * Returns the total sum of a column
     *
     * @param string $column the column to use
     * @return string
     */
    public function sum($column) {
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
    public function md5($column) {
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
    public function length($column) {
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
    public function round($column, $decimals) {
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
    public function mod($expression1, $expression2) {
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
    public function ltrim($str) {
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
    public function upper($str) {
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
    public function lower($str) {
        return 'LOWER(' . $str . ')';
    }
    /**
     * locate
     * returns the position of the first occurrence of substring $substr in string $str
     *
     * @param string $substr    literal string
     * @param string $str       literal string
     * @return string
     */
    public function locate($substr, $str) {
        return 'LOCATE(' . $str . ', ' . $substr . ')';
    }
    /**
     * Returns the current system date.
     *
     * @return string
     */
    public function now() {
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
    public function substring($value, $from, $len = null) {
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
    public function concat($arg1, $arg2) {
        $args = func_get_args();
        $cols = $this->getIdentifiers($cols);
        return 'CONCAT(' . join(', ', $cols) . ')';
    }
}
