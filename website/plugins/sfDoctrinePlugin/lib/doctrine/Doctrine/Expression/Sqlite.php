<?php
/*
 *  $Id: Sqlite.php 1917 2007-07-01 11:27:45Z zYne $
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
Doctrine::autoload('Doctrine_Expression_Driver');
/**
 * Doctrine_Expression_Sqlite
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1917 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Expression_Sqlite extends Doctrine_Expression_Driver
{
    /**
     * Returns the md5 sum of the data that SQLite's md5() function receives.
     *
     * @param mixed $data
     * @return string
     */
    public static function md5Impl($data)
    {
        return md5($data);
    }
    /**
     * Returns the modules of the data that SQLite's mod() function receives.
     *
     * @param integer $dividend
     * @param integer $divisor
     * @return string
     */
    public static function modImpl($dividend, $divisor)
    {
        return $dividend % $divisor;
    }

    /**
     * Returns a concatenation of the data that SQLite's concat() function receives.
     *
     * @return string
     */
    public static function concatImpl()
    {
        $args = func_get_args();
        return join('', $args);
    }
    /**
     * locate
     * returns the position of the first occurrence of substring $substr in string $str that
     * SQLite's locate() function receives
     *
     * @param string $substr    literal string to find
     * @param string $str       literal string
     * @return string
     */
    public static function locateImpl($substr, $str)
    {
        return strpos($str, $substr);
    }
    public static function sha1Impl($str)
    {
        return sha1($str);
    }
    public static function ltrimImpl($str)
    {
        return ltrim($str);
    }
    public static function rtrimImpl($str)
    {
        return rtrim($str);
    }
    public static function trimImpl($str)
    {
        return trim($str);
    }
    /**
     * returns the regular expression operator
     *
     * @return string
     */
    public function regexp()
    {
        return 'RLIKE';
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
        return 'SOUNDEX(' . $value . ')';
    }
    /**
     * Return string to call a variable with the current timestamp inside an SQL statement
     * There are three special variables for current date and time.
     *
     * @return string       sqlite function as string
     */
    public function now($type = 'timestamp')
    {
        switch ($type) {
            case 'time':
                return 'time(\'now\')';
            case 'date':
                return 'date(\'now\')';
            case 'timestamp':
            default:
                return 'datetime(\'now\')';
        }
    }
    /**
     * return string to call a function to get random value inside an SQL statement
     *
     * @return string to generate float between 0 and 1
     */
    public function random()
    {
        return '((RANDOM() + 2147483648) / 4294967296)';
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
    public function substring($value, $position, $length = null)
    {
        if ($length !== null) {
            return 'SUBSTR(' . $value . ', ' . $position . ', ' . $length . ')';
        }
        return 'SUBSTR(' . $value . ', ' . $position . ', LENGTH(' . $value . '))';
    }
}
