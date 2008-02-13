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
 * A ClassMetadata instance holds all the information (metadata) of an entity and it's relations.
 * These informations are used for the proper object-relational mapping of the class.
 *
 * @package Doctrine
 * @author  Roman Borschel <roman@code-factory.org>
 * @since   1.0
 */
class Doctrine_ClassMetadata extends Doctrine_Configurable implements Serializable
{    
    /**
     * The name of the domain class that is mapped to the database with this metadata.
     * 
     * @var string
     */
    protected $_entityName;
    
    /**
     * The name of the custom mapper class used for the entity class.
     *
     * @var string
     */
    protected $_customMapperClassName;
    
    /**
     *
     * @var Doctrine_Connection
     */
    protected $_conn;
    
    /**
     * The names of the parent classes.
     */
    protected $_parentClasses = array();
    
    /**
     * The field names of all fields that are part of the identifier/primary key
     * of the described entity class.
     *
     * @var array
     */
    protected $_identifier = array();
    
    /**
     * The identifier type of the class.
     *
     * @see Doctrine_Identifier constants
     * @var integer
     */
    protected $_identifierType;
    
    /**
     * The inheritance mapping type used by the class.
     *
     * @var integer
     */
    protected $_inheritanceType = Doctrine::INHERITANCETYPE_TABLE_PER_CLASS;
    
    /**
     * The name of the column that acts as a discriminator to identify the type of an
     * object. Used in Single Table Inheritance and Class Table Inheritance.
     *
     * @var string
     */
    protected $_discriminatorColumn;
    
    /**
     * The discriminator map contains the mapping of discriminator values (keys)
     * to class names (values).
     *
     * @var array
     */
    protected $_discriminatorMap;
    
    /**
     * An array containing all templates attached to the class.
     *
     * @see Doctrine_Template
     * @var array $_templates
     * @todo Unify under 'Behaviors'.                 
     */
    protected $_templates = array();
    
    /**
     * An array containing all generators attached to this class.
     *
     * @see Doctrine_Record_Generator
     * @var array $_generators
     * @todo Unify under 'Behaviors'.
     */
    protected $_generators = array();

    /**
     * An array containing all filters attached to the class.
     *
     * @see Doctrine_Record_Filter
     * @var array $_filters                     
     */
    protected $_filters = array();
    
    /**
     * An array of column definitions,
     * keys are column names and values are column definitions
     *
     * the definition array has atleast the following values:
     *
     *  -- type         the column type, eg. 'integer'
     *  -- length       the column length, eg. 11
     *
     *  additional keys:
     *  -- notnull      whether or not the column is marked as notnull
     *  -- values       enum values
     *  -- notblank     notblank validator + notnull constraint
     *  ... many more
     *
     * @var array $columns
     */
    protected $_columns = array();

    /**
     * An array of field names. used to look up field names from column names.
     * Keys are column names and values are field names.
     * This is the reverse lookup map of $_columnNames.
     *
     * @var array
     */
    protected $_fieldNames = array();
    
    /**
     * An array of column names. Keys are field names and values column names.
     * Used to look up column names from field names.
     * This is the reverse lookup map of $_fieldNames.
     * 
     * @var array           
     */
    protected $_columnNames = array();
    
    /**
     * @todo Implementation.
     */
    protected $_readOnlyFieldNames = array();
    
    /**
     * Tree object associated with the class.
     *
     * @var Doctrine_Tree                
     */
    protected $_tree;
    
    /**
     * Cached column count, Doctrine_Record uses this column count when
     * determining its state.
     *
     * @var integer
     */
    protected $_columnCount;
    
    /**
     * Whether or not this class has default values.
     *
     * @var boolean     
     */
    protected $_hasDefaultValues;
    
    /**
     * Relation parser object. Manages the relations for the class.
     *
     * @var Doctrine_Relation_Parser $_parser   
     */
    protected $_parser;
    
    /**
     * Enum value arrays. 
     */
    protected $_enumMap = array();
    
    /**
     * @var array $options                  an array containing all options
     *
     *      -- parents                      the parent classes of this component                          
     *
     *      -- treeImpl                     the tree implementation of this table (if any)
     *
     *      -- treeOptions                  the tree options
     *
     *      -- queryParts                   the bound query parts
     */
    protected $_options      = array(
            'treeImpl'       => null,
            'treeOptions'    => null,
            'queryParts'     => array(),
            'subclasses'     => array(),
            'parents'        => array()
            );
    
