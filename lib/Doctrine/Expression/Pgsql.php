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
Doctrine::autoload('Doctrine_Expression_Driver');
/**
 * Doctrine_Expression_Pgsql
 *
 * @package     Doctrine
 * @subpackage  Expression
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Expression_Pgsql extends Doctrine_Expression_Driver
{
    /**
     * Returns the md5 sum of a field.
     *
     * Note: Not SQL92, but common functionality
     *
     * md5() works with the default PostgreSQL 8 versions.
     *
     * If you are using PostgreSQL 7.x or older you need
     * to make sure that the digest procedure is installed.
     * If you use RPMS (Redhat and Mandrake) install the postgresql-contrib
     * package. You must then install the procedure by running this shell command:
     * <code>
     * psql [dbname] < /usr/share/pgsql/contrib/pgcrypto.sql
     * </code>
     * You should make sure you run this as the postgres user.
     *
     * @return string
     */
    public function md5($column)
    {
        $column = $this->getIdentifier($column);

        if ($this->version > 7) {
            return 'MD5(' . $column . ')';
        } else {
            return 'encode(digest(' . $column .', md5), hex)';
        }
    }

    /**
     * Returns part of a string.
     *
     * Note: Not SQL92, but common functionality.
     *
     * @param string $value the target $value the string or the string column.
     * @param int $from extract from this characeter.
     * @param int $len extract this amount of characters.
     * @return string sql that extracts part of a string.
     */
    public function substring($value, $from, $len = null)
    {
        $value = $this->getIdentifier($value);

        if ($len === null) {
            $len = $this->getIdentifier($len);
            return 'SUBSTR(' . $value . ', ' . $from . ')';
        } else {
            return 'SUBSTR(' . $value . ', ' . $from . ', ' . $len . ')';
        }
    }

    /**
     * PostgreSQLs AGE(<timestamp1> [, <timestamp2>]) function.
     *
     * @param string $timestamp1 timestamp to subtract from NOW()
     * @param string $timestamp2 optional; if given: subtract arguments
     * @return string
     */
    public function age($timestamp1, $timestamp2 = null) {
        if ( $timestamp2 == null ) {
            return 'AGE(' . $timestamp1 . ')';
        }
        return 'AGE(' . $timestamp1 . ', ' . $timestamp2 . ')';
    }

    /**
     * PostgreSQLs DATE_PART( <text>, <time> ) function.
     *
     * @param string $text what to extract
     * @param string $time timestamp or interval to extract from
     * @return string
     */
    public function date_part($text, $time) {
        return 'DATE_PART(' . $text . ', ' . $time . ')';
    }


    /**
     * PostgreSQLs TO_CHAR( <time>, <text> ) function.
     *
     * @param string $time timestamp or interval
     * @param string $text how to the format the output
     * @return string
     */
    public function to_char($time, $text) {
        return 'TO_CHAR(' . $time . ', ' . $text . ')';
    }

    /**
     * Returns the SQL string to return the current system date and time.
     *
     * @return string
     */
    public function now()
    {
        return 'LOCALTIMESTAMP(0)';
    }

    /**
     * regexp
     *
     * @return string           the regular expression operator
     */
    public function regexp()
    {
        return 'SIMILAR TO';
    }

    /**
     * return string to call a function to get random value inside an SQL statement
     *
     * @return return string to generate float between 0 and 1
     * @access public
     */
    public function random()
    {
        return 'RANDOM()';
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
    public function matchPattern($pattern, $operator = null, $field = null)
    {
        $match = '';
        if ( ! is_null($operator)) {
            $field = is_null($field) ? '' : $field.' ';
            $operator = strtoupper($operator);
            switch ($operator) {
                // case insensitive
            case 'ILIKE':
                $match = $field.'ILIKE ';
                break;
                // case sensitive
            case 'LIKE':
                $match = $field.'LIKE ';
                break;
            default:
                throw new Doctrine_Expression_Pgsql_Exception('not a supported operator type:'. $operator);
            }
        }
        $match.= "'";
        foreach ($pattern as $key => $value) {
            if ($key % 2) {
                $match.= $value;
            } else {
                $match.= $this->conn->escapePattern($this->conn->escape($value));
            }
        }
        $match.= "'";
        $match.= $this->patternEscapeString();
        return $match;
    }
}
