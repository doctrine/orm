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
Doctrine::autoload('Doctrine_Hydrate');
/**
 * Doctrine_RawSql
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_RawSql extends Doctrine_Hydrate
{
    /**
     * @var array $fields
     */
    private $fields = array();
    /**
     * __call
     * method overloader
     *
     * @param string $name
     * @param array $args
     * @return Doctrine_RawSql
     */
    public function __call($name, $args)
    {
        if ( ! isset($this->parts[$name])) {
            throw new Doctrine_RawSql_Exception("Unknown overload method $name. Availible overload methods are ".implode(" ",array_keys($this->parts)));
        }
        if ($name == 'select') {
            preg_match_all('/{([^}{]*)}/U', $args[0], $m);

            $this->fields = $m[1];
            $this->parts["select"] = array();
        } else {
            $this->parts[$name][] = $args[0];
        }
        return $this;
    }
    /**
     * parseQuery
     * parses an sql query and adds the parts to internal array
     *
     * @param string $query         query to be parsed
     * @return Doctrine_RawSql      this object
     */
    public function parseQuery($query)
    {
        preg_match_all('/{([^}{]*)}/U', $query, $m);

        $this->fields = $m[1];
        $this->clear();

        $e = Doctrine_Tokenizer::sqlExplode($query,' ');

        foreach ($e as $k => $part) {
            $low = strtolower($part);
            switch (strtolower($part)) {
                case 'select':
                case 'from':
                case 'where':
                case 'limit':
                case 'offset':
                case 'having':
                    $p = $low;
                    if ( ! isset($parts[$low])) {
                        $parts[$low] = array();
                    }
                    break;
                case 'order':
                case 'group':
                    $i = ($k + 1);
                    if (isset($e[$i]) && strtolower($e[$i]) === 'by') {
                        $p = $low;
                        $p .= 'by';
                        $parts[$low . 'by'] = array();

                    } else {
                        $parts[$p][] = $part;
                    }
                    break;
                case 'by':
                    continue;
                default:
                    if ( ! isset($parts[$p][0])) {
                        $parts[$p][0] = $part;
                    } else {
                        $parts[$p][0] .= ' '.$part;
                    }
            };
        };

        $this->parts = $parts;
        $this->parts['select'] = array();

        return $this;
    }
    /**
     * getQuery
     * builds the sql query from the given query parts
     *
     * @return string       the built sql query
     */
    public function getQuery()
    {
        foreach ($this->fields as $field) {
            $e = explode('.', $field);
            if ( ! isset($e[1])) {
                throw new Doctrine_RawSql_Exception('All selected fields in Sql query must be in format tableAlias.fieldName');
            }
            // try to auto-add component
            if ( ! $this->aliasHandler->hasAlias($e[0])) {
                try {
                    $this->addComponent($e[0], ucwords($e[0]));
                } catch(Doctrine_Exception $exception) {
                    throw new Doctrine_RawSql_Exception('The associated component for table alias ' . $e[0] . ' couldn\'t be found.');
                }
            }

            if ($e[1] == '*') {
                $componentAlias = $this->aliasHandler->getComponentAlias($e[0]);

                foreach ($this->_aliasMap[$componentAlias]['table']->getColumnNames() as $name) {
                    $field = $e[0] . '.' . $name;
                    $this->parts['select'][$field] = $field . ' AS ' . $e[0] . '__' . $name;
                }
            } else {
                $field = $e[0] . '.' . $e[1];
                $this->parts['select'][$field] = $field . ' AS ' . $e[0] . '__' . $e[1];
            }
        }

        // force-add all primary key fields

        foreach ($this->aliasHandler->getAliases() as $tableAlias => $componentAlias) {
            $map = $this->_aliasMap[$componentAlias];

            foreach ($map['table']->getPrimaryKeys() as $key) {
                $field = $tableAlias . '.' . $key;

                if ( ! isset($this->parts['select'][$field])) {
                    $this->parts['select'][$field] = $field . ' AS ' . $tableAlias . '__' . $key;
                }
            }
        }

        $q = 'SELECT ' . implode(', ', $this->parts['select']);

        $string = $this->applyInheritance();
        if ( ! empty($string)) {
            $this->parts['where'][] = $string;
        }
        $copy = $this->parts;
        unset($copy['select']);

        $q .= ( ! empty($this->parts['from']))?    ' FROM '     . implode(' ', $this->parts['from']) : '';
        $q .= ( ! empty($this->parts['where']))?   ' WHERE '    . implode(' AND ', $this->parts['where']) : '';
        $q .= ( ! empty($this->parts['groupby']))? ' GROUP BY ' . implode(', ', $this->parts['groupby']) : '';
        $q .= ( ! empty($this->parts['having']))?  ' HAVING '   . implode(' ', $this->parts['having']) : '';
        $q .= ( ! empty($this->parts['orderby']))? ' ORDER BY ' . implode(' ', $this->parts['orderby']) : '';
        $q .= ( ! empty($this->parts['limit']))?   ' LIMIT ' . implode(' ', $this->parts['limit']) : '';
        $q .= ( ! empty($this->parts['offset']))?  ' OFFSET ' . implode(' ', $this->parts['offset']) : '';

        if ( ! empty($string)) {
            array_pop($this->parts['where']);
        }
        return $q;
    }
    /**
     * getFields
     * returns the fields associated with this parser
     *
     * @return array    all the fields associated with this parser
     */
    public function getFields()
    {
        return $this->fields;
    }
    /**
     * addComponent
     *
     * @param string $tableAlias
     * @param string $componentName
     * @return Doctrine_RawSql
     */
    public function addComponent($tableAlias, $path)
    {
        $tmp           = explode(' ', $path);
        $originalAlias = (count($tmp) > 1) ? end($tmp) : null;

        $e = explode('.', $tmp[0]);

        $fullPath = $tmp[0];
        $fullLength = strlen($fullPath);

        $table = null;

        $currPath = '';

        if (isset($this->_aliasMap[$e[0]])) {
            $table = $this->_aliasMap[$e[0]]['table'];

            $currPath = $parent = array_shift($e);
        }

        foreach ($e as $k => $component) {
            // get length of the previous path
            $length = strlen($currPath);

            // build the current component path
            $currPath = ($currPath) ? $currPath . '.' . $component : $component;

            $delimeter = substr($fullPath, $length, 1);

            // if an alias is not given use the current path as an alias identifier
            if (strlen($currPath) === $fullLength && isset($originalAlias)) {
                $componentAlias = $originalAlias;
            } else {
                $componentAlias = $currPath;
            }
            if ( ! isset($table)) {
                $conn = Doctrine_Manager::getInstance()
                        ->getConnectionForComponent($component);
                        
                $table = $conn->getTable($component);
                $this->_aliasMap[$componentAlias] = array('table' => $table);
            } else {
                $relation = $table->getRelation($component);

                $this->_aliasMap[$componentAlias] = array('table'    => $relation->getTable(),
                                                          'parent'   => $parent,
                                                          'relation' => $relation);
            }
            $this->aliasHandler->addAlias($tableAlias, $componentAlias);

            $parent = $currPath;
        }

        return $this;
    }

}
