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
        if ( ! isset($options['type'])) {
            throw new Doctrine_Relation_Exception('Relation type not set.');
        }

        $this->_pending[$alias] = array_merge($options, array('class' => $name, 'alias' => $alias));
    }
    /**
     * getRelation
     *
     * @param string $alias      relation alias
     */
    public function getRelation($alias, $recursive = true)
    {
        if (isset($this->_relations[$alias])) {
            return $this->_relations[$alias];
        }

        if (isset($this->_pending[$alias])) {
            $def = $this->_pending[$alias];
        
            if (isset($def['refClass'])) {
                $def = $this->completeAssocDefinition($def);
                $localClasses = array_merge($this->_table->getOption('parents'), array($this->_table->getComponentName()));

                if ( ! isset($this->_pending[$def['refClass']]) && 
                     ! isset($this->_relations[$def['refClass']])) {
                    
                    $def['refTable']->getRelationParser()->bind($this->_table->getComponentName(),
                                                                array('type' => Doctrine_Relation::ONE));

                    $this->bind($def['refClass'], array('type' => Doctrine_Relation::MANY));
                }
                if (in_array($def['class'], $localClasses)) {
                    return new Doctrine_Relation_Association_Self($def);
                } else {
                    return new Doctrine_Relation_Association($def);
                }
            } else {
                $def = $this->completeDefinition($def);
                
                return new Doctrine_Relation_ForeignKey($def);
            }
        }
    }
    /**
     * Completes the given association definition
     *
     * @param array $def    definition array to be completed
     * @return array        completed definition array
     */
    public function completeAssocDefinition($def) 
    {
    	$conn = $this->_table->getConnection();
        $def['table']    = $conn->getTable($def['class']);
        $def['refTable'] = $conn->getTable($def['refClass']);

        if ( ! isset($def['foreign'])) {
            // foreign key not set
            // try to guess the foreign key

            $columns = $this->getIdentifiers($def['table']);

            $def['foreign'] = $columns;
        }
        if ( ! isset($def['local'])) {
            // local key not set
            // try to guess the local key
            $columns = $this->getIdentifiers($this->_table);

            $def['local'] = $columns;
        } 

        return $def;
    }
    /** 
     * getIdentifiers
     * gives a list of identifiers from given table
     *
     * the identifiers are in format:
     * [componentName].[identifier]
     *
     * @param Doctrine_Table $table     table object to retrieve identifiers from
     */
    public function getIdentifiers(Doctrine_Table $table)
    {
    	if (is_array($table->getIdentifier())) {
            $columns = array();
            foreach((array) $table->getIdentifier() as $identifier) {
                $columns[] = strtolower($table->getComponentName())
                           . '_' . $table->getIdentifier();
            }
    	} else {
            $columns = strtolower($table->getComponentName())
                           . '_' . $table->getIdentifier();
    	}

        return $columns;
    }
    public function guessColumns($classes, Doctrine_Table $foreignTable)
    {
        $conn = $this->_table->getConnection();

        foreach ($classes as $class) {
            $table   = $conn->getTable($class);
            $columns = $this->getIdentifiers($table);
            $found   = true;

            foreach ((array) $columns as $column) {
                if ( ! $foreignTable->hasColumn($column)) {
                    $found = false;
                    break;
                }
            }
            if ($found) {
                break;
            }
        }
        
        if ( ! $found) {
            throw new Doctrine_Relation_Exception("Couldn't find columns.");
        }

        return $columns;
    }
    /**
     * Completes the given definition
     *
     * @param array $def    definition array to be completed
     * @return array        completed definition array
     */
    public function completeDefinition($def)
    {
    	$conn = $this->_table->getConnection();
        $def['table'] = $conn->getTable($def['class']);
        $foreignClasses = array_merge($def['table']->getOption('parents'), array($def['class']));
        $localClasses   = array_merge($this->_table->getOption('parents'), array($this->_table->getComponentName()));

        if (isset($def['local'])) {
            if ( ! isset($def['foreign'])) {
                // local key is set, but foreign key is not
                // try to guess the foreign key

                if ($def['local'] === $this->_table->getIdentifier()) {
                    $def['foreign'] = $this->guessColumns($localClasses, $def['table']);
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
                if ($def['foreign'] === $def['table']->getIdentifier()) {
                    $def['local'] = $this->guessColumns($foreignClasses, $this->_table);
                } else {
                    $def['local'] = $this->_table->getIdentifier();
                }
            } else {
                // neither local or foreign key is being set
                // try to guess both keys

                $conn = $this->_table->getConnection();

                // the following loops are needed for covering inheritance
                foreach ($localClasses as $class) {
                    $table  = $conn->getTable($class);
                    $column = strtolower($table->getComponentName())
                            . '_' . $table->getIdentifier();
                
                    foreach ($foreignClasses as $class2) {
                        $table2 = $conn->getTable($class2);
                        if ($table2->hasColumn($column)) {
                            $def['foreign'] = $column;
                            $def['local']   = $table->getIdentifier();
                            return $def;
                        }
                    }
                }

                foreach ($foreignClasses as $class) {
                    $table  = $conn->getTable($class);
                    $column = strtolower($table->getComponentName())
                            . '_' . $table->getIdentifier();
                
                    foreach ($localClasses as $class2) {
                        $table2 = $conn->getTable($class2);
                        if ($table2->hasColumn($column)) {
                            $def['foreign'] = $table->getIdentifier();
                            $def['local']   = $column;
                            return $def;
                        }
                    }
                }
            }
        }
        return $def;
    }
}
