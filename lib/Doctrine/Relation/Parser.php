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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Relation_Parser
 *
 * @package     Doctrine
 * @subpackage  Relation
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 1397 $
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @todo Composite key support?
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
     * constructor
     *
     * @param Doctrine_Table $table         the table object this parser belongs to
     */
    public function __construct(/*Doctrine_Table*/ $table) 
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
    
    public function hasRelation($name)
    {
        if ( ! isset($this->_pending[$name]) && ! isset($this->_relations[$name])) {
            return false;
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
    public function bind($name, $options = array())
    {
        if (isset($this->_relations[$name])) {
            unset($this->_relations[$name]);
        }

        $e = explode(' as ', $name);
        $name = $e[0];
        $alias = isset($e[1]) ? $e[1] : $name;

        if ( ! isset($options['type'])) {
            throw new Doctrine_Relation_Exception('Relation type not set.');
        }
        
        $this->_pending[$alias] = array_merge($options, array('class' => $name, 'alias' => $alias));

        return $this->_pending[$alias];
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
            $this->_loadRelation($alias);
        }
        
        if ($recursive) {
            $this->getRelations();
            return $this->getRelation($alias, false);
        } else {
            try {
                throw new Exception();
            } catch (Exception $e) {
                //echo "" . "<br />";
                ///echo $e->getTraceAsString() . "<br /><br /><br />";
            }
            throw new Doctrine_Relation_Exception("Unknown relation '$alias'.");
        }
    }
    
    public function addRelation($name, Doctrine_Relation $relation)
    {
        if (isset($this->_relations[$name])) {
            throw new Doctrine_Relation_Exception("Relation '$name' does already exist.");
        }
        $this->_relations[$name] = $relation;
    }
    
    /**
     * Loads a relation and puts it into the collection of loaded relations.
     * In the process of initializing a relation it is common that multiple other, closely related
     * relations are initialized, too.
     *
     * @param string $alias  The name of the relation.
     */
    protected function _loadRelation($alias)
    {
        $def = $this->_pending[$alias];
        
        // check if reference class name exists
        // if it does we are dealing with an association relation (many-many)
        if (isset($def['refClass'])) {
            $def = $this->completeAssocDefinition($def);
            $localClasses = array_merge($this->_table->getOption('parents'), array($this->_table->getClassName()));

            // if the two many-many related components share the same table, we need
            // custom relation names to distinguish the relations.
            /*if ($this->_table->getInheritanceType() == Doctrine::INHERITANCETYPE_SINGLE_TABLE &&
                    in_array($def['class'], $this->_table->getOption('subclasses'))) {
                if ( ! isset($def['refRelationName']) || ! isset($def['refReverseRelationName'])) {
                    throw new Doctrine_Relation_Exception("Incomplete relation. Many-to-many relations between "
                            . "classes that share the same table (single table inheritance) need to specify "
                            . "a 'refRelationName' and a 'refReverseRelationName' to distinguish relations.");
                }            
                $relationName = $def['refRelationName'];
            } else {*/
                $relationName = $def['refClass'];
            //}

            if ( ! isset($this->_pending[$relationName]) && ! isset($this->_relations[$relationName])) {
                $this->_completeManyToManyRelation($def);
            }
            
            if (in_array($def['class'], $localClasses)) {
                $rel = new Doctrine_Relation_Nest($def);
            } else {
                $rel = new Doctrine_Relation_Association($def);
            }
        } else {
            // simple foreign key relation
            $def = $this->completeDefinition($def);

            if (isset($def['localKey'])) {
                $rel = new Doctrine_Relation_LocalKey($def);
            } else {
                $rel = new Doctrine_Relation_ForeignKey($def);
            }
        }
        if (isset($rel)) {
            unset($this->_pending[$alias]);
            $this->_relations[$alias] = $rel;
            return $rel;
        }
    }
    
    /**
     * Completes the initialization of a many-to-many relation by adding 
     * two uni-directional relations between this parser's table and the intermediary table.
     *
     * @param array  The relation definition.
     */
    protected function _completeManyToManyRelation(array $def)
    {
        $identifierColumnNames = $this->_table->getIdentifierColumnNames();
        $idColumnName = array_pop($identifierColumnNames);
        
        // if the two many-many related components shared the same table, we need a relation name
        // to distinguish the relations.
        $relationName = $def['refClass'];
        /*if (isset($def['refRelationName'])) {
           $relationName .= ' as ' . $def['refRelationName'];
        }*/
        
        // add a relation pointing from the intermediary table to the table of this parser
        $parser = $def['refTable']->getRelationParser();
        if ( ! $parser->hasRelation($this->_table->getClassName())) {
            $parser->bind($this->_table->getClassName(),
                    array('type'    => Doctrine_Relation::ONE,
                          'local'   => $def['local'],
                          'foreign' => $idColumnName,
                          'localKey' => true
                    )
            );
        }
        
        // add a relation pointing from this parser's table to the xref table 
        if ( ! $this->hasRelation($relationName/*$def['refClass']*/)) {            
            $this->bind($relationName, array(
                    'type' => Doctrine_Relation::MANY,
                    'foreign' => $def['local'],
                    'local' => $idColumnName)
                    );
        }
    }

    /**
     * getRelations
     * returns an array containing all relation objects
     *
     * @return array        an array of Doctrine_Relation objects
     */
    public function getRelations()
    {
        foreach ($this->_pending as $k => $v) {
            $this->getRelation($k);
        }

        return $this->_relations;
    }

    /**
     * getImpl
     * returns the table class of the concrete implementation for given template
     * if the given template is not a template then this method just returns the
     * table class for the given record
     *
     * @param string $template
     */
    public function getImpl(array &$def, $key)
    {
        $conn = $this->_table->getConnection();
        if (in_array('Doctrine_Template', class_parents($def[$key]))) {
            $impl = $this->_table->getImpl($def[$key]);
            if ($impl === null) {
                throw new Doctrine_Relation_Parser_Exception("Couldn't find concrete implementation for template " . $def[$key]);
            }
            $def[$key] = $impl;
        }

        return $conn->getMetadata($def[$key]);
    }
    
    protected function _isTemplate($className)
    {
        return in_array('Doctrine_Template', class_parents($className));
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
        $def['table'] = $this->getImpl($def, 'class');
        $def['localTable'] = $this->_table;
        $def['refTable'] = $this->getImpl($def, 'refClass');

        $id = $def['refTable']->getIdentifierColumnNames();

        if (count($id) > 1) {
            if ( ! isset($def['foreign'])) {
                // foreign key not set
                // try to guess the foreign key
                $def['foreign'] = ($def['local'] === $id[0]) ? $id[1] : $id[0];
            }
            if ( ! isset($def['local'])) {
                // foreign key not set
                // try to guess the foreign key

                $def['local'] = ($def['foreign'] === $id[0]) ? $id[1] : $id[0];
            }
        } else {

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
    public function getIdentifiers($table)
    {
        $componentNameToLower = strtolower($table->getComponentName());
        if (is_array($table->getIdentifier())) {
            $columns = array();      
            foreach ((array) $table->getIdentifierColumnNames() as $identColName) {
                $columns[] = $componentNameToLower . '_' . $identColName;
            }
        } else {
            $columns = $componentNameToLower . '_' . $table->getColumnName(
                    $table->getIdentifier());
        }

        return $columns;
    }

    /**
     * guessColumns
     *
     * @param array $classes                    an array of class names
     * @param Doctrine_Table $foreignTable      foreign table object
     * @return array                            an array of column names
     */
    public function guessColumns(array $classes, $foreignTable)
    {
        $conn = $this->_table->getConnection();

        foreach ($classes as $class) {
            try {
                $table = $conn->getClassMetadata($class);
            } catch (Doctrine_Table_Exception $e) {
                continue;
            }
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
     * @todo Description: What does it mean to complete a definition? What is done (not how)?
     *       Refactor (too long & nesting level)
     */
    public function completeDefinition($def)
    {
        $conn = $this->_table->getConnection();
        $def['table'] = $this->getImpl($def, 'class');
        //$def['class'] = $def['table']->getComponentName();
        $def['localTable'] = $this->_table;

        $foreignClasses = array_merge($def['table']->getOption('parents'), array($def['class']));
        $localClasses   = array_merge($this->_table->getOption('parents'), array($this->_table->getClassName()));

        $localIdentifierColumnNames = $this->_table->getIdentifierColumnNames();
        if ((count($localIdentifierColumnNames) - 1) < 0) {
            echo $this->_table->getClassName();
            var_dump($this->_table->getIdentifier());
            try {
                throw new Exception();
            } catch (Exception $e) {
                echo $e->getTraceAsString() . "<br />";
            }
        }
        $localIdColumnName = $localIdentifierColumnNames[count($localIdentifierColumnNames) - 1];
        $foreignIdentifierColumnNames = $def['table']->getIdentifierColumnNames();
        $foreignIdColumnName = $foreignIdentifierColumnNames[count($foreignIdentifierColumnNames) - 1];

        if (isset($def['local'])) {
            if ( ! isset($def['foreign'])) {
                // local key is set, but foreign key is not
                // try to guess the foreign key
                if ($def['local'] == $localIdColumnName) {
                    $def['foreign'] = $this->guessColumns($localClasses, $def['table']);
                } else {
                    // the foreign field is likely to be the
                    // identifier of the foreign class
                    $def['foreign'] = $foreignIdColumnName;
                    $def['localKey'] = true;
                }
            } else {
                if ((array)$def['local'] != $localIdentifierColumnNames &&
                        $def['type'] == Doctrine_Relation::ONE) {
                    $def['localKey'] = true;
                }
            }
        } else {
            if (isset($def['foreign'])) {
                // local key not set, but foreign key is set
                // try to guess the local key
                if ($def['foreign'] === $foreignIdColumnName) {
                    $def['localKey'] = true;
                    try {
                        $def['local'] = $this->guessColumns($foreignClasses, $this->_table);
                    } catch (Doctrine_Relation_Exception $e) {
                        $def['local'] = $localIdColumnName;
                    }
                } else {
                    $def['local'] = $localIdColumnName;
                }
            } else {
                // neither local or foreign key is being set
                // try to guess both keys

                $conn = $this->_table->getConnection();

                // the following loops are needed for covering inheritance
                foreach ($localClasses as $class) {
                    $table = $conn->getClassMetadata($class);
                    $identifierColumnNames = $table->getIdentifierColumnNames();
                    $idColumnName = array_pop($identifierColumnNames);
                    $column = strtolower($table->getComponentName())
                            . '_' . $idColumnName;

                    foreach ($foreignClasses as $class2) {
                        $table2 = $conn->getClassMetadata($class2);
                        if ($table2->hasColumn($column)) {
                            $def['foreign'] = $column;
                            $def['local'] = $idColumnName;
                            return $def;
                        }
                    }
                }

                foreach ($foreignClasses as $class) {
                    $table  = $conn->getClassMetadata($class);
                    $identifierColumnNames = $table->getIdentifierColumnNames();
                    $idColumnName = array_pop($identifierColumnNames);
                    $column = strtolower($table->getComponentName())
                            . '_' . $idColumnName;
                
                    foreach ($localClasses as $class2) {
                        $table2 = $conn->getClassMetadata($class2);
                        if ($table2->hasColumn($column)) {
                            $def['foreign']  = $idColumnName;
                            $def['local']    = $column;
                            $def['localKey'] = true;
                            return $def;
                        }
                    }
                }

                // auto-add columns and auto-build relation
                $columns = array();
                foreach ((array) $this->_table->getIdentifierColumnNames() as $id) {
                    // ?? should this not be $this->_table->getComponentName() ??
                    $column = strtolower($table->getComponentName())
                            . '_' . $id;

                    $col = $this->_table->getColumnDefinition($id);
                    $type = $col['type'];
                    $length = $col['length'];

                    unset($col['type']);
                    unset($col['length']);
                    unset($col['autoincrement']);
                    unset($col['sequence']);
                    unset($col['primary']);

                    $def['table']->setColumn($column, $type, $length, $col);
                    
                    $columns[] = $column;
                }
                if (count($columns) > 1) {
                    $def['foreign'] = $columns;
                } else {
                    $def['foreign'] = $columns[0];
                }
                $def['local'] = $localIdColumnName;
            }
        }
        return $def;
    }
}