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
/**
 * Doctrine_Table   represents a database table
 *                  each Doctrine_Table holds the information of foreignKeys and associations
 *
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Table extends Doctrine_Configurable implements Countable
{
    /**
     * @var array $data                                 temporary data which is then loaded into Doctrine_Record::$data
     */
    private $data             = array();
    /**
     * @var array $primaryKeys                          an array containing all primary key column names
     */
    private $primaryKeys      = array();
    /**
     * @var mixed $identifier
     */
    private $identifier;
    /**
     * @see Doctrine_Identifier constants
     * @var integer $identifierType                     the type of identifier this table uses
     */
    private $identifierType;
    /**
     * @var string $query                               cached simple query
     */
    private $query;
    /**
     * @var Doctrine_Connection $conn                   Doctrine_Connection object that created this table
     */
    private $conn;
    /**
     * @var string $name
     */
    private $name;
    /**
     * @var array $identityMap                          first level cache
     */
    private $identityMap        = array();
    /**
     * @var Doctrine_Table_Repository $repository       record repository
     */
    private $repository;
    /**
     * @var array $columns                  an array of column definitions,
     *                                      keys as column names and values as column definitions
     *
     *                                      the value array has three values:
     *
     *                                      the column type, eg. 'integer'
     *                                      the column length, eg. 11
     *                                      the column options/constraints/validators. eg array('notnull' => true)
     *
     *                                      so the full columns array might look something like the following:
     *                                      array(
     *                                             'name' => array('string',  20, array('notnull' => true, 'default' => 'someone')),
     *                                             'age'  => array('integer', 11, array('notnull' => true))
     *                                              )
     */
    protected $columns          = array();
    /**
     * @var array $columnAliases            an array of column aliases
     *                                      keys as column aliases and values as column names
     */
    protected $columnAliases    = array();
    /**
     * @var integer $columnCount            cached column count, Doctrine_Record uses this column count in when
     *                                      determining its state
     */
    private $columnCount;
    /**
     * @var boolean $hasDefaultValues       whether or not this table has default values
     */
    private $hasDefaultValues;
    /**
     * @var array $options                  an array containing all options
     *
     *      -- name                         name of the component, for example component name of the GroupTable is 'Group'
     *
     *      -- parents                      the parent classes of this component
     *
     *      -- declaringClass               name of the table definition declaring class (when using inheritance the class
     *                                      that defines the table structure can be any class in the inheritance hierarchy, 
     *                                      hence we need reflection to check out which class actually calls setTableDefinition)
     *
     *      -- tableName                    database table name, in most cases this is the same as component name but in some cases
     *                                      where one-table-multi-class inheritance is used this will be the name of the inherited table
     *
     *      -- sequenceName                 Some databases need sequences instead of auto incrementation primary keys,
     *                                      you can set specific sequence for your table by calling setOption('sequenceName', $seqName)
     *                                      where $seqName is the name of the desired sequence
     *
     *      -- enumMap                      enum value arrays
     *
     *      -- inheritanceMap               inheritanceMap is used for inheritance mapping, keys representing columns and values
     *                                      the column values that should correspond to child classes
     *
     *      -- type                         table type (mysql example: INNODB)
     *
     *      -- charset                      character set
     *
     *      -- foreignKeys                  the foreign keys of this table
     *
     *      -- collation
     *
     *      -- indexes                      the index definitions of this table
     *
     *      -- treeImpl                     the tree implementation of this table (if any)
     *
     *      -- treeOptions                  the tree options
     *
     *      -- versioning
     */
    protected $options          = array('name'           => null,
        'tableName'      => null,
        'sequenceName'   => null,
        'inheritanceMap' => array(),
        'enumMap'        => array(),
        'engine'         => null,
        'charset'        => null,
        'collation'      => null,
        'treeImpl'       => null,
        'treeOptions'    => null,
        'indexes'        => array(),
        'parents'        => array(),
        'versioning'     => null,
    );
    /**
     * @var Doctrine_Tree $tree                 tree object associated with this table
     */
    protected $tree;
    /**
     * @var Doctrine_Relation_Parser $_parser   relation parser object
     */
    protected $_parser;
    /**
     * @var Doctrine_AuditLog $_auditLog
     */
    protected $_auditLog;
    /**
     * the constructor
     * @throws Doctrine_Connection_Exception    if there are no opened connections
     * @throws Doctrine_Table_Exception         if there is already an instance of this table
     * @return void
     */
    public function __construct($name, Doctrine_Connection $conn)
    {
        $this->conn = $conn;

        $this->setParent($this->conn);

        $this->options['name'] = $name;
        $this->_parser = new Doctrine_Relation_Parser($this);

        if ( ! class_exists($name) || empty($name)) {
            throw new Doctrine_Exception("Couldn't find class " . $name);
        }
        $record = new $name($this);

        $names = array();

        $class = $name;

        // get parent classes

        do {
            if ($class == "Doctrine_Record") {
                break;                        
            }

            $name  = $class;
            $names[] = $name;
        } while ($class = get_parent_class($class));

        // reverse names
        $names = array_reverse($names);
        // save parents
        array_pop($names);
        $this->options['parents'] = $names;

        // create database table
        if (method_exists($record, 'setTableDefinition')) {
            $record->setTableDefinition();

            // set the table definition for the given tree implementation
            if ($this->isTree()) {
                $this->getTree()->setTableDefinition();
            }
            
            $this->columnCount = count($this->columns);

            if (isset($this->columns)) {
                // get the declaring class of setTableDefinition method
                $method    = new ReflectionMethod($this->options['name'], 'setTableDefinition');
                $class     = $method->getDeclaringClass();

                $this->options['declaringClass'] = $class;

                if ( ! isset($this->options['tableName'])) {
                    $this->options['tableName'] = Doctrine::tableize($class->getName());
                }
                switch (count($this->primaryKeys)) {
                case 0:
                    $this->columns = array_merge(array('id' =>
                        array('integer',
                            20,
                            array('autoincrement' => true,
                            'primary'       => true,
                        )
                    )
                ), $this->columns);

                    $this->primaryKeys[] = 'id';
                    $this->identifier = 'id';
                    $this->identifierType = Doctrine::IDENTIFIER_AUTOINC;
                    $this->columnCount++;
                    break;
                default:
                    if (count($this->primaryKeys) > 1) {
                        $this->identifier = $this->primaryKeys;
                        $this->identifierType = Doctrine::IDENTIFIER_COMPOSITE;

                    } else {
                        foreach ($this->primaryKeys as $pk) {
                            $e = $this->columns[$pk][2];

                            $found = false;

                            foreach ($e as $option => $value) {
                                if ($found)
                                    break;

                                $e2 = explode(':', $option);

                                switch (strtolower($e2[0])) {
                                case 'autoincrement':
                                case 'autoinc':
                                    $this->identifierType = Doctrine::IDENTIFIER_AUTOINC;
                                    $found = true;
                                    break;
                                case 'seq':
                                case 'sequence':
                                    $this->identifierType = Doctrine::IDENTIFIER_SEQUENCE;
                                    $found = true;

                                    if ($value) {
                                        $this->options['sequenceName'] = $value;
                                    } else {
                                        if (($sequence = $this->getAttribute(Doctrine::ATTR_DEFAULT_SEQUENCE)) !== null) {
                                            $this->options['sequenceName'] = $sequence;
                                        } else {
                                            $this->options['sequenceName'] = $this->conn->getSequenceName($this->options['tableName']);
                                        }
                                    }
                                    break;
                                }
                            }
                            if ( ! isset($this->identifierType)) {
                                $this->identifierType = Doctrine::IDENTIFIER_NATURAL;
                            }
                            $this->identifier = $pk;
                        }
                    }
                }
            }
        } else {
            throw new Doctrine_Table_Exception("Class '$name' has no table definition.");
        }

        $record->setUp();

        // if tree, set up tree
        if ($this->isTree()) {
            $this->getTree()->setUp();
        }
        $this->repository = new Doctrine_Table_Repository($this);
    }
    /**
     * export
     * exports this table to database based on column and option definitions
     *
     * @throws Doctrine_Connection_Exception    if some error other than Doctrine::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation
     * @return boolean                          whether or not the export operation was successful
     *                                          false if table already existed in the database
     */
    public function export() 
    {
        $this->conn->export->exportTable($this);
    }
    /**
     * getExportableFormat
     * returns exportable presentation of this object
     *
     * @return array
     */
    public function getExportableFormat($parseForeignKeys = true)
    {
        $columns = array();
        $primary = array();

        foreach ($this->getColumns() as $name => $column) {
            $definition = $column[2];
            $definition['type'] = $column[0];
            $definition['length'] = $column[1];

            switch ($definition['type']) {
            case 'enum':
                if (isset($definition['default'])) {
                    $definition['default'] = $this->enumIndex($name, $definition['default']);
                }
                break;
            case 'boolean':
                if (isset($definition['default'])) {
                    $definition['default'] = $this->getConnection()->convertBooleans($definition['default']);
                }
                break;
            }
            $columns[$name] = $definition;

            if(isset($definition['primary']) && $definition['primary']) {
                $primary[] = $name;
            }
        }
        $options['foreignKeys'] = array();

        if ($parseForeignKeys) {
            if ($this->getAttribute(Doctrine::ATTR_EXPORT) & Doctrine::EXPORT_CONSTRAINTS) {
    
                $constraints = array();

                $emptyIntegrity = array('onUpdate' => null,
                                        'onDelete' => null);

                foreach ($this->getRelations() as $name => $relation) {
                    $fk = $relation->toArray();
                    $fk['foreignTable'] = $relation->getTable()->getTableName();

                    if ($relation->getTable() === $this && in_array($relation->getLocal(), $primary)) {
                        if ($relation->hasConstraint()) {
                            throw new Doctrine_Table_Exception("Badly constructed integrity constraints.");
                        }
                        
                        continue;
                    }

                    $integrity = array('onUpdate' => $fk['onUpdate'],
                                       'onDelete' => $fk['onDelete']);

                    if ($relation instanceof Doctrine_Relation_ForeignKey) {    
                        if ($relation->getLocal() !== $relation->getTable()->getIdentifier() && 
                            $relation->getLocal() !== $this->getIdentifier() ||
                            $relation->hasConstraint()) {

                            $def = array('local'        => $relation->getLocal(),
                                         'foreign'      => $this->getIdentifier(),
                                         'foreignTable' => $relation->getTable()->getTableName());

                            if (($key = array_search($def, $options['foreignKeys'])) === false) {
                                $options['foreignKeys'][] = $def;
                                
                                $constraints[] = $integrity;
                            } else {
                                if ($integrity !== $emptyIntegrity) {
                                    $constraints[$key] = $integrity;
                                }
                            }
                        }
                    } elseif ($relation instanceof Doctrine_Relation_LocalKey) {

                        if ($relation->getLocal() !== $this->getIdentifier() &&
                            $relation->getForeign() !== $relation->getTable()->getIdentifier()) {

                            $def = array('local'        => $relation->getLocal(),
                                         'foreign'      => $this->getIdentifier(),
                                         'foreignTable' => $relation->getTable()->getTableName());
    
                            if (($key = array_search($def, $options['foreignKeys'])) === false) {
                                $options['foreignKeys'][] = $def;
                                
                                $constraints[] = $integrity;
                            } else {
                                if ($integrity !== $emptyIntegrity) {
                                    $constraints[$key] = $integrity;
                                }
                            }
                        }
                    } elseif ($relation instanceof Doctrine_Relation_Nest) {
                        /**
                        $def = array('local'        => $relation->getLocal(),
                                     'table'        => $relation->getAssociationTable()->getTableName(),
                                     'foreign'      => $this->getIdentifier(),
                                     'foreignTable' => $this->getTableName());


                        if (($key = array_search($def, $options['foreignKeys'])) === false) {
                            $options['foreignKeys'][] = $def;
                            
                            $constraints[] = $integrity;
                        } else {
                            if ($integrity !== $emptyIntegrity) {
                                $constraints[$key] = $integrity;
                            }
                        }

                        $def = array('local'        => $relation->getForeign(),
                                     'table'        => $relation->getAssociationTable()->getTableName(),
                                     'foreign'      => $this->getIdentifier(),
                                     'foreignTable' => $relation->getTable()->getTableName());

                        if (($key = array_search($def, $options['foreignKeys'])) === false) {
                            $options['foreignKeys'][] = $def;

                            if ( ! isset($integrity['onDelete'])) {
                                $integrity['onDelete'] = 'CASCADE';
                            }

                            $constraints[] = $integrity;
                        } else {
                            if ($integrity !== $emptyIntegrity) {
                                if ( ! isset($integrity['onDelete'])) {
                                    $integrity['onDelete'] = 'CASCADE';
                                }                                                                	

                                $constraints[$key] = $integrity;
                            }
                        }
                        */
                    } elseif ($relation instanceof Doctrine_Relation_Association) {
                        /**
                        $def = array('local'        => $relation->getLocal(),
                                     'table'        => $relation->getAssociationTable()->getTableName(),
                                     'foreign'      => $this->getIdentifier(),
                                     'foreignTable' => $this->getTableName());
                        if (($key = array_search($def, $options['foreignKeys'])) === false) {
                            $options['foreignKeys'][] = $def;
                            
                            if ( ! isset($integrity['onDelete'])) {
                                $integrity['onDelete'] = 'CASCADE';
                            }

                            $constraints[] = $integrity;
                        } else {
                            if ($integrity !== $emptyIntegrity) {
                                if ( ! isset($integrity['onDelete'])) {
                                    $integrity['onDelete'] = 'CASCADE';
                                }                                
                                $constraints[$key] = $integrity;
                            }
                        }
                        */
                    }

                }

                foreach ($constraints as $k => $def) {
                    $options['foreignKeys'][$k] = array_merge($options['foreignKeys'][$k], $def);
                }

            }
        }
        $options['primary'] = $primary;
        
        return array('tableName' => $this->getOption('tableName'), 
                     'columns'   => $columns, 
                     'options'   => array_merge($this->getOptions(), $options));
    }
    /**
     * exportConstraints
     * exports the constraints of this table into database based on option definitions
     *
     * @throws Doctrine_Connection_Exception    if something went wrong on db level
     * @return void
     */
    public function exportConstraints()
    {
        try {
            $this->conn->beginTransaction();

            foreach ($this->options['index'] as $index => $definition) {
                $this->conn->export->createIndex($this->options['tableName'], $index, $definition);
            }
            $this->conn->commit();
        } catch(Doctrine_Connection_Exception $e) {
            $this->conn->rollback();

            throw $e;
        }
    }
    /**
     * getRelationParser
     * return the relation parser associated with this table
     *
     * @return Doctrine_Relation_Parser     relation parser object
     */
    public function getRelationParser()
    {
        return $this->_parser;
    }
    /**
     * __get
     * an alias for getOption
     *
     * @param string $option
     */
    public function __get($option)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }
        return null;
    }
    /**
     * __isset
     *
     * @param string $option
     */
    public function __isset($option) 
    {
        return isset($this->options[$option]);
    }
    /**
     * getOptions
     * returns all options of this table and the associated values
     *
     * @return array    all options and their values
     */
    public function getOptions()
    {
        return $this->options;
    }
    /**
     * addForeignKey
     *
     * adds a foreignKey to this table
     *
     * @return void
     */
    public function addForeignKey(array $definition)
    {
        $this->options['foreignKeys'][] = $definition;
    }
    /**
     * addIndex
     * 
     * adds an index to this table
     *
     * @return void
     */
    public function addIndex($index, array $definition)
    {
        $this->options['indexes'][$index] = $definition;
    }
    /**
     * getIndex
     *
     * @return array|boolean        array on success, FALSE on failure
     */
    public function getIndex($index) 
    {
        if (isset($this->options['indexes'][$index])) {
            return $this->options['indexes'][$index];
        }

        return false;
    }
    public function bind($args, $type)
    {
    	$options = array();
        $options['type'] = $type;
        
        // the following is needed for backwards compatibility
        if (is_string($args[1])) {
            if ( ! isset($args[2])) {
                $args[2] = array();
            } elseif (is_string($args[2])) {
                $args[2] = (array) $args[2];
            }

            $classes = array_merge($this->options['parents'], array($this->getComponentName()));


            $e = explode('.', $args[1]);
            if (in_array($e[0], $classes)) {
                if ($options['type'] >= Doctrine_Relation::MANY) {
                    $options['foreign'] = $e[1];                                             	
                } else {
                    $options['local'] = $e[1];
                }
            } else {
                $e2 = explode(' as ', $args[0]);
                if ($e[0] !== $e2[0] && ( ! isset($e2[1]) || $e[0] !== $e2[1])) {
                    $options['refClass'] = $e[0];
                }

                $options['foreign'] = $e[1];
            }

            $options = array_merge($args[2], $options);

            $this->_parser->bind($args[0], $options);
        } else {
            $options = array_merge($args[1], $options);
            $this->_parser->bind($args[0], $options);
        }
    }
    /**
     * getRelation
     *
     * @param string $alias      relation alias
     */
    public function getRelation($alias, $recursive = true)
    {
        return $this->_parser->getRelation($alias, $recursive);
    }
    /**
     * getRelations
     * returns an array containing all relation objects
     *
     * @return array        an array of Doctrine_Relation objects
     */
    public function getRelations()
    {
        return $this->_parser->getRelations();
    }
    /**
     * createQuery
     * creates a new Doctrine_Query object and adds the component name
     * of this table as the query 'from' part
     *
     * @return Doctrine_Query
     */
    public function createQuery()
    {
        return Doctrine_Query::create()->from($this->getComponentName());
    }
    /**
     * getRepository
     *
     * @return Doctrine_Table_Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }
    /**
     * setOption
     * sets an option and returns this object in order to 
     * allow flexible method chaining
     *
     * @see Doctrine_Table::$_options   for available options
     * @param string $name              the name of the option to set
     * @param mixed $value              the value of the option
     * @return Doctrine_Table           this object
     */
    public function setOption($name, $value)
    {
        switch ($name) {
        case 'name':
        case 'tableName':
            break;
        case 'enumMap':
        case 'inheritanceMap':
        case 'index':
        case 'treeOptions':
            if ( ! is_array($value)) {
                throw new Doctrine_Table_Exception($name . ' should be an array.');
            }
            break;
        }
        $this->options[$name] = $value;
    }
    /**
     * getOption
     * returns the value of given option
     *
     * @param string $name  the name of the option
     * @return mixed        the value of given option
     */
    public function getOption($name)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }
        return null;
    }
    /**
     * getColumnName
     *
     * returns a column name for column alias
     * if the actual name for the alias cannot be found
     * this method returns the given alias
     *
     * @param string $alias         column alias
     * @return string               column name
     */
    public function getColumnName($alias)
    {
        $alias = strtolower($alias);
        if(isset($this->columnAliases[$alias])) {
            return $this->columnAliases[$alias];
        }

        return $alias;
    }
    /**
     * setColumn
     *
     * @param string $name
     * @param string $type
     * @param integer $length
     * @param mixed $options
     * @throws Doctrine_Table_Exception     if trying use wrongly typed parameter
     * @return void
     */
    public function setColumn($name, $type, $length = null, $options = array())
    {
        if (is_string($options)) {
            $options = explode('|', $options);
        }

        foreach ($options as $k => $option) {
            if (is_numeric($k)) {
                if ( ! empty($option)) {
                    $options[$option] = true;
                }
                unset($options[$k]);
            }
        }

        $name  = strtolower($name);
        $parts = explode(' as ', $name);

        if (count($parts) > 1) {
            $this->columnAliases[$parts[1]] = $parts[0];
            $name = $parts[0];
        }


        if ($length == null) {
            $length = 2147483647;
        }

        if ((string) (int) $length !== (string) $length) {
            throw new Doctrine_Table_Exception('Invalid argument given for column length');
        }

        $this->columns[$name] = array($type, $length, $options);

        if (isset($options['primary'])) {
            $this->primaryKeys[] = $name;
        }
        if (isset($options['default'])) {
            $this->hasDefaultValues = true;
        }
    }
    /**
     * hasDefaultValues
     * returns true if this table has default values, otherwise false
     *
     * @return boolean
     */
    public function hasDefaultValues()
    {
        return $this->hasDefaultValues;
    }
    /**
     * getDefaultValueOf
     * returns the default value(if any) for given column
     *
     * @param string $column
     * @return mixed
     */
    public function getDefaultValueOf($column)
    {
        $column = strtolower($column);
        if ( ! isset($this->columns[$column])) {
            throw new Doctrine_Table_Exception("Couldn't get default value. Column ".$column." doesn't exist.");
        }
        if (isset($this->columns[$column][2]['default'])) {
            return $this->columns[$column][2]['default'];
        } else {
            return null;
        }
    }
    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
    /**
     * @return integer
     */
    public function getIdentifierType()
    {
        return $this->identifierType;
    }
    /**
     * hasColumn
     * @return boolean
     */
    public function hasColumn($name)
    {
        return isset($this->columns[$name]);
    }
    /**
     * @param mixed $key
     * @return void
     */
    public function setPrimaryKey($key)
    {
        switch (gettype($key)) {
        case "array":
            $this->primaryKeys = array_values($key);
            break;
        case "string":
            $this->primaryKeys[] = $key;
            break;
        };
    }
    /**
     * returns all primary keys
     * @return array
     */
    public function getPrimaryKeys()
    {
        return $this->primaryKeys;
    }
    /**
     * @return boolean
     */
    public function hasPrimaryKey($key)
    {
        return in_array($key,$this->primaryKeys);
    }
    /**
     * @return Doctrine_Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }
    /**
     * create
     * creates a new record
     *
     * @param $array                    an array where keys are field names and values representing field values
     * @return Doctrine_Record
     */
    public function create(array $array = array()) {
        $this->data         = $array;
        $record = new $this->options['name']($this, true);
        $this->data         = array();
        return $record;
    }
    /**
     * finds a record by its identifier
     *
     * @param $id                       database row id
     * @return Doctrine_Record|false    a record for given database identifier
     */
    public function find($id)
    {
        if ($id !== null) {
            if ( ! is_array($id)) {
                $id = array($id);
            } else {
                $id = array_values($id);
            }

            $query  = 'SELECT ' . implode(', ', array_keys($this->columns)) . ' FROM ' . $this->getTableName() 
                    . ' WHERE ' . implode(' = ? AND ', $this->primaryKeys) . ' = ?';
            $query  = $this->applyInheritance($query);

            $params = array_merge($id, array_values($this->options['inheritanceMap']));

            $stmt  = $this->conn->execute($query, $params);

            $this->data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($this->data === false)
                return false;

            return $this->getRecord();
        }
        return false;
    }
    /**
     * applyInheritance
     * @param $where                    query where part to be modified
     * @return string                   query where part with column aggregation inheritance added
     */
    final public function applyInheritance($where)
    {
        if ( ! empty($this->options['inheritanceMap'])) {
            $a = array();
            foreach ($this->options['inheritanceMap'] as $field => $value) {
                $a[] = $field . ' = ?';
            }
            $i = implode(' AND ', $a);
            $where .= ' AND ' . $i;
        }
        return $where;
    }
    /**
     * findAll
     * returns a collection of records
     *
     * @return Doctrine_Collection
     */
    public function findAll()
    {
        $graph = new Doctrine_Query($this->conn);
        $users = $graph->query("FROM ".$this->options['name']);
        return $users;
    }
    /**
     * findByDql
     * finds records with given DQL where clause
     * returns a collection of records
     *
     * @param string $dql               DQL after WHERE clause
     * @param array $params             query parameters
     * @return Doctrine_Collection
     */
    public function findBySql($dql, array $params = array()) {
        $q = new Doctrine_Query($this->conn);
        $users = $q->query("FROM ".$this->options['name']." WHERE ".$dql, $params);
        return $users;
    }

    public function findByDql($dql, array $params = array()) {
        return $this->findBySql($dql, $params);
    }
    /**
     * clear
     * clears the first level cache (identityMap)
     *
     * @return void
     */
    public function clear()
    {
        $this->identityMap = array();
    }
    /**
     * getRecord
     * first checks if record exists in identityMap, if not
     * returns a new record
     *
     * @return Doctrine_Record
     */
    public function getRecord()
    {
    	if ( ! empty($this->data)) {
            $this->data = array_change_key_case($this->data, CASE_LOWER);
    
            $key = $this->getIdentifier();
    
            if ( ! is_array($key)) {
                $key = array($key);
            }
    
            $found = false;
            foreach ($key as $k) {
                if ( ! isset($this->data[$k])) {
                    // primary key column not found return new record
                    $found = true;
                    break;
                }
                $id[] = $this->data[$k];
            }
            
            if ($found) {
                $this->data = array();
                $recordName = $this->getClassnameToReturn();
                $record = new $recordName($this, true);    

                
                return $record;
            }


            $id = implode(' ', $id);
    
            if (isset($this->identityMap[$id])) {
                $record = $this->identityMap[$id];
                $record->hydrate($this->data);
            } else {
                $recordName = $this->getClassnameToReturn();
                $record = new $recordName($this);
                $this->identityMap[$id] = $record;
            }
            $this->data = array();
        } else {
            $recordName = $this->getClassnameToReturn();
            $record = new $recordName($this, true);
        }


        return $record;
    }

    /**
     * Get the classname to return. Most often this is just the options['name']
     *
     * Check the subclasses option and the inheritanceMap for each subclass to see 
     * if all the maps in a subclass is met. If this is the case return that 
     * subclass name. If no subclasses match or if there are no subclasses defined 
     * return the name of the class for this tables record.
     *
     * @todo this function could use reflection to check the first time it runs 
     * if the subclassing option is not set. 
     *
     * @return string The name of the class to create
     *
     */ 
    public function getClassnameToReturn()
    {
        if (!isset($this->options['subclasses'])) {
            return $this->options['name'];
        }
        foreach ($this->options['subclasses'] as $subclass) {
            $table = $this->conn->getTable($subclass);
            $inheritanceMap = $table->getOption('inheritanceMap');
            $nomatch = false;
            foreach ($inheritanceMap as $key => $value) {
                if (!isset($this->data[$key]) || $this->data[$key] != $value) {
                    $nomatch = true;
                    break;
                }
            }
            if ( ! $nomatch) {
                return $table->getComponentName();
            }
        }
        return $this->options['name'];
    }

    /**
     * @param $id                       database row id
     * @throws Doctrine_Find_Exception
     */
    final public function getProxy($id = null)
    {
        if ($id !== null) {
            $query = 'SELECT ' . implode(', ',$this->primaryKeys) 
                . ' FROM ' . $this->getTableName() 
                . ' WHERE ' . implode(' = ? && ',$this->primaryKeys).' = ?';
            $query = $this->applyInheritance($query);

            $params = array_merge(array($id), array_values($this->options['inheritanceMap']));

            $this->data = $this->conn->execute($query,$params)->fetch(PDO::FETCH_ASSOC);

            if ($this->data === false)
                return false;
        }
        return $this->getRecord();
    }
    /**
     * count
     *
     * @return integer
     */
    public function count()
    {
        $a = $this->conn->getDBH()->query("SELECT COUNT(1) FROM ".$this->options['tableName'])->fetch(PDO::FETCH_NUM);
        return current($a);
    }
    /**
     * @return Doctrine_Query                           a Doctrine_Query object
     */
    public function getQueryObject()
    {
        $graph = new Doctrine_Query($this->getConnection());
        $graph->load($this->getComponentName());
        return $graph;
    }
    /**
     * execute
     * @param string $query
     * @param array $array
     * @param integer $limit
     * @param integer $offset
     */
    public function execute($query, array $array = array(), $limit = null, $offset = null) {
        $coll  = new Doctrine_Collection($this);
        $query = $this->conn->modifyLimitQuery($query,$limit,$offset);
        if ( ! empty($array)) {
            $stmt = $this->conn->getDBH()->prepare($query);
            $stmt->execute($array);
        } else {
            $stmt = $this->conn->getDBH()->query($query);
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($data as $row) {
            $this->data = $row;
            $record = $this->getRecord();
            $coll->add($record);
        }
        return $coll;
    }
    /**
     * @param string $field
     * @return array
     */
    final public function getEnumValues($field)
    {
        if (isset($this->columns[$field][2]['values'])) {
            return $this->columns[$field][2]['values'];
        } else {
            return array();
        }
    }
    /**
     * enumValue
     *
     * @param string $field
     * @param integer $index
     * @return mixed
     */
    public function enumValue($field, $index)
    {
        if ($index instanceof Doctrine_Null)
            return $index;

        return isset($this->columns[$field][2]['values'][$index]) ? $this->columns[$field][2]['values'][$index] : $index;
    }
    /**
     * enumIndex
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    public function enumIndex($field, $value)
    {
        $values = $this->getEnumValues($field);

        return array_search($value, $values);
    }
    /**
     * getDefinitionOf
     *
     * @return string       ValueWrapper class name on success, false on failure
     */
    public function getValueWrapperOf($column)
    {
        if (isset($this->columns[$column][2]['wrapper'])) {
            return $this->columns[$column][2]['wrapper'];
        }
        return false;
    }
    /**
     * getColumnCount
     *
     * @return integer      the number of columns in this table
     */
    final public function getColumnCount()
    {
        return $this->columnCount;
    }

    /**
     * returns all columns and their definitions
     *
     * @return array
     */
    final public function getColumns()
    {
        return $this->columns;
    }
    /**
     * returns an array containing all the column names
     *
     * @return array
     */
    public function getColumnNames()
    {
        return array_keys($this->columns);
    }
    /**
     * getDefinitionOf
     *
     * @return mixed        array on success, false on failure
     */
    public function getDefinitionOf($column)
    {
        if (isset($this->columns[$column])) {
            return $this->columns[$column];
        }
        return false;
    }
    /**
     * getTypeOf
     *
     * @return mixed        string on success, false on failure
     */
    public function getTypeOf($column)
    {
        if (isset($this->columns[$column])) {
            return $this->columns[$column][0];
        }
        return false;
    }
    /**
     * setData
     * doctrine uses this function internally
     * users are strongly discouraged to use this function
     *
     * @param array $data               internal data
     * @return void
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }
    /**
     * returns the maximum primary key value
     *
     * @return integer
     */
    final public function getMaxIdentifier()
    {
        $sql  = "SELECT MAX(".$this->getIdentifier().") FROM ".$this->getTableName();
        $stmt = $this->conn->getDBH()->query($sql);
        $data = $stmt->fetch(PDO::FETCH_NUM);
        return isset($data[0])?$data[0]:1;
    }
    /**
     * returns simple cached query
     *
     * @return string
     */
    final public function getQuery()
    {
        return $this->query;
    }
    /**
     * returns internal data, used by Doctrine_Record instances
     * when retrieving data from database
     *
     * @return array
     */
    final public function getData()
    {
        return $this->data;
    }
    /**
     * getter for associated tree
     *
     * @return mixed  if tree return instance of Doctrine_Tree, otherwise returns false
     */    
    public function getTree() {
        if (isset($this->options['treeImpl'])) {
            if ( ! $this->tree) {
                $options = isset($this->options['treeOptions']) ? $this->options['treeOptions'] : array();
                $this->tree = Doctrine_Tree::factory($this, 
                    $this->options['treeImpl'], 
                    $options
                );
            }
            return $this->tree;
        }
        return false;
    }
    public function getComponentName() 
    {
        return $this->options['name'];
    }
    public function getTableName()
    {
        return $this->options['tableName'];
    }
    public function setTableName($tableName)
    {
        $this->options['tableName'] = $tableName;	
    }
    /**
     * determine if table acts as tree
     *
     * @return mixed  if tree return true, otherwise returns false
     */    
    public function isTree() {
        return ( ! is_null($this->options['treeImpl'])) ? true : false;
    }
    public function getAuditLog()
    {
        if ( ! isset($this->_auditLog)) {
            $this->_auditLog = new Doctrine_AuditLog($this);
        }
        
        return $this->_auditLog;
    }
    /**
     * returns a string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return Doctrine_Lib::getTableAsString($this);
    }
}