    /**
     * Inheritance options.
     */
    protected $_inheritanceOptions = array(
            'discriminatorColumn' => null,
            'discriminatorMap'    => array(),
            'joinSubclasses'      => true
            );
    
    /**
     * Specific options that can be set for the database table the class is mapped to.
     * Some of them are dbms specific and they are only used if the table is generated
     * by Doctrine (NOT when using Migrations).
     * 
     *      -- type                         table type (mysql example: INNODB)
     *
     *      -- charset                      character set
     *
     *      -- checks                       the check constraints of this table, eg. 'price > dicounted_price'
     *
     *      -- collation                    collation attribute
     *
     *      -- indexes                      the index definitions of this table
     *
     *      -- sequenceName                 Some databases need sequences instead of auto incrementation primary keys,
     *                                      you can set specific sequence for your table by calling setOption('sequenceName', $seqName)
     *                                      where $seqName is the name of the desired sequence
     */
    protected $_tableOptions = array(
            'tableName'      => null,
            'sequenceName'   => null,
            'type'           => null,
            'charset'        => null,
            'collation'      => null,
            'collate'        => null,
            'indexes'        => array(),
            'checks'         => array()
            );     
    
    /**
     * Constructs a new metadata instance.
     *
     * @param string $domainClassName  Name of the class the metadata instance is used for.
     */
    public function __construct($entityName, Doctrine_Connection $conn)
    {        
        $this->_entityName = $entityName;
        $this->_conn = $conn;
        $this->_parser = new Doctrine_Relation_Parser($this);
        $this->_filters[]  = new Doctrine_Record_Filter_Standard();
        $this->setParent($this->_conn); 
    }
    
    /**
     *
     */
    public function getConnection()
    {
        return $this->_conn;
    }
    
    /**
     * getComponentName
     *
     * @return void
     */
    public function getClassName()
    {
        return $this->_entityName;
    }
    
    /**
     * @deprecated
     */
    public function getComponentName()
    {
        return $this->getClassName();
    }
    
    /**
     * Checks whether a field is part of the identifier/primary key field(s).
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
     * addIndex
     *
     * adds an index to this table
     *
     * @return void
     */
    public function addIndex($index, array $definition)
    {
        $this->_tableOptions['indexes'][$index] = $definition;
    }

    /**
     * getIndex
     *
     * @return array|boolean        array on success, FALSE on failure
     */
    public function getIndex($index)
    {
        if (isset($this->_tableOptions['indexes'][$index])) {
            return $this->_tableOptions['indexes'][$index];
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
            case 'tableName':
            case 'index':
            case 'sequenceName':
            case 'type':
            case 'charset':
            case 'collation':
            case 'collate':
                return $this->setTableOption($name, $value);
            case 'enumMap':
                $this->_enumMap = $value;
                return;
        }
        $this->_options[$name] = $value;
    }
    
    /**
     * Sets a table option.
     */
    public function setTableOption($name, $value)
    {
        if ( ! array_key_exists($name, $this->_tableOptions)) {
            throw new Doctrine_ClassMetadata_Exception("Unknown table option: '$name'.");
        }
        $this->_tableOptions[$name] = $value;        
    }
    
    /**
     * Gets a table option.
     */
    public function getTableOption($name)
    {
        if ( ! array_key_exists($name, $this->_tableOptions)) {
            throw new Doctrine_ClassMetadata_Exception("Unknown table option: '$name'.");
        }
        
        return $this->_tableOptions[$name];
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
        } else if (isset($this->_tableOptions[$name])) {
            return $this->_tableOptions[$name];
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
     * getTableOptions
     * returns all table options.
     *
     * @return array    all options and their values
     */
    public function getTableOptions()
    {
        return $this->_tableOptions;
    }
    
    /**
     * getColumnName
     *
     * returns a column name for a field name.
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
     * @deprecated
     */
    public function mapField($name, $type, $length = null, $options = array(), $prepend = false)
    {
        return $this->setColumn($name, $type, $length, $options, $prepend);
    }
    
    /**
     * addMappedColumn
     *
     * @param string $name
     * @param string $type
     * @param integer $length
     * @param mixed $options
     * @param boolean $prepend   Whether to prepend or append the new column to the column list.
     *                           By default the column gets appended.
     * @throws Doctrine_ClassMetadata_Exception If trying use wrongly typed parameter.
     * @deprecated
     */
    public function addMappedColumn($name, $type, $length = null, $options = array(), $prepend = false)
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
        
        if (isset($this->_columnNames[$fieldName])) {
            return;
        }
        
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
            $this->_hasDefaultValues = true;
        }
        
