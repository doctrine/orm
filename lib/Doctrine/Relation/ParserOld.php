<?php
/*
 *  $Id: Table.php 1397 2007-05-19 19:54:15Z zYne $
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
 * Doctrine_Relation_ParserOld
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 1397 $
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Relation_ParserOld 
{
    /**
     * @var Doctrine_Table $_table          the table object this parser belongs to
     */
    protected $_table;
    /**
     * @var array $relations        an array containing all the Doctrine_Relation objects for this table
     */
    private $relations          = array();
    /**
     * @var array $bound            bound relations
     */
    private $bound              = array();
    /**
     * @var array $boundAliases     bound relation aliases
     */
    private $boundAliases       = array();
    /**
     * constructor
     *
     * @param Doctrine_Table $table         the table object this parser belongs to
     */
    public function __construct(Doctrine_Table $table) 
    {
        $this->_table = $table;
    }
    /**
     * getTable
     *
     * @return Doctrine_Table   the table object this parser belongs to
     */
    public function getTable()
    {
        return $this->_table;
    }
    /**
     * returns all bound relations
     *
     * @return array
     */
    public function getBounds()
    {
        return $this->bound;
    }
    /**
     * returns a bound relation array
     *
     * @param string $name
     * @return array
     */
    public function getBound($name)
    {
        if ( ! isset($this->bound[$name])) {
            throw new Doctrine_Relation_Exception('Unknown bound ' . $name);
        }
        return $this->bound[$name];
    }
    /**
     * returns a bound relation array
     *
     * @param string $name
     * @return array
     */
    public function getBoundForName($name, $component)
    {
        foreach ($this->bound as $k => $bound) {
            $e = explode('.', $bound['field']);

            if ($bound['class'] == $name && $e[0] == $component) {
                return $this->bound[$k];
            }
        }
        throw new Doctrine_Relation_Exception('Unknown bound ' . $name);
    }
    /**
     * returns the alias for given component name
     *
     * @param string $name
     * @return string
     */
    public function getAlias($name)
    {
        if (isset($this->boundAliases[$name])) {
            return $this->boundAliases[$name];
        }
        return $name;
    }
    /**
     * returns component name for given alias
     *
     * @param string $alias
     * @return string
     */
    public function getAliasName($alias)
    {
        if ($name = array_search($alias, $this->boundAliases)) {
            return $name;
        }
        return $alias;
    }
    /**
     * unbinds all relations
     *
     * @return void
     */
    public function unbindAll()
    {
        $this->bound        = array();
        $this->relations    = array();
        $this->boundAliases = array();
    }
    /**
     * unbinds a relation
     * returns true on success, false on failure
     *
     * @param $name
     * @return boolean
     */
    public function unbind($name)
    {
        if ( ! isset($this->bound[$name])) {
            return false;
        }
        unset($this->bound[$name]);

        if (isset($this->relations[$name])) {
            unset($this->relations[$name]);
        }
        if (isset($this->boundAliases[$name])) {
            unset($this->boundAliases[$name]);
        }
        return true;
    }
    /**
     * binds a relation
     *
     * @param string $name
     * @param string $field
     * @return void
     */
    public function bind($name, $field, $type, $options = null)
    {
        if (isset($this->relations[$name])) {
            unset($this->relations[$name]);
        }

        $lower = strtolower($name);

        if ($this->_table->hasColumn($lower)) {
            throw new Doctrine_Relation_Exception("Couldn't bind relation. Column with name " . $lower . ' already exists!');
        }

        $e    = explode(' as ', $name);
        $name = $e[0];

        if (isset($e[1])) {
            $alias = $e[1];
            $this->boundAliases[$name] = $alias;
        } else {
            $alias = $name;
        }

        $this->bound[$alias] = array('field'    => $field,
                                     'type'     => $type,
                                     'class'    => $name,
                                     'alias'    => $alias);
        if ($options !== null) {
            $opt = array();
            if (is_string($options)) {
                $opt['local'] = $options;
            } else {
                $opt = (array) $options;
            }

            $this->bound[$alias] = array_merge($this->bound[$alias], $opt);
        }
    }
    /**
     * hasRelatedComponent
     * @return boolean
     */
    public function hasRelatedComponent($name, $component)
    {
        return (strpos($this->bound[$name]['field'], $component . '.') !== false);
    }
    /**
     * @param string $name              component name of which a foreign key object is bound
     * @return boolean
     */
    final public function hasRelation($name)
    {
        if (isset($this->bound[$name])) {
            return true;
        }
        foreach ($this->bound as $k=>$v) {
            if ($this->hasRelatedComponent($k, $name)) {
                return true;
            }
        }
        return false;
    }
    /**
     * getRelation
     *
     * @param string $name              component name of which a foreign key object is bound
     * @return Doctrine_Relation
     */
    public function getRelation($name, $recursive = true)
    {
        if (isset($this->relations[$name])) {
            return $this->relations[$name];
        }

        if ( ! $this->conn->hasTable($this->options['name'])) {
            $allowExport = true;
        } else {
            $allowExport = false;
        }

        if (isset($this->bound[$name])) {

            $definition = $this->bound[$name];

            list($component, $tmp) = explode('.', $definition['field']);
            
            if ( ! isset($definition['foreign'])) {
                $definition['foreign'] = $tmp;
            }

            unset($definition['field']);

            $definition['table'] = $this->conn->getTable($definition['class'], $allowExport);
            $definition['constraint'] = false;

            if ($component == $this->options['name'] || in_array($component, $this->options['parents'])) {

                // ONE-TO-ONE
                if ($definition['type'] == Doctrine_Relation::ONE_COMPOSITE ||
                    $definition['type'] == Doctrine_Relation::ONE_AGGREGATE) {
                        // tree structure parent relation found

                        if ( ! isset($definition['local'])) {
                            $definition['local']   = $definition['foreign'];
                            $definition['foreign'] = $definition['table']->getIdentifier();
                        }

                        $relation = new Doctrine_Relation_LocalKey($definition);

                    } else {
                        // tree structure children relation found

                        if ( ! isset($definition['local'])) {
                            $tmp = $definition['table']->getIdentifier();

                            $definition['local'] = $tmp;
                        }

                        //$definition['foreign'] = $tmp;  
                        $definition['constraint'] = true;

                        $relation = new Doctrine_Relation_ForeignKey($definition);
                    }

            } elseif ($component == $definition['class'] ||
                ($component == $definition['alias'])) {     //  && ($name == $this->options['name'] || in_array($name,$this->parents))

                    if ( ! isset($defintion['local'])) {
                        $definition['local'] = $this->identifier;
                    }

                    $definition['constraint'] = true;

                    // ONE-TO-MANY or ONE-TO-ONE
                    $relation = new Doctrine_Relation_ForeignKey($definition);

                } else {
                    // MANY-TO-MANY
                    // only aggregate relations allowed

                    if ($definition['type'] != Doctrine_Relation::MANY_AGGREGATE) {
                        throw new Doctrine_Table_Exception("Only aggregate relations are allowed for many-to-many relations");
                    }

                    $classes = array_merge($this->options['parents'], array($this->options['name']));

                    foreach (array_reverse($classes) as $class) {
                        try {
                            $bound = $definition['table']->getBoundForName($class, $component);
                            break;
                        } catch(Doctrine_Table_Exception $exc) { }
                    }
                    if ( ! isset($bound)) {
                        throw new Doctrine_Table_Exception("Couldn't map many-to-many relation for "
                            . $this->options['name'] . " and $name. Components use different join tables.");
                    }
                    if ( ! isset($definition['local'])) {
                        $definition['local'] = $this->identifier;
                    }
                    $e2     = explode('.', $bound['field']);
                    $fields = explode('-', $e2[1]);

                    if ($e2[0] != $component) {
                        throw new Doctrine_Table_Exception($e2[0] . ' doesn\'t match ' . $component);
                    }
                    $associationTable = $this->conn->getTable($e2[0], $allowExport);

                    if (count($fields) > 1) {
                        // SELF-REFERENCING THROUGH JOIN TABLE

                        $def['table']   = $associationTable;
                        $def['local']   = $this->identifier;
                        $def['foreign'] = $fields[0];
                        $def['alias']   = $e2[0];
                        $def['class']   = $e2[0];
                        $def['type']    = Doctrine_Relation::MANY_COMPOSITE;

                        $this->relations[$e2[0]] = new Doctrine_Relation_ForeignKey($def);

                        $definition['assocTable'] = $associationTable;
                        $definition['local']      = $fields[0];
                        $definition['foreign']    = $fields[1];
                        $relation = new Doctrine_Relation_Association_Self($definition);
                    } else {
                        if($definition['table'] === $this) {

                        } else {
                            // auto initialize a new one-to-one relationships for association table
                            $associationTable->bind($this->getComponentName(),  
                                                    $associationTable->getComponentName(). '.' . $e2[1],
                                                    Doctrine_Relation::ONE_AGGREGATE
                                                    );

                            $associationTable->bind($definition['table']->getComponentName(),
                                $associationTable->getComponentName(). '.' . $definition['foreign'],
                                Doctrine_Relation::ONE_AGGREGATE
                            );

                            // NORMAL MANY-TO-MANY RELATIONSHIP

                            $def['table']   = $associationTable;
                            $def['foreign'] = $e2[1];
                            $def['local']   = $definition['local'];
                            $def['alias']   = $e2[0];
                            $def['class']   = $e2[0];
                            $def['type']    = Doctrine_Relation::MANY_COMPOSITE;
                            $this->relations[$e2[0]] = new Doctrine_Relation_ForeignKey($def);

                            $definition['local']      = $e2[1];
                            $definition['assocTable'] = $associationTable;
                            $relation = new Doctrine_Relation_Association($definition);
                        }
                    }
                }

            $this->relations[$name] = $relation;

            return $this->relations[$name];
        }


        // load all relations
        $this->getRelations();

        if ($recursive) {
            return $this->getRelation($name, false);
        } else {
            throw new Doctrine_Table_Exception($this->options['name'] . " doesn't have a relation to " . $name);
        }

    }
    /**
     * returns an array containing all foreign key objects
     *
     * @return array
     */
    final public function getRelations()
    {
        foreach ($this->bound as $k => $v) {
            $this->getRelation($k);
        }

        return $this->relations;
    }
}
