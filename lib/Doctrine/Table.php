<?php 

/**
 * A table object holds all the information (meta data) of a database table and it's relations
 * These informations are needed for the proper object-relational mapping of the domain classes.
 *
 * @package Doctrine
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Doctrine_Table extends Doctrine_Configurable implements Serializable
{
    /**
     * The name of the domain class that is mapped to the database table with this metadata.
     * Note: In Single Table Inheritance this will be the name of the root class of the
     * hierarchy (the one that gets the database table).
     */
    protected $_domainClassName;
    
    protected $_conn;
    
    /**
     * @var mixed $identifier   The field names of all fields that are part of the identifier/primary key
     *                          of the described component.
     */
    protected $_identifier = array();
    
    /**
     * The identifier type of the component.
     *
     * @see Doctrine_Identifier constants
     * @var integer $identifierType
     */
    protected $_identifierType;
    
    /**
     *
     */
    protected $_inheritanceType = Doctrine::INHERITANCETYPE_TABLE_PER_CLASS;
    
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
    protected $_columns = array();

    /**
     * @var array $_fieldNames            an array of field names. used to look up field names
     *                                    from column names.
     *                                    keys are column names and values are field names
     */
    protected $_fieldNames = array();
    
    /**
     * 
     * @var array $_columnNames             an array of column names
     *                                      keys are field names and values column names.
     *                                      used to look up column names from field names.
     *                                      this is the reverse lookup map of $_fieldNames.
     */
    protected $_columnNames = array();
    
    /**
     * @var Doctrine_Tree $tree                 tree object associated with this table
     */
    protected $_tree;
    
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
     * @var Doctrine_Relation_Parser $_parser   relation parser object
     */
    protected $_parser;
    
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
     *      -- inheritanceMap               contains the mapping of the discriminator column (which discriminator value identifies
     *                                      which class). Used in Single & Class Table Inheritance.
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
    protected $_options      = array(
            'tableName'      => null,
            'sequenceName'   => null,
            'inheritanceType' => null,
            'inheritanceMap' => array(),
            'enumMap'        => array(),
            'type'           => null,
            'charset'        => null,
            'collation'      => null,
            'collate'        => null,
            'treeImpl'       => null,
            'treeOptions'    => null,
            'subclasses'     => null,
            'queryParts'     => array(),
            'indexes'        => array(),
            'parents'        => array(),
            'joinedParents'  => array()
            );
    
    /**
     * Constructs a new table object.
     */
    public function __construct($domainClassName, Doctrine_Connection $conn)
    {        
        $this->_domainClassName = $domainClassName;
        $this->_conn = $conn;
        $this->_parser = new Doctrine_Relation_Parser($this);
        $this->_filters[]  = new Doctrine_Record_Filter_Standard();
        $this->setParent($this->_conn); 
    }
    
    public function getConnection()
    {
        return $this->_conn;
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
     * getComponentName
     *
     * @return void
     */
    public function getComponentName()
    {
        //return $this->_options['name'];
        return $this->_domainClassName;
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

        if ( ! empty($options['primary'])) {
            if (isset($this->_identifier)) {
                $this->_identifier = $this->_identifier; 
            }
            if ( ! in_array($fieldName, (array) $this->_identifier)) {
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
    
    public function setIdentifier($identifier)
    {
        $this->_identifier = $identifier;
    }

    /**
     * @return integer
     */
    public function getIdentifierType()
    {
        return $this->_identifierType;
    }
    
    public function setIdentifierType($type)
    {
        $this->_identifierType = $type;
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
     * hasField
     * @return boolean
     */
    public function hasField($fieldName)
    {
        return isset($this->_columnNames[$fieldName]);
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
        if ( ! $this->_conn->getAttribute(Doctrine::ATTR_USE_NATIVE_ENUM) &&
                isset($this->_columns[$columnName]['values'][$index])) {
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
        if ($index === false || ! $this->_conn->getAttribute(Doctrine::ATTR_USE_NATIVE_ENUM)) {
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
    
    public function setColumnCount($count)
    {
        $this->columnCount = $count;
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
     * getTableName
     *
     * @return void
     */
    public function getTableName()
    {
        return $this->_options['tableName'];
    }
    
    public function bindRelation($args, $type)
    {
        return $this->bind($args, $type);
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

            $classes = array_merge($this->getOption('parents'), array($this->getComponentName()));


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
    
    public function getRelationParser()
    {
        return $this->_parser;
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
     * getTemplates
     * returns all templates attached to this table
     *
     * @return array     an array containing all templates
     */
    public function getTemplates()
    {
        return $this->_templates;
    }

    public function getInheritanceType()
    {
        return $this->_inheritanceType;
    }
    
    public function setInheritanceType($type)
    {
        $this->_inheritanceType = $type;
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
     * Returns an array with the DDL for this table object.
     *
     * @return array
     * @todo move to Table
     */
    public function getExportableFormat($parseForeignKeys = true)
    {
        $columns = array();
        $primary = array();

        foreach ($this->getColumns() as $name => $definition) {
            switch ($definition['type']) {
                case 'enum':
                    if (isset($definition['default'])) {
                        $definition['default'] = $this->enumIndex($name, $definition['default']);
                    }
                    break;
                case 'boolean':
                    if (isset($definition['default'])) {
                        $definition['default'] = $this->_conn->convertBooleans($definition['default']);
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
     * getTree
     *
     * getter for associated tree
     *
     * @return mixed  if tree return instance of Doctrine_Tree, otherwise returns false
     */
    public function getTree()
    {
        if ($this->getOption('treeImpl')) {
            if ( ! $this->_tree) {
                $options = $this->getOption('treeOptions') ? $this->getOption('treeOptions') : array();
                $this->_tree = Doctrine_Tree::factory($this,
                        $this->getOption('treeImpl'), $options);
            }
            return $this->_tree;
        }
        return false;
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
        return ( ! is_null($this->getOption('treeImpl'))) ? true : false;
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
     * setTableName
     *
     * @param string $tableName 
     * @return void
     */
    public function setTableName($tableName)
    {
        $this->setOption('tableName', $this->_conn->formatter->getTableName($tableName));
    }
    
    public function serialize()
    {
        return serialize($this->_columns);
    }
    
    public function unserialize($serialized)
    {
        return true;
    }
    
    public function __toString()
    {
        return spl_object_hash($this);
    }
}

