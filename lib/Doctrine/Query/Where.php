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
Doctrine::autoload('Doctrine_Query_Condition');
/**
 * Doctrine_Query_Where
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Query_Where extends Doctrine_Query_Condition
{
    public function load($where) 
    {
        $where = Doctrine_Tokenizer::bracketTrim(trim($where));
        $conn  = $this->query->getConnection();
        $terms = Doctrine_Tokenizer::sqlExplode($where);  

        if (count($terms) > 1) {
            if (substr($where, 0, 6) == 'EXISTS') {
                return $this->parseExists($where, true);
            } elseif (substr($where, 0, 10) == 'NOT EXISTS') {
                return $this->parseExists($where, false);
            }
        }

        if (count($terms) < 3) {
            $terms = Doctrine_Tokenizer::sqlExplode($where, array('=', '<', '<>', '>', '!='));
        }

        if (count($terms) > 1) {
            $first = array_shift($terms);
            $value = array_pop($terms);
            $operator = trim(substr($where, strlen($first), -strlen($value)));
            $table = null;
            $field = null;

            if (strpos($first, "'") === false && strpos($first, '(') === false) {
                // normal field reference found
                $a = explode('.', $first);
        
                $field = array_pop($a);
                $reference = implode('.', $a);
                $map = $this->query->load($reference, false);
        
                $alias = $this->query->getTableAlias($reference);
                $table = $map['table'];
        
                $first = $conn->quoteIdentifier($alias) 
                       . '.' 
                       . $conn->quoteIdentifier($table->getColumnName($field));
            } else {
                $first = $this->query->parseClause($first);
            }
            $sql = $first . ' ' . $operator . ' ' . $this->parseValue($value, $table, $field);
        
            return $sql;  
        } else {

        }
    }

    public function parseValue($value, Doctrine_Table $table = null, $field = null)
    {
        if (substr($value, 0, 1) == '(') {
            // trim brackets
            $trimmed   = Doctrine_Tokenizer::bracketTrim($value);

            if (substr($trimmed, 0, 4) == 'FROM' ||
                substr($trimmed, 0, 6) == 'SELECT') {

                // subquery found
                $q     = new Doctrine_Query();
                $value = '(' . $q->isSubquery(true)->parseQuery($trimmed)->getQuery() . ')';

            } elseif (substr($trimmed, 0, 4) == 'SQL:') {
                $value = '(' . substr($trimmed, 4) . ')';
            } else {
                // simple in expression found
                $e = Doctrine_Tokenizer::sqlExplode($trimmed, ',');

                $value = array();

                $index = false;

                foreach ($e as $part) {
                    if (isset($table) && isset($field)) {
                        $index = $table->enumIndex($field, trim($part, "'"));
                    }

                    if ($index !== false) {
                        $value[] = $index;
                    } else {
                        $value[] = $this->parseLiteralValue($part);
                    }
                }
                $value = '(' . implode(', ', $value) . ')';
            }
        } elseif(substr($value, 0, 1) == ':' || $value === '?') {
            // placeholder found
            if (isset($table) && isset($field) && $table->getTypeOf($field) == 'enum') {
                $this->query->addEnumParam($value, $table, $field);
            } else {
                $this->query->addEnumParam($value, null, null);
            }
        } else {
            // check if value is enumerated value
            $enumIndex = $table->enumIndex($field, trim($value, "'"));

            if ($enumIndex !== false) {
                $value = $enumIndex;
            } else {
                $value = $this->parseLiteralValue($value);
            }
        }
        return $value;
    }
    /**
     * parses an EXISTS expression
     *
     * @param string $where         query where part to be parsed
     * @param boolean $negation     whether or not to use the NOT keyword
     * @return string
     */
    public function parseExists($where, $negation)
    {
        $operator = ($negation) ? 'EXISTS' : 'NOT EXISTS';

        $pos = strpos($where, '(');

        if ($pos == false) {
            throw new Doctrine_Query_Exception('Unknown expression, expected a subquery with () -marks');
        }

        $sub = Doctrine_Tokenizer::bracketTrim(substr($where, $pos));

        return $operator . ' (' . $this->query->createSubquery()->parseQuery($sub, false)->getQuery() . ')';
    }
}
