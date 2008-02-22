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

/**
 * Doctrine_Query_Tokenizer
 *
 * @package     Doctrine
 * @subpackage  Query
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @todo Give the tokenizer state, make it better work together with Doctrine_Query and maybe
 *       take out commonly used string manipulation methods
 *       into a stateless StringUtil? class. This tokenizer should be concerned with tokenizing
 *       DQL strings.
 */
class Doctrine_Query_Tokenizer
{

    /**
     * tokenizeQuery
     * splits the given dql query into an array where keys
     * represent different query part names and values are
     * arrays splitted using sqlExplode method
     *
     * example:
     *
     * parameter:
     *      $query = "SELECT u.* FROM User u WHERE u.name LIKE ?"
     * returns:
     *      array('select' => array('u.*'),
     *            'from'   => array('User', 'u'),
     *            'where'  => array('u.name', 'LIKE', '?'))
     *
     * @param string $query                 DQL query
     * @throws Doctrine_Query_Exception     if some generic parsing error occurs
     * @return array                        an array containing the query string parts
     */
    public function tokenizeQuery($query)
    {
        $parts = array();
        $tokens = $this->sqlExplode($query, ' ');

        foreach ($tokens as $index => $token) {
            $token = trim($token);
            switch (strtolower($token)) {
                case 'delete':
                case 'update':
                case 'select':
                case 'set':
                case 'from':
                case 'where':
                case 'limit':
                case 'offset':
                case 'having':
                    $p = $token;
                    //$parts[$token] = array();
                    $parts[$token] = '';
                break;
                case 'order':
                case 'group':
                    $i = ($index + 1);
                    if (isset($tokens[$i]) && strtolower($tokens[$i]) === 'by') {
                        $p = $token;
                        $parts[$token] = '';
                        //$parts[$token] = array();
                    } else {
                        $parts[$p] .= "$token ";
                        //$parts[$p][] = $token;
                    }
                break;
                case 'by':
                    continue;
                default:
                    if ( ! isset($p)) {
                        throw new Doctrine_Query_Tokenizer_Exception(
                                "Couldn't tokenize query. Encountered invalid token: '$token'.");
                    }

                    $parts[$p] .= "$token ";
                    //$parts[$p][] = $token;
            }
        }
        return $parts;
    }

    /**
     * trims brackets
     *
     * @param string $str
     * @param string $e1        the first bracket, usually '('
     * @param string $e2        the second bracket, usually ')'
     */
    public function bracketTrim($str, $e1 = '(', $e2 = ')')
    {
        if (substr($str, 0, 1) === $e1 && substr($str, -1) === $e2) {
            return substr($str, 1, -1);
        } else {
            return $str;
        }
    }

    /**
     * bracketExplode
     *
     * example:
     *
     * parameters:
     *      $str = (age < 20 AND age > 18) AND email LIKE 'John@example.com'
     *      $d = ' AND '
     *      $e1 = '('
     *      $e2 = ')'
     *
     * would return an array:
     *      array("(age < 20 AND age > 18)",
     *            "email LIKE 'John@example.com'")
     *
     * @param string $str
     * @param string $d         the delimeter which explodes the string
     * @param string $e1        the first bracket, usually '('
     * @param string $e2        the second bracket, usually ')'
     *
     */
    public function bracketExplode($str, $d = ' ', $e1 = '(', $e2 = ')')
    {
        if (is_array($d)) {
            $a = preg_split('#('.implode('|', $d).')#i', $str);
            $d = stripslashes($d[0]);
        } else {
            $a = explode($d, $str);
        }

        $i = 0;
        $term = array();
        foreach($a as $key=>$val) {
            if (empty($term[$i])) {
                $term[$i] = trim($val);
                $s1 = substr_count($term[$i], $e1);
                $s2 = substr_count($term[$i], $e2);

                if ($s1 == $s2) {
                    $i++;
                }
            } else {
                $term[$i] .= $d . trim($val);
                $c1 = substr_count($term[$i], $e1);
                $c2 = substr_count($term[$i], $e2);

                if ($c1 == $c2) {
                    $i++;
                }
            }
        }
        return $term;
    }

    /**
     * quoteExplode
     *
     * example:
     *
     * parameters:
     *      $str = email LIKE 'John@example.com'
     *      $d = ' LIKE '
     *
     * would return an array:
     *      array("email", "LIKE", "'John@example.com'")
     *
     * @param string $str
     * @param string $d         the delimeter which explodes the string
     */
    public function quoteExplode($str, $d = ' ')
    {
        if (is_array($d)) {
            $a = preg_split('/('.implode('|', $d).')/', $str);
            $d = stripslashes($d[0]);
        } else {
            $a = explode($d, $str);
        }

        $i = 0;
        $term = array();
        foreach ($a as $key => $val) {
            if (empty($term[$i])) {
                $term[$i] = trim($val);

                if ( ! (substr_count($term[$i], "'") & 1)) {
                    $i++;
                }
            } else {
                $term[$i] .= $d . trim($val);

                if ( ! (substr_count($term[$i], "'") & 1)) {
                    $i++;
                }
            }
        }
        return $term;
    }

