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
 * Doctrine_Relation_Parser
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 1397 $
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Relation_Parser 
{
    /**
     * @var Doctrine_Table $_table          the table object this parser belongs to
     */
    protected $_table;
    /**
     * @var array $_relations               an array containing all the Doctrine_Relation objects for this table
     */
    protected $_relations = array();
    /**
     * @var array $_pending                 relations waiting for parsing
     */
    protected $_pending   = array();
    /**
     * @var array $_relationAliases         relation aliases
     */
    protected $_aliases   = array();
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
     * getPendingRelation
     *
     * @return array            an array defining a pending relation
     */
    public function getPendingRelation($name) 
    {
    	if ( ! isset($this->_pending[$name])) {
            throw new Doctrine_Relation_Exception('Unknown pending relation ' . $name);
    	}
    	
    	return $this->_pending[$name];
    }
    /**
     * binds a relation
     *
     * @param string $name
     * @param string $field
     * @return void
     */
    public function bind($name, $options = array())
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
            $this->_aliases[$name] = $alias;
        } else {
            $alias = $name;
        }

        if ( ! isset($options['definer'])) {
            throw new Doctrine_Relation_Exception('Relation definer not set.');
        }

        if ( ! isset($options['type'])) {
            throw new Doctrine_Relation_Exception('Relation type not set.');
        }

        $this->_pending[$alias] = array_merge($options, array('class' => $name, 'alias' => $alias));
    }
    
    public function getRelation($name, $recursive = true)
    {
        if (isset($this->_relations[$name])) {
            return $this->_relations[$name];
        }

        if (isset($this->_pending[$name])) {
            $def = $this->_pending[$name];


        }
    }
    public function completeDefinition($def)
    {
    	$conn = $this->_table->getConnection();
        $def['table']   = $conn->getTable($def['class']);
        $def['definer'] = $conn->getTable($def['definer']);

        if (isset($def['local'])) {
            if ( ! isset($def['foreign'])) {
                // local key is set, but foreign key is not
                // try to guess the foreign key

                if ($def['local'] === $def['definer']->getIdentifier()) {
                    $column = strtolower($def['definer']->getComponentName())
                            . '_' . $def['definer']->getIdentifier();

                    if ( ! $def['table']->hasColumn($column)) {
                        // auto-add column
                    }
                    
                    $def['foreign'] = $column;
                } else {
                    // the foreign field is likely to be the
                    // identifier of the foreign class
                    $def['foreign'] = $def['table']->getIdentifier();
                }
            }
        } else {
            if (isset($def['foreign'])) {
                // local key not set, but foreign key is set
                // try to guess the local key
                if ($def['foreign'] === $def['definer']->getIdentifier()) {
                    $column = strtolower($def['table']->getComponentName())
                            . '_' . $def['table']->getIdentifier();
                    
                    $def['local'] = $column;
                } else {
                    $def['local'] = $def['definer']->getIdentifier();
                }
            } else {
                // neither local or foreign key is being set
                // try to guess both keys
                
                $column = strtolower($def['definer']->getComponentName())
                        . '_' . $def['definer']->getIdentifier();

                if ($def['table']->hasColumn($column)) {
                    $def['foreign'] = $column;
                    $def['local']   = $def['definer']->getIdentifier();
                } else {

                    $column = strtolower($def['table']->getComponentName())
                            . '_' . $def['table']->getIdentifier();
                            
                    if ($def['definer']->hasColumn($column)) {
                        $def['foreign'] = $def['table']->getIdentifier();
                        $def['local']   = $column;
                    }
                }
            }
        }
        return $def;
    }
    /**
     * getRelation
     *
     * @param string $name              component name of which a foreign key object is bound
     * @return Doctrine_Relation
     */
    public function getRelation2($name, $recursive = true)
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
}
