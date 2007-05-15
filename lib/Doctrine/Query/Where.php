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
    /**
     * load
     * returns the parsed query part
     *
     * @param string $where
     * @return string
     */
    public function load($where)
    {
        $where = trim($where);

        $e     = Doctrine_Query::sqlExplode($where);

        if (count($e) > 1) {
            $tmp   = $e[0] . ' ' . $e[1];

            if (substr($tmp, 0, 6) == 'EXISTS') {
                return $this->parseExists($where, true);
            } elseif (substr($where, 0, 10) == 'NOT EXISTS') {
                return $this->parseExists($where, false);
            }
        }

        if (count($e) < 3) {
            $e = Doctrine_Query::sqlExplode($where, array('=', '<', '>', '!='));
        }
        $r = array_shift($e);

        $a = explode('.', $r);

        if (count($a) > 1) {
            $field     = array_pop($a);
            $count     = count($e);
            $slice     = array_slice($e, -1, 1);
            $value     = implode('', $slice);
            $operator  = trim(substr($where, strlen($r), -strlen($value)));

            $reference = implode('.', $a);
            $count     = count($a);

            $pos       = strpos($field, '(');

            if ($pos !== false) {
                $func   = substr($field, 0, $pos);
                $value  = trim(substr($field, ($pos + 1), -1));

                $values = Doctrine_Query::sqlExplode($value, ',');

                $field      = array_pop($a);
                $reference  = implode('.', $a);
                $table      = $this->query->load($reference, false);
                
                $field      = $table->getColumnName($field);

                array_pop($a);
                
                $reference2 = implode('.', $a);
                
                $alias      = $this->query->getTableAlias($reference2);

                $stack      = $this->query->getRelationStack();
                $relation   = end($stack);

                $stack      = $this->query->getTableStack();

                switch ($func) {
                    case 'contains':
                    case 'regexp':
                    case 'like':
                        $operator = $this->getOperator($func);

                        if (empty($relation)) {
                            throw new Doctrine_Query_Exception('DQL functions contains/regexp/like can only be used for fields of related components');
                        }
                        $where = array();
                        foreach ($values as $value) {
                            $where[] = $alias . '.' . $relation->getLocal() 
                                     . ' IN (SELECT '.$relation->getForeign()
                                     . ' FROM ' . $relation->getTable()->getTableName()
                                     . ' WHERE ' . $field . $operator . $value . ')';
                        }
                        $where = implode(' AND ', $where);
                        break;
                    default:
                        throw new Doctrine_Query_Exception('Unknown DQL function: '.$func);
                }
            } else {
                $table     = $this->query->load($reference, false);
                $alias     = $this->query->getTableAlias($reference);
                $table     = $this->query->getTable($alias);
                
                $field     = $table->getColumnName($field);
                // check if value is enumerated value
                $enumIndex = $table->enumIndex($field, trim($value, "'"));

                if (substr($value, 0, 1) == '(') {
                    // trim brackets
                    $trimmed   = Doctrine_Query::bracketTrim($value);

                    if (substr($trimmed, 0, 4) == 'FROM' || substr($trimmed, 0, 6) == 'SELECT') {

                        // subquery found
                        $q     = new Doctrine_Query();
                        $value = '(' . $q->isSubquery(true)->parseQuery($trimmed)->getQuery() . ')';

                    } elseif (substr($trimmed, 0, 4) == 'SQL:') {
                        $value = '(' . substr($trimmed, 4) . ')';
                    } else {
                        // simple in expression found
                        $e     = Doctrine_Query::sqlExplode($trimmed, ',');

                        $value = array();
                        foreach ($e as $part) {
                            $index   = $table->enumIndex($field, trim($part, "'"));
                            if ($index !== false) {
                                $value[] = $index;
                            } else {
                                $value[] = $this->parseLiteralValue($part);
                            }
                        }
                        $value = '(' . implode(', ', $value) . ')';
                    }
                } else {
                    if ($enumIndex !== false) {
                        $value = $enumIndex;
                    } else {
                        $value = $this->parseLiteralValue($value);
                    }
                }

                switch ($operator) {
                    case '<':
                    case '>':
                    case '=':
                    case '!=':
                        if ($enumIndex !== false) {
                            $value  = $enumIndex;
                        }
                    default:

                        if ($this->query->getType() === Doctrine_Query::SELECT) {
                            $fieldname = $alias ? $alias . '.' . $field : $field;
                        } else {
                            $fieldname = $field;
                        }
                        
                        $where = $fieldname . ' '
                               . $operator . ' ' . $value;
                }
            }
        }
        return $where;
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

        if ($pos == false)
            throw new Doctrine_Query_Exception("Unknown expression, expected '('");

        $sub = Doctrine_Query::bracketTrim(substr($where, $pos));

        return $operator . ' (' . $this->query->createSubquery()->parseQuery($sub, false)->getQuery() . ')';
    }
    /**
     * getOperator
     *
     * @param string $func
     * @return string
     */
    public function getOperator($func)
    {
        switch ($func) {
            case 'contains':
                $operator = ' = ';
                break;
            case 'regexp':
                $operator = $this->query->getConnection()->getRegexpOperator();
                break;
            case 'like':
                $operator = ' LIKE ';
                break;
        }
        return $operator;
    }
    /**
     * __toString
     * return string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return ( ! empty($this->parts))?implode(' AND ', $this->parts):'';
    }
}
