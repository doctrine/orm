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
 * @subpackage  Table
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Table extends Doctrine_Configurable implements Countable
{
    /**
     * @var array $data                                 temporary data which is then loaded into Doctrine_Record::$_data
     */
    protected $_data             = array();

    /**
     * @var mixed $identifier   The field names of all fields that are part of the identifier/primary key
     */
    protected $_identifier = array();

    /**
     * @see Doctrine_Identifier constants
     * @var integer $identifierType                     the type of identifier this table uses
     */
    protected $_identifierType;

    /**
     * @var Doctrine_Connection $conn                   Doctrine_Connection object that created this table
     */
    protected $_conn;

    /**
     * @var array $identityMap                          first level cache
     */
    protected $_identityMap        = array();

    /**
     * @var Doctrine_Table_Repository $repository       record repository
     */
    protected $_repository;

    /**
     * @var array $columns                  an array of column definitions,
     *                                      keys are column names and values are column definitions
     *
     *                                      the definition array has atleast the following values:
     *
     *                                      -- type         the column type, eg. 'integer'
     *                                      -- length       the column length, eg. 11
     *
     *                                      additional keys:
     *                                      -- notnull      whether or not the column is marked as notnull
     *                                      -- values       enum values
     *                                      -- notblank     notblank validator + notnull constraint
     *                                      ... many more
     */
    protected $_columns          = array();

    /**
     * @var array $_fieldNames            an array of field names. used to look up field names
     *                                    from column names.
     *                                    keys are column names and values are field names
     */
    protected $_fieldNames    = array();
    
    /**
     * 
     * @var array $_columnNames             an array of column names
     *                                      keys are field names and values column names.
     *                                      used to look up column names from field names.
     *                                      this is the reverse lookup map of $_fieldNames.
     */
    protected $_columnNames = array();

    /**
     * @var integer $columnCount            cached column count, Doctrine_Record uses this column count in when
     *                                      determining its state
     */
    protected $columnCount;

    /**
     * @var boolean $hasDefaultValues       whether or not this table has default values
     */
    protected $hasDefaultValues;

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
     *      -- checks                       the check constraints of this table, eg. 'price > dicounted_price'
     *
     *      -- collation                    collation attribute
     *
     *      -- indexes                      the index definitions of this table
     *
     *      -- treeImpl                     the tree implementation of this table (if any)
     *
     *      -- treeOptions                  the tree options
     *
     *      -- queryParts                   the bound query parts
     *
     *      -- versioning
     */
    protected $_options      = array('name'           => null,
                                     'tableName'      => null,
                                     'sequenceName'   => null,
                                     'inheritanceMap' => array(),
                                     'enumMap'        => array(),
                                     'type'           => null,
                                     'charset'        => null,
                                     'collation'      => null,
                                     'treeImpl'       => null,
                                     'treeOptions'    => null,
                                     'indexes'        => array(),
                                     'parents'        => array(),
                                     'joinedParents'  => array(),
                                     'queryParts'     => array(),
                                     'versioning'     => null,
                                     );

    /**
     * @var Doctrine_Tree $tree                 tree object associated with this table
     */
    protected $_tree;

    /**
     * @var Doctrine_Relation_Parser $_parser   relation parser object
     */
    protected $_parser;

    /**
     * @see Doctrine_Template
     * @var array $_templates                   an array containing all templates attached to this table
     */
    protected $_templates   = array();

    /**
     * @see Doctrine_Record_Filter
     * @var array $_filters                     an array containing all record filters attached to this table
     */
    protected $_filters     = array();
    /**
     * @see Doctrine_Record_Generator
     * @var array $_generators                  an array containing all generators attached to this table
     */
    protected $_generators     = array();

    /**
     * @var array $_invokedMethods              method invoker cache
     */
    protected $_invokedMethods = array();



    /**
     * the constructor
     *
     * @throws Doctrine_Connection_Exception    if there are no opened connections
     * @param string $name                      the name of the component
     * @param Doctrine_Connection $conn         the connection associated with this table
     */
    public function __construct($name, Doctrine_Connection $conn, $initDefinition = false)
    {
        $this->_conn = $conn;

        $this->setParent($this->_conn);

        $this->_options['name'] = $name;
        $this->_parser = new Doctrine_Relation_Parser($this);

        if ($initDefinition) {
            $record = $this->initDefinition();
    
            $this->initIdentifier();
    
            $record->setUp();
    
            // if tree, set up tree
            if ($this->isTree()) {
                $this->getTree()->setUp();
            }
        } else {
            if ( ! isset($this->_options['tableName'])) {
                $this->_options['tableName'] = Doctrine::tableize($this->_options['name']);
            }
        }
        $this->_filters[]  = new Doctrine_Record_Filter_Standard();
        $this->_repository = new Doctrine_Table_Repository($this);
    }
    
    /**
     * Initializes the in-memory table definition.
     *
     * @param string $name
     */
    public function initDefinition()
    {        
        $name = $this->_options['name'];
        if ( ! class_exists($name) || empty($name)) {
            throw new Doctrine_Exception("Couldn't find class " . $name);
        }
        $record = new $name($this);

        $names = array();

        $class = $name;

        // get parent classes

        do {
            if ($class === 'Doctrine_Record') {
                break;
            }

            $name = $class;
            $names[] = $name;
        } while ($class = get_parent_class($class));

        if ($class === false) {
            throw new Doctrine_Table_Exception('Unknown component.');
        }

        // reverse names
        $names = array_reverse($names);
        // save parents
        array_pop($names);
        $this->_options['parents'] = $names;

        // create database table
        if (method_exists($record, 'setTableDefinition')) {
            $record->setTableDefinition();
            // get the declaring class of setTableDefinition method
            $method = new ReflectionMethod($this->_options['name'], 'setTableDefinition');
            $class = $method->getDeclaringClass();
            
        } else {
            $class = new ReflectionClass($class);
        }

        $this->_options['joinedParents'] = array();

        foreach (array_reverse($this->_options['parents']) as $parent) {

            if ($parent === $class->getName()) {
                continue;
            }
            $ref = new ReflectionClass($parent);
            
            if ($ref->isAbstract()) {
                continue;
            }
            $parentTable = $this->_conn->getTable($parent);

            $found = false;
            $parentColumns = $parentTable->getColumns();

            foreach ($parentColumns as $columnName => $definition) {
                if ( ! isset($definition['primary'])) {
                    if (isset($this->_columns[$columnName])) {
                        $found = true;
                        break;
                    } else {
                        if ( ! isset($parentColumns[$columnName]['owner'])) {
                            $parentColumns[$columnName]['owner'] = $parentTable->getComponentName();
                        }

                        $this->_options['joinedParents'][] = $parentColumns[$columnName]['owner'];
                    }
                } else {
                    unset($parentColumns[$columnName]);
                }
            }

            if ($found) {
                continue;
            }

            foreach ($parentColumns as $columnName => $definition) {    
                $fullName = $columnName . ' as ' . $parentTable->getFieldName($columnName);
                $this->setColumn($fullName, $definition['type'], $definition['length'], $definition, true);
            }

            break;
        }
        
        $this->_options['joinedParents'] = array_values(array_unique($this->_options['joinedParents']));

        $this->_options['declaringClass'] = $class;

        // set the table definition for the given tree implementation
        if ($this->isTree()) {
            $this->getTree()->setTableDefinition();
        }

        $this->columnCount = count($this->_columns);
        
        if ( ! isset($this->_options['tableName'])) {
            $this->_options['tableName'] = Doctrine::tableize($class->getName());
        }
        
        return $record;
    }
    
    /**
     * Initializes the table identifier(s)/primary key(s)
     *
     */
    public function initIdentifier()
    {
        switch (count($this->_identifier)) {
            case 0:
                if ( ! empty($this->_options['joinedParents'])) {
                    $root = current($this->_options['joinedParents']);
                    
                    $table = $this->_conn->getTable($root);
                
                    $this->_identifier = $table->getIdentifier();

                    $this->_identifierType = ($table->getIdentifierType() !== Doctrine::IDENTIFIER_AUTOINC)
                                            ? $table->getIdentifierType() : Doctrine::IDENTIFIER_NATURAL;

                    // add all inherited primary keys
                    foreach ((array) $this->_identifier as $id) {
                        $definition = $table->getDefinitionOf($id);

                        // inherited primary keys shouldn't contain autoinc
                        // and sequence definitions
                        unset($definition['autoincrement']);
                        unset($definition['sequence']);

                        // add the inherited primary key column
                        $fullName = $id . ' as ' . $table->getFieldName($id);
                        $this->setColumn($fullName, $definition['type'], $definition['length'],
                                $definition, true);
                    }
                } else {
                    $definition = array('type' => 'integer',
                                        'length' => 20,
                                        'autoincrement' => true,
                                        'primary' => true);
                    $this->setColumn('id', $definition['type'], $definition['length'], $definition, true);
                    $this->_identifier = 'id';
                    $this->_identifierType = Doctrine::IDENTIFIER_AUTOINC;
                }
                $this->columnCount++;
                break;
            case 1:
                foreach ($this->_identifier as $pk) {
                    $columnName = $this->getColumnName($pk);
                    $e = $this->_columns[$columnName];

                    $found = false;

                    foreach ($e as $option => $value) {
                        if ($found) {
                            break;
                        }

                        $e2 = explode(':', $option);

                        switch (strtolower($e2[0])) {
                            case 'autoincrement':
                            case 'autoinc':
                                $this->_identifierType = Doctrine::IDENTIFIER_AUTOINC;
                                $found = true;
                                break;
                            case 'seq':
                            case 'sequence':
                                $this->_identifierType = Doctrine::IDENTIFIER_SEQUENCE;
                                $found = true;

                                if ($value) {
                                    $this->_options['sequenceName'] = $value;
                                } else {
                                    if (($sequence = $this->getAttribute(Doctrine::ATTR_DEFAULT_SEQUENCE)) !== null) {
                                        $this->_options['sequenceName'] = $sequence;
                                    } else {
                                        $this->_options['sequenceName'] = $this->_conn->getSequenceName($this->_options['tableName']);
                                    }
                                }
                                break;
                        }
                    }
                    if ( ! isset($this->_identifierType)) {
                        $this->_identifierType = Doctrine::IDENTIFIER_NATURAL;
                    }
                }

                $this->_identifier = $pk;

                break;
            default:
                $this->_identifierType = Doctrine::IDENTIFIER_COMPOSITE;
        }
    }
    
    /**
     * Gets the owner of a column.
     * The owner of a column is the name of the component in a hierarchy that
     * defines the column.
     *
     * @param string $columnName   The column name 
     * @return string  The name of the owning/defining component
     */
    public function getColumnOwner($columnName)
    {
        if (isset($this->_columns[$columnName]['owner'])) {
            return $this->_columns[$columnName]['owner'];
        } else {
            return $this->getComponentName();
        }
    }
    
    /**
     * Checks whether a column is inherited from a component further up in the hierarchy.
     *
     * @param $columnName  The column name
     * @return boolean  TRUE if column is inherited, FALSE otherwise.
     */
    public function isInheritedColumn($columnName)
    {
        return (isset($this->_columns[$columnName]['owner']));
    }
    
    /**
     * Checks whether a field is part of the table identifier/primary key field(s).
     *
     * @param string $fieldName  The field name
     * @return boolean  TRUE if the field is part of the table identifier/primary key field(s), 
     *                  FALSE otherwise.
     */
    public function isIdentifier($fieldName)
    {
        return ($fieldName === $this->getIdentifier() || 
                in_array($fieldName, (array) $this->getIdentifier()));
    }

    public function getMethodOwner($method)
    {
        return (isset($this->_invokedMethods[$method])) ?
                      $this->_invokedMethods[$method] : false;
    }
    
    public function setMethodOwner($method, $class)
    {
        $this->_invokedMethods[$method] = $class;
    }

    /**
     * getTemplates
     * returns all templates attached to this table
     *
     * @return array     an array containing all templates
     */
    public function getTemplates()
    {
        return $this->_templates;
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
        $this->_conn->export->exportTable($this);
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

        foreach ($this->getColumns() as $name => $definition) {

            if (isset($definition['owner'])) {
                continue;
            }

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

            if (isset($definition['primary']) && $definition['primary']) {
                $primary[] = $name;
            }
        }
        $options['foreignKeys'] = array();


        if ($parseForeignKeys && $this->getAttribute(Doctrine::ATTR_EXPORT)
                & Doctrine::EXPORT_CONSTRAINTS) {

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

                if ($relation instanceof Doctrine_Relation_LocalKey) {
                    $def = array('local'        => $relation->getLocal(),
                                 'foreign'      => $relation->getForeign(),
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
            }

            foreach ($constraints as $k => $def) {
                $options['foreignKeys'][$k] = array_merge($options['foreignKeys'][$k], $def);
            }
        }

        $options['primary'] = $primary;

        return array('tableName' => $this->getOption('tableName'),
                     'columns'   => $columns,
                     'options'   => array_merge($this->getOptions(), $options));
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
        if (isset($this->_options[$option])) {
            return $this->_options[$option];
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
        return isset($this->_options[$option]);
    }

    /**
     * getOptions
     * returns all options of this table and the associated values
     *
     * @return array    all options and their values
     */
    public function getOptions()
    {
        return $this->_options;
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
        $this->_options['foreignKeys'][] = $definition;
    }

    /**
     * addCheckConstraint
     *
     * adds a check constraint to this table
     *
     * @return void
     */
    public function addCheckConstraint($definition, $name)
    {
        if (is_string($name)) {
            $this->_options['checks'][$name] = $definition;
        } else {
            $this->_options['checks'][] = $definition;
        }

        return $this;
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
        $this->_options['indexes'][$index] = $definition;
    }

    /**
     * getIndex
     *
     * @return array|boolean        array on success, FALSE on failure
     */
    public function getIndex($index)
    {
        if (isset($this->_options['indexes'][$index])) {
            return $this->_options['indexes'][$index];
        }

        return false;
    }
    
    /** 
     * DESCRIBE WHAT THIS METHOD DOES, PLEASE!
     *
     * @todo Name proposal: addRelation
     */
    public function bind($args, $type)
    {
        $options = array();
        $options['type'] = $type;

        if ( ! isset($args[1])) {
            $args[1] = array();
        }

        // the following is needed for backwards compatibility
        if (is_string($args[1])) {
            if ( ! isset($args[2])) {
                $args[2] = array();
            } elseif (is_string($args[2])) {
                $args[2] = (array) $args[2];
            }

            $classes = array_merge($this->_options['parents'], array($this->getComponentName()));


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
     * hasRelation
     *
     * @param string $alias      the relation to check if exists
     * @return boolean           true if the relation exists otherwise false
     */
    public function hasRelation($alias)
    {
        return $this->_parser->hasRelation($alias);
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
     * @param string Optional alias name for component aliasing.
     *
     * @return Doctrine_Query
     */
    public function createQuery($alias = '')
    {
        if ( ! empty($alias)) {
            $alias = ' ' . trim($alias);
        }
        return Doctrine_Query::create($this->_conn)->from($this->getComponentName() . $alias);
    }

    /**
     * getRepository
     *
     * @return Doctrine_Table_Repository
     */
    public function getRepository()
    {
        return $this->_repository;
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
        $this->_options[$name] = $value;
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
        if (isset($this->_options[$name])) {
            return $this->_options[$name];
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
    public function getColumnName($fieldName)
    {
        if (isset($this->_columnNames[$fieldName])) {
            return $this->_columnNames[$fieldName];
        }
        return $fieldName;
    }
    
    /**
     *
     *
     */
    public function getColumnDefinition($columnName)
    {
        if ( ! isset($this->_columns[$columnName])) {
            return false;
        }
        return $this->_columns[$columnName];
    }
    
    /**
     * getColumnAlias
     * 
     * returns a column alias for a column name 
     * if no alias can be found the column name is returned.
     *
     * @param string $columnName    column name
     * @return string               column alias
     */
    public function getFieldName($columnName)
    {
        if (isset($this->_fieldNames[$columnName])) {
            return $this->_fieldNames[$columnName];
        }
        return $columnName;
    }
    public function setColumns(array $definitions)
    {
        foreach ($definitions as $name => $options) {
            $this->setColumn($name, $options['type'], $options['length'], $options);
        }
    }
    /**
     * setColumn
     *
     * @param string $name
     * @param string $type
     * @param integer $length
     * @param mixed $options
     * @param boolean $prepend   Whether to prepend or append the new column to the column list.
     *                           By default the column gets appended.
     * @throws Doctrine_Table_Exception     if trying use wrongly typed parameter
     * @return void
     */
    public function setColumn($name, $type, $length = null, $options = array(), $prepend = false)
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
        
        // extract column name & field name
        $parts = explode(' as ', $name);
        if (count($parts) > 1) {
            $fieldName = $parts[1];
        } else {
            $fieldName = $parts[0];
        }
        $name = strtolower($parts[0]);
        if ($prepend) {
            $this->_columnNames = array_merge(array($fieldName => $name), $this->_columnNames);
            $this->_fieldNames = array_merge(array($name => $fieldName), $this->_fieldNames);
        } else {
            $this->_columnNames[$fieldName] = $name;
            $this->_fieldNames[$name] = $fieldName;
        }

        if ($length == null) {
            switch ($type) {
                case 'string':
                case 'clob':
                case 'float':
                case 'integer':
                case 'array':
                case 'object':
                case 'blob':
                case 'gzip':
                    // use php int max
                    $length = 2147483647;
                break;
                case 'boolean':
                    $length = 1;
                case 'date':
                    // YYYY-MM-DD ISO 8601
                    $length = 10;
                case 'time':
                    // HH:NN:SS+00:00 ISO 8601
                    $length = 14;
                case 'timestamp':
                    // YYYY-MM-DDTHH:MM:SS+00:00 ISO 8601
                    $length = 25;
                break;
            }
        }
        
        $options['type'] = $type;
        $options['length'] = $length;
        
        if ($prepend) {
            $this->_columns = array_merge(array($name => $options), $this->_columns);
        } else {
            $this->_columns[$name] = $options;
        }

        if (!empty($options['primary'])) {
            if (isset($this->_identifier)) {
                $this->_identifier = (array) $this->_identifier; 
            }
            if ( ! in_array($fieldName, $this->_identifier)) {
                $this->_identifier[] = $fieldName;
            }
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
     * @param string $fieldName
     * @return mixed
     */
    public function getDefaultValueOf($fieldName)
    {
        $columnName = $this->getColumnName($fieldName);
        if ( ! isset($this->_columns[$columnName])) {
            throw new Doctrine_Table_Exception("Couldn't get default value. Column ".$columnName." doesn't exist.");
        }
        if (isset($this->_columns[$columnName]['default'])) {
            return $this->_columns[$columnName]['default'];
        } else {
            return null;
        }
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->_identifier;
    }

    /**
     * @return integer
     */
    public function getIdentifierType()
    {
        return $this->_identifierType;
    }

    /**
     * hasColumn
     * @return boolean
     */
    public function hasColumn($columnName)
    {
        return isset($this->_columns[$columnName]);
    }

    /**
     * sets the connection for this class
     *
     * @params Doctrine_Connection      a connection object 
     * @return Doctrine_Table           this object
     */
    public function setConnection(Doctrine_Connection $conn)
    {
        $this->_conn = $conn;

        $this->setParent($this->_conn);
        
        return $this;
    }

    /**
     * returns the connection associated with this table (if any)
     *
     * @return Doctrine_Connection|null     the connection object
     */
    public function getConnection()
    {
        return $this->_conn;
    }

    /**
     * creates a new record
     *
     * @param $array             an array where keys are field names and
     *                           values representing field values
     * @return Doctrine_Record   the created record object
     */
    public function create(array $array = array()) 
    {
        $record = new $this->_options['name']($this, true);
        $record->fromArray($array);

        return $record;
    }

    /**
     * finds a record by its identifier
     *
     * @param $id                       database row id
     * @param int $hydrationMode        Doctrine::HYDRATE_ARRAY or Doctrine::HYDRATE_RECORD
     * @return mixed                    Array or Doctrine_Record or false if no result
     */
    public function find($id, $hydrationMode = null)
    {
        if (is_null($id)) {
            return false;
        }

        $id = is_array($id) ? array_values($id) : array($id);

        return $this->createQuery()
            ->where(implode(' = ? AND ', $this->getIdentifierColumnNames()) . ' = ?')
            ->fetchOne($id, $hydrationMode);
    }

    /**
     * findAll
     * returns a collection of records
     *
     * @param int $hydrationMode        Doctrine::FETCH_ARRAY or Doctrine::FETCH_RECORD
     * @return Doctrine_Collection
     */
    public function findAll($hydrationMode = null)
    {
        return $this->createQuery()->execute(array(), $hydrationMode);
    }

    /**
     * findByDql
     * finds records with given DQL where clause
     * returns a collection of records
     *
     * @param string $dql               DQL after WHERE clause
     * @param array $params             query parameters
     * @param int $hydrationMode        Doctrine::FETCH_ARRAY or Doctrine::FETCH_RECORD
     * @return Doctrine_Collection
     */
    public function findBySql($dql, array $params = array(), $hydrationMode = null)
    {
        return $this->createQuery()->where($dql)->execute($params, $hydrationMode);
    }

    public function findByDql($dql, array $params = array(), $hydrationMode = null)
    {
        return $this->findBySql($dql, $params, $hydrationMode);
    }

    /**
     * execute
     * fetches data using the provided queryKey and 
     * the associated query in the query registry
     *
     * if no query for given queryKey is being found a 
     * Doctrine_Query_Registry exception is being thrown
     *
     * @param string $queryKey      the query key
     * @param array $params         prepared statement params (if any)
     * @return mixed                the fetched data
     */
    public function execute($queryKey, $params = array(), $hydrationMode = Doctrine::HYDRATE_RECORD)
    {
        return Doctrine_Manager::getInstance()
                            ->getQueryRegistry()
                            ->get($queryKey, $this->getComponentName())
                            ->execute($params, $hydrationMode);
    }

    /**
     * executeOne
     * fetches data using the provided queryKey and 
     * the associated query in the query registry
     *
     * if no query for given queryKey is being found a 
     * Doctrine_Query_Registry exception is being thrown
     *
     * @param string $queryKey      the query key
     * @param array $params         prepared statement params (if any)
     * @return mixed                the fetched data
     */
    public function executeOne($queryKey, $params = array(), $hydrationMode = Doctrine::HYDRATE_RECORD)
    {
        return Doctrine_Manager::getInstance()
                            ->getQueryRegistry()
                            ->get($queryKey, $this->getComponentName())
                            ->fetchOne($params, $hydrationMode);
    }

    /**
     * clear
     * clears the first level cache (identityMap)
     *
     * @return void
     * @todo what about a more descriptive name? clearIdentityMap?
     */
    public function clear()
    {
        $this->_identityMap = array();
    }

    /**
     * addRecord
     * adds a record to identity map
     *
     * @param Doctrine_Record $record       record to be added
     * @return boolean
     * @todo Better name? registerRecord?
     */
    public function addRecord(Doctrine_Record $record)
    {
        $id = implode(' ', $record->identifier());

        if (isset($this->_identityMap[$id])) {
            return false;
        }

        $this->_identityMap[$id] = $record;

        return true;
    }

    /**
     * removeRecord
     * removes a record from the identity map, returning true if the record
     * was found and removed and false if the record wasn't found.
     *
     * @param Doctrine_Record $record       record to be removed
     * @return boolean
     */
    public function removeRecord(Doctrine_Record $record)
    {
        $id = implode(' ', $record->identifier());

        if (isset($this->_identityMap[$id])) {
            unset($this->_identityMap[$id]);
            return true;
        }

        return false;
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
        if ( ! empty($this->_data)) {

            $identifierFieldNames = $this->getIdentifier();

            if ( ! is_array($identifierFieldNames)) {
                $identifierFieldNames = array($identifierFieldNames);
            }

            $found = false;
            foreach ($identifierFieldNames as $fieldName) {
                if ( ! isset($this->_data[$fieldName])) {
                    // primary key column not found return new record
                    $found = true;
                    break;
                }
                $id[] = $this->_data[$fieldName];
            }

            if ($found) {
                $recordName = $this->getClassnameToReturn();
                $record = new $recordName($this, true);
                $this->_data = array();
                return $record;
            }


            $id = implode(' ', $id);

            if (isset($this->_identityMap[$id])) {
                $record = $this->_identityMap[$id];
                $record->hydrate($this->_data);
            } else {
                $recordName = $this->getClassnameToReturn();
                $record = new $recordName($this);
                $this->_identityMap[$id] = $record;
            }
            $this->_data = array();
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
        if ( ! isset($this->_options['subclasses'])) {
            return $this->_options['name'];
        }
        foreach ($this->_options['subclasses'] as $subclass) {
            $table = $this->_conn->getTable($subclass);
            $inheritanceMap = $table->getOption('inheritanceMap');
            $nomatch = false;
            foreach ($inheritanceMap as $key => $value) {
                if ( ! isset($this->_data[$key]) || $this->_data[$key] != $value) {
                    $nomatch = true;
                    break;
                }
            }
            if ( ! $nomatch) {
                return $table->getComponentName();
            }
        }
        return $this->_options['name'];
    }

    /**
     * @param $id                       database row id
     * @throws Doctrine_Find_Exception
     */
    final public function getProxy($id = null)
    {
        if ($id !== null) {
            $identifierColumnNames = $this->getIdentifierColumnNames();
            $query = 'SELECT ' . implode(', ', (array) $identifierColumnNames)
                . ' FROM ' . $this->getTableName()
                . ' WHERE ' . implode(' = ? && ', (array) $identifierColumnNames) . ' = ?';
            $query = $this->applyInheritance($query);

            $params = array_merge(array($id), array_values($this->_options['inheritanceMap']));

            $this->_data = $this->_conn->execute($query, $params)->fetch(PDO::FETCH_ASSOC);

            if ($this->_data === false)
                return false;
        }
        return $this->getRecord();
    }

    /**
     * applyInheritance
     * @param $where                    query where part to be modified
     * @return string                   query where part with column aggregation inheritance added
     */
    final public function applyInheritance($where)
    {
        if ( ! empty($this->_options['inheritanceMap'])) {
            $a = array();
            foreach ($this->_options['inheritanceMap'] as $field => $value) {
                $a[] = $this->getColumnName($field) . ' = ?';
            }
            $i = implode(' AND ', $a);
            $where .= ' AND ' . $i;
        }
        return $where;
    }

    /**
     * count
     *
     * @return integer
     */
    public function count()
    {
        $a = $this->_conn->execute('SELECT COUNT(1) FROM ' . $this->_options['tableName'])->fetch(Doctrine::FETCH_NUM);
        return current($a);
    }

    /**
     * @return Doctrine_Query  a Doctrine_Query object
     */
    public function getQueryObject()
    {
        $graph = new Doctrine_Query($this->getConnection());
        $graph->load($this->getComponentName());
        return $graph;
    }

    /**
     * @param string $fieldName
     * @return array
     */
    public function getEnumValues($fieldName)
    {
        $columnName = $this->getColumnName($fieldName);
        if (isset($this->_columns[$columnName]['values'])) {
            return $this->_columns[$columnName]['values'];
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
    public function enumValue($fieldName, $index)
    {
        if ($index instanceof Doctrine_Null) {
            return $index;
        }
        
        $columnName = $this->getColumnName($fieldName);
        if ( ! $this->_conn->getAttribute(Doctrine::ATTR_USE_NATIVE_ENUM)
            && isset($this->_columns[$columnName]['values'][$index])
        ) {
            return $this->_columns[$columnName]['values'][$index];
        }

        return $index;
    }

    /**
     * enumIndex
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    public function enumIndex($fieldName, $value)
    {
        $values = $this->getEnumValues($fieldName);

        $index = array_search($value, $values);
        if ($index === false || !$this->_conn->getAttribute(Doctrine::ATTR_USE_NATIVE_ENUM)) {
            return $index;
        }
        return $value;
    }
    
    /**
     * getColumnCount
     *
     * @return integer      the number of columns in this table
     */
    public function getColumnCount()
    {
        return $this->columnCount;
    }

    /**
     * returns all columns and their definitions
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->_columns;
    }

    /**
     * removeColumn
     * removes given column
     *
     * @return boolean
     */
    public function removeColumn($fieldName)
    {
    	$columnName = array_search($fieldName, $this->_fieldNames);

        unset($this->_fieldNames[$columnName]);

        if (isset($this->_columns[$columnName])) {
            unset($this->_columns[$columnName]);
            return true;
        }
        $this->columnCount--;
        
        return false;
    }

    /**
     * returns an array containing all the column names.
     *
     * @return array
     */
    public function getColumnNames(array $fieldNames = null)
    {
        if ($fieldNames === null) {
            return array_keys($this->_columns);
        } else {
           $columnNames = array();
           foreach ($fieldNames as $fieldName) {
               $columnNames[] = $this->getColumnName($fieldName);
           }
           return $columnNames;
        }
    }
    
    /**
     * returns an array with all the identifier column names.
     *
     * @return array
     */
    public function getIdentifierColumnNames()
    {
        return $this->getColumnNames((array) $this->getIdentifier());
    }
    
    /**
     * returns an array containing all the field names.
     *
     * @return array
     */
    public function getFieldNames()
    {
        return array_values($this->_fieldNames);
    }

    /**
     * getDefinitionOf
     *
     * @return mixed        array on success, false on failure
     */
    public function getDefinitionOf($fieldName)
    {
        $columnName = $this->getColumnName($fieldName);
        return $this->getColumnDefinition($columnName);
    }

    /**
     * getTypeOf
     *
     * @return mixed        string on success, false on failure
     */
    public function getTypeOf($fieldName)
    {
        return $this->getTypeOfColumn($this->getColumnName($fieldName));
    }
    
    /**
     * getTypeOfColumn
     *
     * @return mixed  The column type or FALSE if the type cant be determined.
     */
    public function getTypeOfColumn($columnName)
    {
        return isset($this->_columns[$columnName]) ? $this->_columns[$columnName]['type'] : false;
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
        $this->_data = $data;
    }

    /**
     * returns internal data, used by Doctrine_Record instances
     * when retrieving data from database
     *
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * prepareValue
     * this method performs special data preparation depending on
     * the type of the given column
     *
     * 1. It unserializes array and object typed columns
     * 2. Uncompresses gzip typed columns
     * 3. Gets the appropriate enum values for enum typed columns
     * 4. Initializes special null object pointer for null values (for fast column existence checking purposes)
     *
     * example:
     * <code type='php'>
     * $field = 'name';
     * $value = null;
     * $table->prepareValue($field, $value); // Doctrine_Null
     * </code>
     *
     * @throws Doctrine_Table_Exception     if unserialization of array/object typed column fails or
     * @throws Doctrine_Table_Exception     if uncompression of gzip typed column fails         *
     * @param string $field     the name of the field
     * @param string $value     field value
     * @return mixed            prepared value
     */
    public function prepareValue($fieldName, $value)
    {
        if ($value === self::$_null) {
            return self::$_null;
        } else if ($value === null) {
            return null;
        } else {
            $type = $this->getTypeOf($fieldName);

            switch ($type) {
                case 'integer':
                case 'string';
                    // don't do any casting here PHP INT_MAX is smaller than what the databases support
                break;
                case 'enum':
                    return $this->enumValue($fieldName, $value);
                break;
                case 'boolean':
                    return (boolean) $value;
                break;
                case 'array':
                case 'object':
                    if (is_string($value)) {
                        $value = unserialize($value);

                        if ($value === false) {
                            throw new Doctrine_Table_Exception('Unserialization of ' . $fieldName . ' failed.');
                        }
                        return $value;
                    }
                break;
                case 'gzip':
                    $value = gzuncompress($value);

                    if ($value === false) {
                        throw new Doctrine_Table_Exception('Uncompressing of ' . $fieldName . ' failed.');
                    }
                    return $value;
                break;
            }
        }
        return $value;
    }

    /**
     * getTree
     *
     * getter for associated tree
     *
     * @return mixed  if tree return instance of Doctrine_Tree, otherwise returns false
     */
    public function getTree()
    {
        if (isset($this->_options['treeImpl'])) {
            if ( ! $this->_tree) {
                $options = isset($this->_options['treeOptions']) ? $this->_options['treeOptions'] : array();
                $this->_tree = Doctrine_Tree::factory($this,
                    $this->_options['treeImpl'],
                    $options
                );
            }
            return $this->_tree;
        }
        return false;
    }

    /**
     * getComponentName
     *
     * @return void
     */
    public function getComponentName()
    {
        return $this->_options['name'];
    }

    /**
     * getTableName
     *
     * @return void
     */
    public function getTableName()
    {
        return $this->_options['tableName'];
    }

    /**
     * setTableName
     *
     * @param string $tableName 
     * @return void
     */
    public function setTableName($tableName)
    {
        $this->setOption('tableName', $this->_conn->formatter->getTableName($tableName));
    }

    /**
     * isTree
     *
     * determine if table acts as tree
     *
     * @return mixed  if tree return true, otherwise returns false
     */
    public function isTree()
    {
        return ( ! is_null($this->_options['treeImpl'])) ? true : false;
    }

    /**
     * getTemplate
     *
     * @param string $template 
     * @return void
     */
    public function getTemplate($template)
    {
        if ( ! isset($this->_templates[$template])) {
            throw new Doctrine_Table_Exception('Template ' . $template . ' not loaded');
        }

        return $this->_templates[$template];
    }
    
    public function hasTemplate($template)
    {
        return isset($this->_templates[$template]);
    }

    public function addTemplate($template, Doctrine_Template $impl)
    {
        $this->_templates[$template] = $impl;

        return $this;
    }
    public function getGenerators()
    {
        return $this->_generators;
    }
    public function getGenerator($generator)
    {
        if ( ! isset($this->_generators[$generator])) {
            throw new Doctrine_Table_Exception('Generator ' . $generator . ' not loaded');
        }

        return $this->_generators[$plugin];
    }
    
    public function hasGenerator($generator)
    {
        return isset($this->_generators[$generator]);
    }

    public function addGenerator(Doctrine_Record_Generator $generator, $name = null)
    {
    	if ($name === null) {
            $this->_generators[] = $generator;
        } else {
            $this->_generators[$name] = $generator;
        }
        return $this;
    }
    /**
     * bindQueryParts
     * binds query parts to given component
     *
     * @param array $queryParts         an array of pre-bound query parts
     * @return Doctrine_Record          this object
     */
    public function bindQueryParts(array $queryParts)
    {
    	$this->_options['queryParts'] = $queryParts;

        return $this;
    }

    /**
     * bindQueryPart
     * binds given value to given query part
     *
     * @param string $queryPart
     * @param mixed $value
     * @return Doctrine_Record          this object
     */
    public function bindQueryPart($queryPart, $value)
    {
    	$this->_options['queryParts'][$queryPart] = $value;

        return $this;
    }
    
    /**
     * getBoundQueryPart
     *
     * @param string $queryPart 
     * @return string $queryPart
     */
    public function getBoundQueryPart($queryPart)
    {
        if ( ! isset($this->_options['queryParts'][$queryPart])) {
            return null;
        }

        return $this->_options['queryParts'][$queryPart];
    }
    
    /**
     * unshiftFilter
     *
     * @param  object Doctrine_Record_Filter $filter
     * @return object $this
     */
    public function unshiftFilter(Doctrine_Record_Filter $filter)
    {
        $filter->setTable($this);

        $filter->init();

        array_unshift($this->_filters, $filter);

        return $this;
    }
    
    /**
     * getFilters
     *
     * @return array $filters
     */
    public function getFilters()
    {
        return $this->_filters;
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
    
    /**
     * findBy
     *
     * @param string $column 
     * @param string $value 
     * @param string $hydrationMode 
     * @return void
     */
    protected function findBy($fieldName, $value, $hydrationMode = null)
    {
        return $this->createQuery()->where($fieldName . ' = ?')->execute(array($value), $hydrationMode);
    }
    
    /**
     * findOneBy
     *
     * @param string $column 
     * @param string $value 
     * @param string $hydrationMode 
     * @return void
     */
    protected function findOneBy($fieldName, $value, $hydrationMode = null)
    {
        $results = $this->createQuery()->where($fieldName . ' = ?')->limit(1)->execute(array($value), $hydrationMode);
        
        return $hydrationMode === Doctrine::FETCH_ARRAY ? $results[0] : $results->getFirst();
    }
    
    /**
     * __call
     *
     * Adds support for magic finders.
     * findByColumnName, findByRelationAlias
     * findById, findByContactId, etc.
     *
     * @return void
     */
    public function __call($method, $arguments)
    {
        if (substr($method, 0, 6) == 'findBy') {
            $by = substr($method, 6, strlen($method));
            $method = 'findBy';
        } else if (substr($method, 0, 9) == 'findOneBy') {
            $by = substr($method, 9, strlen($method));
            $method = 'findOneBy';
        }
        
        if (isset($by)) {
            if ( ! isset($arguments[0])) {
                throw new Doctrine_Table_Exception('You must specify the value to findBy');
            }
            
            $fieldName = Doctrine::tableize($by);
            $hydrationMode = isset($arguments[1]) ? $arguments[1]:null;
            
            if ($this->hasColumn($fieldName)) {
                return $this->$method($fieldName, $arguments[0], $hydrationMode);
            } else if ($this->hasRelation($by)) {
                $relation = $this->getRelation($by);
                
                if ($relation['type'] === Doctrine_Relation::MANY) {
                    throw new Doctrine_Table_Exception('Cannot findBy many relationship.');
                }
                
                return $this->$method($relation['local'], $arguments[0], $hydrationMode);
            } else {
                throw new Doctrine_Table_Exception('Cannot find by: ' . $by . '. Invalid column or relationship alias.');
            }
        }
    }
}