        $this->_columnCount++;
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
     * @deprecated
     */
    public function setColumn($name, $type, $length = null, $options = array(), $prepend = false)
    {
        return $this->addMappedColumn($name, $type, $length, $options, $prepend);
    }
    
    /**
     * hasDefaultValues
     * returns true if this table has default values, otherwise false
     *
     * @return boolean
     */
    public function hasDefaultValues()
    {
        return $this->_hasDefaultValues;
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
        return $this->_columnCount;
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
        $this->_columnCount--;
        
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
        return $this->getTableOption('tableName');
    }
    
    /**
     *
     */
    public function getFieldMapping($fieldName)
    {
        return $this->getDefinitionOf($fieldName);
    }
    
    /**
     * 
     */
    public function getFields()
    {
        return $this->_columns;
    }
    
    public function getInheritedFields()
    {
        
    }
    
    public function getAllFields()
    {
        
    }
    
    /**
     *
     * @param string $name  The name under which the query gets registered.
     * @param string $query The DQL query.
     * @todo Implementation.
     */
    public function addNamedQuery($name, $query)
    {
        
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
        if ( ! is_array($args[1])) {
            try {
                throw new Exception();
            } catch (Exception $e) {
                echo $e->getTraceAsString();
            }
        }
        $options = array_merge($args[1], $options);
        $this->_parser->bind($args[0], $options);
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
    
    /**
     * Gets the inheritance type used by the class.
     *
     * @return integer
     */
    public function getInheritanceType()
    {
        return $this->_inheritanceType;
    }
    
    /**
     * Sets the subclasses of the class.
     * All entity classes that participate in a hierarchy and have subclasses
     * need to declare them in this way.
     *
     * @param array $subclasses  The names of all subclasses.
     */
    public function setSubclasses(array $subclasses)
    {
        $this->_options['subclasses'] = $subclasses;     
    }
    
    /**
     * Gets the names of all subclasses.
     *
     * @return array  The names of all subclasses.
     */
    public function getSubclasses()
    {
        return $this->getOption('subclasses');
    }
    
    /**
     * Checks whether the class has any persistent subclasses.
     *
     * @return boolean TRUE if the class has one or more persistent subclasses, FALSE otherwise.
     */
    public function hasSubclasses()
    {
        return ! $this->getOption('subclasses');
    }
    
    /**
     * Gets the names of all parent classes.
     */
    public function getParentClasses()
    {
        return $this->getOption('parents');
    }
    
    /**
     * Sets the parent class names.
     */
    public function setParentClasses(array $classNames)
    {
        $this->_options['parents'] = $classNames;
    }
    
    /**
     * Checks whether the class has any persistence parent classes.
     *
     * @return boolean TRUE if the class has one or more persistent parent classes, FALSE otherwise.
     */
    public function hasParentClasses()
    {
        return ! $this->getOption('parents');
    }
    
    /**
     * Sets the inheritance type used by the class and it's subclasses.
     *
     * @param integer $type
     */
    public function setInheritanceType($type, array $options = array())
    {
        if ($type == Doctrine::INHERITANCETYPE_SINGLE_TABLE) {
            $this->_checkRequiredDiscriminatorOptions($options);
        } else if ($type == Doctrine::INHERITANCETYPE_JOINED) {
            $this->_checkRequiredDiscriminatorOptions($options);
        } else if ($type == Doctrine::INHERITANCETYPE_TABLE_PER_CLASS) {
            // concrete table inheritance ...
        } else {
            throw new Doctrine_ClassMetadata_Exception("Invalid inheritance type '$type'.");
        }
        $this->_inheritanceType = $type;
        foreach ($options as $name => $value) {
            $this->setInheritanceOption($name, $value);
        }
    }
    
    private function _checkRequiredDiscriminatorOptions(array $options)
    {
        if ( ! isset($options['discriminatorColumn'])) {
            throw new Doctrine_ClassMetadata_Exception("Missing option 'discriminatorColumn'."
                    . " Inheritance types JOINED and SINGLE_TABLE require this option.");
        } else if ( ! isset($options['discriminatorMap'])) {
            throw new Doctrine_ClassMetadata_Exception("Missing option 'discriminatorMap'."
                    . " Inheritance types JOINED and SINGLE_TABLE require this option.");
        }
    }
    
    public function getInheritanceOption($name)
    {
        if ( ! array_key_exists($name, $this->_inheritanceOptions)) {
            echo $name;
            throw new Doctrine_ClassMetadata_Exception("Unknown inheritance option: '$name'.");
        }
        
        return $this->_inheritanceOptions[$name];
    }
    
    public function getInheritanceOptions()
    {
        return $this->_inheritanceOptions;
    }
    
    public function setInheritanceOption($name, $value)
    {
        if ( ! array_key_exists($name, $this->_inheritanceOptions)) {
            throw new Doctrine_ClassMetadata_Exception("Unknown inheritance option: '$name'.");
        }
        
        switch ($name) {
            case 'discriminatorColumn':
                if ( $value !== null && ! is_string($value)) {
                    throw new Doctrine_ClassMetadata_Exception("Invalid value '$value' for option"
                            . " 'discriminatorColumn'.");
                }
                break;
            case 'discriminatorMap':
                if ( ! is_array($value)) {
                    throw new Doctrine_ClassMetadata_Exception("Value for option 'discriminatorMap'"
                            . " must be an array.");
                }
                break;
        }
        
        $this->_inheritanceOptions[$name] = $value;
    }
    
    /**
     * export
     * exports this class to the database based on its mapping.
     *
     * @throws Doctrine_Connection_Exception    If some error other than Doctrine::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation.
     * @return boolean                          Whether or not the export operation was successful
     *                                          false if table already existed in the database.
     */
    public function export()
    {
        $this->_conn->export->exportTable($this);
    }

    /**
     * getExportableFormat
     * Returns an array with all the information needed to create the main database table
     * for the class.
     *
     * @return array
     * @todo Move somewhere else ... somehow this seems wrong here. Exporting is a separate task.
     */
    public function getExportableFormat($parseForeignKeys = true)
    {
        $columns = array();
        $primary = array();
        $allColumns = $this->getColumns();
        
        // If the class is part of a Single Table Inheritance hierarchy, collect the fields
        // of all classes in the hierarchy.
        if ($this->_inheritanceType == Doctrine::INHERITANCETYPE_SINGLE_TABLE) {
            $parents = $this->getOption('parents');
            if ($parents) {
                $rootClass = $this->_conn->getClassMetadata(array_pop($parents));
            } else {
                $rootClass = $this;
            }
            $subClasses = $rootClass->getOption('subclasses');
            foreach ($subClasses as $subClass) {
                $subClassMetadata = $this->_conn->getClassMetadata($subClass);
                $allColumns = array_merge($allColumns, $subClassMetadata->getColumns());
            }
        } else if ($this->_inheritanceType == Doctrine::INHERITANCETYPE_JOINED) {
            // Remove inherited, non-pk fields. They're not in the table of this class
            foreach ($allColumns as $name => $definition) {
                if (isset($definition['primary']) && $definition['primary'] === true) {
                    if ($this->getParentClasses() && isset($definition['autoincrement'])) {
                        unset($allColumns[$name]['autoincrement']);
                    }
                    continue;
                }
                if (isset($definition['inherited']) && $definition['inherited'] === true) {
                    unset($allColumns[$name]);
                }
            }
        } else if ($this->_inheritanceType == Doctrine::INHERITANCETYPE_TABLE_PER_CLASS) {
            // If this is a subclass, just remove existing autoincrement options on the pk
            if ($this->getParentClasses()) {
                foreach ($allColumns as $name => $definition) {
                    if (isset($definition['primary']) && $definition['primary'] === true) {
                        if (isset($definition['autoincrement'])) {
                            unset($allColumns[$name]['autoincrement']);
                        }
                    }
                }
            }
        }

        // Convert enum & boolean default values
        foreach ($allColumns as $name => $definition) {
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
        
        // Collect foreign keys from the relations
        $options['foreignKeys'] = array();
        if ($parseForeignKeys && $this->getAttribute(Doctrine::ATTR_EXPORT)
                & Doctrine::EXPORT_CONSTRAINTS) {
            $constraints = array();
            $emptyIntegrity = array('onUpdate' => null, 'onDelete' => null);
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
        
        return array('tableName' => $this->getTableOption('tableName'),
                     'columns'   => $columns,
                     'options'   => array_merge($options, $this->getTableOptions()));
    }
    
    /**
     * getTemplate
     *
     * @param string $template 
     * @return void
     * @todo Unify under 'Behaviors'.
     */
    public function getTemplate($template)
    {
        if ( ! isset($this->_templates[$template])) {
            throw new Doctrine_Table_Exception('Template ' . $template . ' not loaded');
        }

        return $this->_templates[$template];
    }
    
    /**
     * @todo Unify under 'Behaviors'.
     */
    public function hasTemplate($template)
    {
        return isset($this->_templates[$template]);
    }
    
    /**
     * @todo Unify under 'Behaviors'.
     */
    public function addTemplate($template, Doctrine_Template $impl)
    {
        $this->_templates[$template] = $impl;

        return $this;
    }
    
    /**
     * @todo Unify under 'Behaviors'.
     */
    public function getGenerators()
    {
        return $this->_generators;
    }
    
    /**
     * @todo Unify under 'Behaviors'.
     */
    public function getGenerator($generator)
    {
        if ( ! isset($this->_generators[$generator])) {
            throw new Doctrine_Table_Exception('Generator ' . $generator . ' not loaded');
        }

        return $this->_generators[$plugin];
    }
    
    /**
     * @todo Unify under 'Behaviors'.
     */
    public function hasGenerator($generator)
    {
        return isset($this->_generators[$generator]);
    }
    
    /**
     * @todo Unify under 'Behaviors'.
     */
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
     * loadTemplate
     *
     * @param string $template
     * @todo Unify under 'Behaviors'.
     */
    public function loadTemplate($template, array $options = array())
    {
        $this->actAs($template, $options);
    }
    
    /**
     * @todo Unify under 'Behaviors'.
     */
    public function loadGenerator(Doctrine_Record_Generator $generator)
    {
    	$generator->initialize($this->_table);
        $this->addGenerator($generator, get_class($generator));
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
        array_unshift($this->_filters, $filter);
        
        return $this;
    }
    
    /**
     * getTree
     *
     * getter for associated tree
     *
     * @return mixed  if tree return instance of Doctrine_Tree, otherwise returns false
     * @todo Belongs to the NestedSet Behavior.
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
     * @todo Belongs to the NestedSet Behavior.
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
     * Checks whether a persistent field is inherited from a superclass.
     *
     * @return boolean TRUE if the field is inherited, FALSE otherwise.
     */
    public function isInheritedField($fieldName)
    {
        return isset($this->_columns[$this->getColumnName($fieldName)]['inherited']);
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
     * Sets the name of the primary table the class is mapped to.
     *
     * @param string $tableName  The table name.
     */
    public function setTableName($tableName)
    {
        $this->setTableOption('tableName', $this->_conn->formatter->getTableName($tableName));
    }
    
    /**
     * Serializes the metadata class.
     *
     * Part of the implementation of the Serializable interface.
     *
     * @return string  The serialized metadata class.
     */
    public function serialize()
    {
        return serialize($this->_columns);
    }
    
    /**
     * Reconstructs the metadata class from it's serialized representation.
     *
     * Part of the implementation of the Serializable interface.
     *
     * @param string $serialized  The serialized metadata class.
     */
    public function unserialize($serialized)
    {
        return true;
    }
    
    /**
     * @todo Implementation.
     */
    public function oneToOne($targetEntity, $definition)
    {
                
    }
    
    /**
     * @todo Implementation.
     */
    public function oneToMany($targetEntity, $definition)
    {
        
    }
    
    /**
     * @todo Implementation.
     */
    public function manyToOne($targetEntity, $definition)
    {
                
    }
    
    /**
     * @todo Implementation.
     */
    public function manyToMany($targetEntity, $definition)
    {
                
    }

    /**
     * actAs
     * loads the given plugin
     *
     * @param mixed $tpl
     * @param array $options
     * @todo Unify under 'Behaviors'.
     */
    public function actAs($tpl, array $options = array())
    {
        if ( ! is_object($tpl)) {
            if (class_exists($tpl, true)) {
                $tpl = new $tpl($options);
            } else {
                $className = 'Doctrine_Template_' . $tpl;
                if ( ! class_exists($className, true)) {
                    throw new Doctrine_Record_Exception("Couldn't load plugin.");
                }
                $tpl = new $className($options);
            }
        }

        if ( ! ($tpl instanceof Doctrine_Template)) {
            throw new Doctrine_Record_Exception('Loaded plugin class is not an instance of Doctrine_Template.');
        }

        $className = get_class($tpl);

        $this->addTemplate($className, $tpl);

        $tpl->setTable($this);
        $tpl->setUp();
        $tpl->setTableDefinition();

        return $this;
    }
    
    /**
     * check
     * adds a check constraint
     *
     * @param mixed $constraint     either a SQL constraint portion or an array of CHECK constraints
     * @param string $name          optional constraint name
     * @return Doctrine_Record      this object
     * @todo Should be done through $_tableOptions
     */
    public function check($constraint, $name = null)
    {
        if (is_array($constraint)) {
            foreach ($constraint as $name => $def) {
                $this->_addCheckConstraint($def, $name);
            }
        } else {
            $this->_addCheckConstraint($constraint, $name);
        }
        
        return $this;
    }
    
    protected function _addCheckConstraint($definition, $name)
    {
        if (is_string($name)) {
            $this->_tableOptions['checks'][$name] = $definition;
        } else {
            $this->_tableOptions['checks'][] = $definition;
        }
    }
    
    /**
     * Mixes a predefined behaviour into the class.
     * 
     * @param string|object  The name of the behavior or the behavior object.
     * @todo Implementation.
     */
    public function addBehavior($behavior)
    {
        // ...
    }
    
    /**
     * Registers a custom mapper for the entity class.
     *
     * @param string $mapperClassName  The class name of the custom mapper.
     */
    public function setCustomMapperClass($mapperClassName)
    {
        if ( ! is_subclass_of($mapperClassName, 'Doctrine_Mapper')) {
            throw new Doctrine_ClassMetadata_Exception("The custom mapper must be a subclass"
                    . " of Doctrine_Mapper.");
        }
        $this->_customMapperClassName = $mapperClassName;
    }
    
    /**
     * Gets the name of the custom mapper class used for the entity class.
     *
     * @return string|null  The name of the custom mapper class or NULL if the entity
     *                      class does not have a custom mapper class.
     */
    public function getCustomMapperClass()
    {
        return $this->_customMapperClassName;
    }
    
    /**
     * @todo Thoughts & Implementation.
     */
    public function setType($type)
    {
        //Doctrine::CLASSTYPE_ENTITY
        //Doctrine::CLASSTYPE_MAPPED_SUPERCLASS
        //Doctrine::CLASSTYPE_TRANSIENT
    }
    
    /**
     * @todo Implementation. Immutable entities can not be updated or deleted once
     *       they are created. This means the entity can only be modified as long as it's
     *       in transient state (TCLEAN, TDIRTY).
     */
    public function isImmutable()
    {
        return false;
    }
    
    /**
     * hasOne
     * binds One-to-One aggregate relation
     *
     * @param string $componentName     the name of the related component
     * @param string $options           relation options
     * @see Doctrine_Relation::_$definition
     * @return Doctrine_Record          this object
     */
    public function hasOne()
    {
        $this->bind(func_get_args(), Doctrine_Relation::ONE_AGGREGATE);

        return $this;
    }

    /**
     * hasMany
     * binds One-to-Many / Many-to-Many aggregate relation
     *
     * @param string $componentName     the name of the related component
     * @param string $options           relation options
     * @see Doctrine_Relation::_$definition
     * @return Doctrine_Record          this object
     */
    public function hasMany()
    {
        $this->bind(func_get_args(), Doctrine_Relation::MANY_AGGREGATE);

        return $this;
    }
    
    /**
     *
     */
    public function __toString()
    {
        return spl_object_hash($this);
    }
}

