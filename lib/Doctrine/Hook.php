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
 * Doctrine_Hook
 *
 * @package     Doctrine
 * @subpackage  Hook
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Hook
{
    /**
     * @var Doctrine_Query $query           the base query
     */
    protected $query;

    /**
     * @var array $joins                    the optional joins of the base query
     */
    protected $joins;

    /**
     * @var array $hooks                    hooks array
     */
    protected $hooks        = array(
                             'where',
                             'orderby',
                             'limit',
                             'offset'
                              );

    /**
     * @var array $fieldParsers             custom field parsers array
     *                                      keys as field names in the format componentAlias.FieldName
     *                                      values as parser names / objects
     */
    protected $fieldParsers = array();

    /**
     * @var array $typeParsers              type parsers array
     *                                      keys as type names and values as parser names / objects
     */
    protected $typeParsers  = array(
                              'char'      => 'Doctrine_Hook_WordLike',
                              'string'    => 'Doctrine_Hook_WordLike',
                              'varchar'   => 'Doctrine_Hook_WordLike',
                              'integer'   => 'Doctrine_Hook_Integer',
                              'enum'      => 'Doctrine_Hook_Integer',
                              'time'      => 'Doctrine_Hook_Time',
                              'date'      => 'Doctrine_Hook_Date',
                              );

    /**
     * @param Doctrine_Query $query         the base query
     */
    public function __construct($query)
    {
        if (is_string($query)) {
            $this->query = new Doctrine_Query();
            $this->query->parseQuery($query);
        } elseif ($query instanceof Doctrine_Query) {
            $this->query = $query;
        } else {
            throw new Doctrine_Exception('Constructor argument should be either Doctrine_Query object or valid DQL query');          
        }
        
        $this->query->getQuery();
    }

    /**
     * getQuery
     *
     * @return Doctrine_Query       returns the query object associated with this hook
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * setTypeParser
     *
     * @param string $type              type name
     * @param string|object $parser     parser name or custom parser object
     */
    public function setTypeParser($type, $parser) 
    {
        $this->typeParsers[$type] = $parser;
    }

    /**
     * setFieldParser
     *
     * @param string $field             field name
     * @param string|object $parser     parser name or custom parser object
     */
    public function setFieldParser($field, $parser)
    {
        $this->fieldParsers[$field] = $parser;
    }

    /**
     * hookWhere
     * builds DQL query where part from given parameter array
     *
     * @param array $params         an associative array containing field
     *                              names and their values
     * @return boolean              whether or not the hooking was
     */
    public function hookWhere($params)
    {
        if ( ! is_array($params)) {
            return false;
        }
        foreach ($params as $name => $value) {
            if ($value === '' || $value === '-') {
                continue;
            }
            $e = explode('.', $name);

            if (count($e) == 2) {
                list($alias, $column) = $e;

                $map   = $this->query->getAliasDeclaration($alias);
                $table = $map['table'];

                if ( ! $table) {
                    throw new Doctrine_Exception('Unknown alias ' . $alias);
                }

                if ($def = $table->getDefinitionOf($column)) {

                $def[0] = gettype($value);
                    if (isset($this->typeParsers[$def[0]])) {
                        $name   = $this->typeParsers[$def[0]];
                        $parser = new $name;
                    }

                    $parser->parse($alias, $column, $value);

                    $this->query->addWhere($parser->getCondition(), $parser->getParams());
                }
            }
        }

        return true;
    }

    /**
     * hookOrderBy
     * builds DQL query orderby part from given parameter array
     *
     * @param array $params         an array containing all fields which the built query
     *                              should be ordered by
     * @return boolean              whether or not the hooking was successful
     */
    public function hookOrderby($params)
    {
        if ( ! is_array($params)) {
            return false;
        }
        foreach ($params as $name) {
            $e = explode(' ', $name);

            $order = 'ASC';

            if (count($e) > 1) {
                $order = ($e[1] == 'DESC') ? 'DESC' : 'ASC';
            }

            $e = explode('.', $e[0]);

            if (count($e) == 2) {
                list($alias, $column) = $e;

                $map   = $this->query->getAliasDeclaration($alias);
                $table = $map['table'];

                if ($def = $table->getDefinitionOf($column)) {   
                    $this->query->addOrderBy($alias . '.' . $column . ' ' . $order);
                }
            }
        }
        return true;
    }

    /**
     * set the hook limit 
     * 
     * @param integer $limit 
     * @return void
     */
    public function hookLimit($limit)
    {
        $this->query->limit((int) $limit);
    }

    /**
     * set the hook offset
     *
     * @param integer $offset
     */
    public function hookOffset($offset)
    {
        $this->query->offset((int) $offset);
    }
}
