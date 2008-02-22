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
Doctrine::autoload('Doctrine_Query_Part');
/**
 * Doctrine_Query_Condition
 *
 * @package     Doctrine
 * @subpackage  Query
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
abstract class Doctrine_Query_Condition extends Doctrine_Query_Part
{
    /**
     * DQL CONDITION PARSER
     * parses the join condition/where/having part of the query string
     *
     * @param string $str
     * @return string
     */
    public function parse($str)
    {
        $tmp = trim($str);

        $parts = $this->_tokenizer->bracketExplode($str, array(' \&\& ', ' AND '), '(', ')');

        if (count($parts) > 1) {
            $ret = array();
            foreach ($parts as $part) {
                $part = $this->_tokenizer->bracketTrim($part, '(', ')');
                $ret[] = $this->parse($part);
            }
            $r = implode(' AND ', $ret);
        } else {

            $parts = $this->_tokenizer->bracketExplode($str, array(' \|\| ', ' OR '), '(', ')');
            if (count($parts) > 1) {
                $ret = array();
                foreach ($parts as $part) {
                    $part = $this->_tokenizer->bracketTrim($part, '(', ')');
                    $ret[] = $this->parse($part);
                }
                $r = implode(' OR ', $ret);
            } else {
                // Fix for #710
                if (substr($parts[0],0,1) == '(' && substr($parts[0], -1) == ')') {
                    return $this->parse(substr($parts[0], 1, -1));
                } else {
                    // Processing NOT here
                    if (strtoupper(substr($parts[0], 0, 4)) === 'NOT ') {
                        $r = 'NOT ('.$this->parse(substr($parts[0], 4)).')';
                    } else {
                        return $this->load($parts[0]);
                    }
                }
            }
        }

        return '(' . $r . ')';
    }

    /**
     * parses a literal value and returns the parsed value
     *
     * boolean literals are parsed to integers
     * components are parsed to associated table aliases
     *
     * @param string $value         literal value to be parsed
     * @return string
     */
    public function parseLiteralValue($value)
    {
        // check that value isn't a string
        if (strpos($value, '\'') === false) {
            // parse booleans
            $value = $this->query->getConnection()
                     ->dataDict->parseBoolean($value);

            $a = explode('.', $value);

            if (count($a) > 1) {
            // either a float or a component..

                if ( ! is_numeric($a[0])) {
                    // a component found
                    $field     = array_pop($a);
                	  $reference = implode('.', $a);
                    $value = $this->query->getTableAlias($reference). '.' . $field;
                }
            }
        } else {
            // string literal found
        }

        return $value;
    }
}