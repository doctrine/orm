<?php
/*
 *  $Id: From.php 1080 2007-02-10 18:17:08Z romanb $
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

/**
 * Doctrine_Query_Check
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Query_Check
{
    /**
     * @var Doctrine_Table $table           Doctrine_Table object
     */
    protected $table;
    /**
     * @var string $sql                     database specific sql CHECK constraint definition 
     *                                      parsed from the given dql CHECK definition
     */
    protected $sql;
    /**
     * @param Doctrine_Table|string $table  Doctrine_Table object
     */
    public function __construct($table)
    {
        if ( ! ($table instanceof Doctrine_Table)) {
            $table = Doctrine_Manager::getInstance()
                        ->getCurrentConnection()
                        ->getTable($table);
        }
        $this->table = $table;
    }
    /**
     * getTable
     * returns the table object associated with this object
     *
     * @return Doctrine_Connection
     */
    public function getTable()
    {
        return $this->table;
    }
    /**
     * parse
     *
     * @param string $dql       DQL CHECK constraint definition
     * @return string
     */
    public function parse($dql)
    {
        $this->sql = $this->parseClause($dql);
    }
    /**
     * parseClause
     *
     * @param string $alias     component alias
     * @param string $field     the field name
     * @param mixed $value      the value of the field
     * @return void
     */
    public function parseClause($dql)
    {
        $parts = Doctrine_Tokenizer::sqlExplode($dql, ' AND ');

        if (count($parts) > 1) {
            $ret = array();
            foreach ($parts as $part) {
                $ret[] = $this->parseSingle($part);
            }

            $r = implode(' AND ', $ret);
        } else {
            $parts = Doctrine_Tokenizer::quoteExplode($dql, ' OR ');
            if (count($parts) > 1) {
                $ret = array();
                foreach ($parts as $part) {
                    $ret[] = $this->parseClause($part);
                }

                $r = implode(' OR ', $ret);
            } else {
                $ret = $this->parseSingle($dql);
                return $ret;
            }
        }
        return '(' . $r . ')';
    }
    public function parseSingle($part)
    {
        $e = explode(' ', $part);
        
        $e[0] = $this->parseFunction($e[0]);

        switch ($e[1]) {
            case '>':
            case '<':
            case '=':
            case '!=':
            case '<>':

            break;
            default:
                throw new Doctrine_Query_Exception('Unknown operator ' . $e[1]);
        }

        return implode(' ', $e);
    }
    public function parseFunction($dql) 
    {
        if (($pos = strpos($dql, '(')) !== false) {
            $func  = substr($dql, 0, $pos);
            $value = substr($dql, ($pos + 1), -1);
            
            $expr  = $this->table->getConnection()->expression;

            if ( ! method_exists($expr, $func)) {
                throw new Doctrine_Query_Exception('Unknown function ' . $func);
            }
            
            $func  = $expr->$func($value);
        }
        return $func;
    }
    /**
     * getSql
     *
     * returns database specific sql CHECK constraint definition
     * parsed from the given dql CHECK definition
     *
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }
}