    /**
     * sqlExplode
     *
     * explodes a string into array using custom brackets and
     * quote delimeters
     *
     *
     * example:
     *
     * parameters:
     *      $str = "(age < 20 AND age > 18) AND name LIKE 'John Doe'"
     *      $d   = ' '
     *      $e1  = '('
     *      $e2  = ')'
     *
     * would return an array:
     *      array('(age < 20 AND age > 18)',
     *            'name',
     *            'LIKE',
     *            'John Doe')
     *
     * @param string $str
     * @param string $d         the delimeter which explodes the string
     * @param string $e1        the first bracket, usually '('
     * @param string $e2        the second bracket, usually ')'
     *
     * @return array
     */
    public function sqlExplode($str, $d = ' ', $e1 = '(', $e2 = ')')
    {
        if ($d == ' ') {
            $d = array(' ', '\s');
        }
        if (is_array($d)) {
            $d = array_map('preg_quote', $d);

            if (in_array(' ', $d)) {
                $d[] = '\s';
            }

            $split = '#(' . implode('|', $d) . ')#';

            $str = preg_split($split, $str);
            $d = stripslashes($d[0]);
        } else {
            $str = explode($d, $str);
        }

        $i = 0;
        $term = array();

        foreach ($str as $key => $val) {
            if (empty($term[$i])) {
                $term[$i] = trim($val);

                $s1 = substr_count($term[$i], $e1);
                $s2 = substr_count($term[$i], $e2);

                if (strpos($term[$i], '(') !== false) {
                    if ($s1 == $s2) {
                        $i++;
                    }
                } else {
                    if ( ! (substr_count($term[$i], "'") & 1) &&
                         ! (substr_count($term[$i], "\"") & 1)) {
                        $i++;
                    }
                }
            } else {
                $term[$i] .= $d . trim($val);
                $c1 = substr_count($term[$i], $e1);
                $c2 = substr_count($term[$i], $e2);

                if (strpos($term[$i], '(') !== false) {
                    if ($c1 == $c2) {
                        $i++;
                    }
                } else {
                    if ( ! (substr_count($term[$i], "'") & 1) &&
                         ! (substr_count($term[$i], "\"") & 1)) {
                        $i++;
                    }
                }
            }
        }
        return $term;
    }

    /**
     * clauseExplode
     *
     * explodes a string into array using custom brackets and
     * quote delimeters
     *
     *
     * example:
     *
     * parameters:
     *      $str = "(age < 20 AND age > 18) AND name LIKE 'John Doe'"
     *      $d   = ' '
     *      $e1  = '('
     *      $e2  = ')'
     *
     * would return an array:
     *      array('(age < 20 AND age > 18)',
     *            'name',
     *            'LIKE',
     *            'John Doe')
     *
     * @param string $str
     * @param string $d         the delimeter which explodes the string
     * @param string $e1        the first bracket, usually '('
     * @param string $e2        the second bracket, usually ')'
     *
     * @return array
     */
    public function clauseExplode($str, array $d, $e1 = '(', $e2 = ')')
    {
        if (is_array($d)) {
            $d = array_map('preg_quote', $d);

            if (in_array(' ', $d)) {
                $d[] = '\s';
            }

            $split = '#(' . implode('|', $d) . ')#';

            $str = preg_split($split, $str, -1, PREG_SPLIT_DELIM_CAPTURE);
        }

        $i = 0;
        $term = array();

        foreach ($str as $key => $val) {
            if ($key & 1) {
                if (isset($term[($i - 1)]) && ! is_array($term[($i - 1)])) {
                    $term[($i - 1)] = array($term[($i - 1)], $val);
                }
                continue;
            }
            if (empty($term[$i])) {
                $term[$i] = $val;
            } else {
                $term[$i] .= $str[($key - 1)] . $val;
            }

            $c1 = substr_count($term[$i], $e1);
            $c2 = substr_count($term[$i], $e2);

            if (strpos($term[$i], '(') !== false) {
                if ($c1 == $c2) {
                    $i++;
                }
            } else {
                if ( ! (substr_count($term[$i], "'") & 1) &&
                     ! (substr_count($term[$i], "\"") & 1)) {
                    $i++;
                }
            }
        }

        if (isset($term[$i - 1])) {
            $term[$i - 1] = array($term[$i - 1], '');
        }

        return $term;
    }
}
