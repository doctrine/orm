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
 * Doctrine_Query_JoinCondition
 *
 * @package     Doctrine
 * @subpackage  Query
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Query_JoinCondition extends Doctrine_Query_Condition 
{
    public function load($condition) 
    {
        $condition = trim($condition);

        $e = $this->_tokenizer->sqlExplode($condition);

        if (count($e) > 2) {
            $a         = explode('.', $e[0]);
            $field     = array_pop($a);
            $reference = implode('.', $a);
            $operator  = $e[1];
            $value     = $e[2];

            $alias     = $this->query->getTableAlias($reference);
            $map       = $this->query->getAliasDeclaration($reference);
            $table     = $map['table'];
            // check if value is enumerated value
            $enumIndex = $table->enumIndex($field, trim($value, "'"));


            if (substr($value, 0, 1) == '(') {
                // trim brackets
                $trimmed   = $this->_tokenizer->bracketTrim($value);

                if (substr($trimmed, 0, 4) == 'FROM' || substr($trimmed, 0, 6) == 'SELECT') {
                    // subquery found
                    $q = $this->query->createSubquery();
                    $value = '(' . $q->parseQuery($trimmed)->getQuery() . ')';
                } elseif (substr($trimmed, 0, 4) == 'SQL:') {
                    $value = '(' . substr($trimmed, 4) . ')';
                } else {
                    // simple in expression found
                    $e     = $this->_tokenizer->sqlExplode($trimmed, ',');

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
                    $condition  = $alias . '.' . $field . ' '
                                . $operator . ' ' . $value;
            }

        }
        
        return $condition;
    }
}