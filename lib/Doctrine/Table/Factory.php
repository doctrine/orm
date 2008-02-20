<?php 

/**
 * A table factory is used to create table objects and load them with meta data.
 *
 * @todo Support different drivers for loading the meta data from different sources.
 * @package Doctrine
 * @deprecated
 */
class Doctrine_Table_Factory
{
    protected $_conn;
    protected $_driver;
    
    public function __construct(Doctrine_Connection $conn /*Doctrine_Table_Factory_Driver $driver*/)
    {
        $this->_conn = $conn;
        //$this->_driver = $driver;
        $name = "Doctrine_Table_Factory";
        //call_user_func_array(array($name, 'foobar'), array());
    }
    
    /**
     * Loads the metadata of the class in question and all it's ancestors whose metadata
     * is still not loaded.
     *
     * @param string $name   The name of the class for which the metadata should get loaded.
     * @param array  $tables The metadata collection to which the loaded metadata is added.
     */
    public function loadTables($name, array &$tables)
    {        
        $parentClass = $name;
        $parentClasses = array();
        $parentClassWithTable = false;
        while ($parentClass = get_parent_class($parentClass)) {
            if ($parentClass == 'Doctrine_Record') {
                break;
            }
            if (isset($tables[$parentClass])) {
                $parentClassWithTable = $parentClass;
                break;
            }
            $class = new ReflectionClass($parentClass);
            if ($class->isAbstract()) {
                continue;
            }
            $parentClasses[] = $parentClass;
        }
        $parentClasses = array_reverse($parentClasses);
        $parentClasses[] = $name;
        
        if ($parentClassWithTable) {
            $table = $tables[$parentClassWithTable];
        } else {
            $rootClassOfHierarchy = count($parentClasses) > 0 ? array_shift($parentClasses) : $name;
            $table = new Doctrine_Table($rootClassOfHierarchy, $this->_conn);
            $this->_loadMetaDataFromCode($table, $rootClassOfHierarchy);
            $tables[$rootClassOfHierarchy] = $table;
        }
        
        if (count($parentClasses) == 0) {
            return $table;
        }
        //var_dump($parentClasses);
        //echo "<br /><br />";
        
        // load meta data of subclasses
        if ($table->getInheritanceType() == Doctrine::INHERITANCETYPE_JOINED) {
            foreach ($parentClasses as $subclass) {
                $subTable = new Doctrine_Table($subclass, $this->_conn);
                $subTable->setInheritanceType(Doctrine::INHERITANCETYPE_JOINED);
                $this->_loadMetaDataFromCode($subTable, $subclass);
                $tables[$subclass] = $subTable;
            }
        } else if ($table->getInheritanceType() == Doctrine::INHERITANCETYPE_SINGLE_TABLE) {
            foreach ($parentClasses as $subclass) {
                $this->_mergeInto($table, $subclass);
                $tables[$subclass] = $table;
            }
        } else if ($table->getInheritanceType() == Doctrine::INHERITANCETYPE_TABLE_PER_CLASS) {
            $parents = array();
            foreach ($parentClasses as $subclass) {
                $class = new ReflectionClass($subclass);
                if ($class->isAbstract()) {
                    $parents[] = $subclass;
                    continue;
                }
                $subTable = new Doctrine_Table($subclass, $this->_conn);
                $subTable->setInheritanceType(Doctrine::INHERITANCETYPE_TABLE_PER_CLASS);
                $this->_loadMetaDataFromCode($subTable, $subclass);               
                $this->_mergeColumnsInto($table, $subTable, true);
                foreach ($parents as $parent) {
                    $this->_mergeColumnsInto($this->_conn->getTable($parent), $subTable, true);
                }
                // currently relying on parent::setTableDefinition();
                /*foreach ($abstracts as $abstractParent) {
                    Doctrine_Table_Factory::mergeInto($subTable, $abstractParent);
                }*/
                $tables[$subclass] = $subTable;
                $parents[] = $subclass;
            }
        } else {
            throw new Doctrine_Table_Factory_Exception("Failed to load meta data. Unknown inheritance type "
                    . "or no inheritance type specified for hierarchy.");
        }
    }
    
    /**
     * Initializes the in-memory metadata for the domain class this mapper belongs to.
     * Uses reflection and code setup.
     */
    protected function _loadMetaDataFromCode(Doctrine_Table $table, $name)
    {
        if ($name == 'Doctrine_Locator_Injectable') {
            try {
                throw new Exception();
            } catch (Exception $e) {
                echo $e->getTraceAsString() . "<br /><br />";
            }
        }
        
        if ( ! class_exists($name) || empty($name)) {
            //try {
            throw new Doctrine_Exception("Couldn't find class " . $name);
            //} catch (Exception $e) {
            //    echo $e->getTraceAsString() . "<br /><br />";
            //}
        }
        $record = new $name($table);

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
        $table->setOption('parents', $names);

        /*echo "<br />";
        var_dump($names);
        echo "<br /><br />";*/

        // set up metadata mapping
        if (method_exists($record, 'setTableDefinition')) {
            $record->setTableDefinition();
            // get the declaring class of setTableDefinition method
            $method = new ReflectionMethod($name, 'setTableDefinition');
            $class = $method->getDeclaringClass();
        } else {
            $class = new ReflectionClass($class);
        }
        
        if ($table->getInheritanceType() == Doctrine::INHERITANCETYPE_JOINED) {
            $joinedParents = array();
            foreach (array_reverse($names) as $parent) {
                $parentTable = $table->getConnection()->getTable($parent);
                $parentColumns = $parentTable->getColumns();
                $thisColumns = $table->getColumns();
                
                foreach ($parentColumns as $columnName => $definition) {
                    if ( ! isset($definition['primary'])) {
                        if (isset($thisColumns[$columnName])) {
                            continue;
                        } else {
                            /*if ( ! isset($parentColumns[$columnName]['owner'])) {
                                $parentColumns[$columnName]['owner'] = $parentTable->getComponentName();
                            }
                            $joinedParents[] = $parentColumns[$columnName]['owner'];*/
                            $joinedParents[] = $parentTable->getComponentName();
                        }
                    }/* else {
                        //echo "adding primary key $columnName on ".$table->getComponentName().".<br />";
                        unset($definition['autoincrement']);
                        $fullName = $columnName . ' as ' . $parentTable->getFieldName($columnName);
                        $table->setColumn($fullName, $definition['type'], $definition['length'], $definition, true);
                    }*/
                }
            }
            $table->setOption('joinedParents', array_values(array_unique($joinedParents)));
        }

        $table->setOption('declaringClass', $class);

        // set the table definition for the given tree implementation
        /*if ($table->isTree()) {
            $table->getTree()->setTableDefinition();
        }*/
        
        $tableName = $table->getOption('tableName');
        if ( ! isset($tableName)) {
            $table->setOption('tableName', Doctrine::tableize($class->getName()));
        }
        
        $this->_initIdentifier($table);
        
        // set up domain class relations
        $record->setUp();
        
        // if tree, set up tree relations
        /*if ($table->isTree()) {
            $table->getTree()->setUp();
        }*/
        
        return $table;
    }
    
    protected function _mergeInto(Doctrine_Table $table, $domainClassName)
    {
        if ( ! class_exists($domainClassName) || empty($domainClassName)) {
            throw new Doctrine_Exception("Couldn't find class " . $domainClassName);
        }
        
        $record = new $domainClassName($table);
        $record->setTableDefinition();
        $record->setUp();
        
    }
    
    protected function _mergeColumnsInto(Doctrine_Table $sourceTable, Doctrine_Table $targetTable, $skipPk = false)
    {
        
        $sourceColumns = $sourceTable->getColumns();
        foreach ($sourceColumns as $columnName => $definition) {
            if ($skipPk && isset($definition['primary'])) {
                continue;
            }
            $fullName = $columnName . ' as ' . $sourceTable->getFieldName($columnName);
            $targetTable->setColumn($fullName, $definition['type'], $definition['length'], $definition);
        }
        
    }
    
    protected function _mergeRelationsInto(Doctrine_Table $table, $domainClassName)
    {
        
        
        
    }
    
    
    /**
     * Initializes the table identifier(s)/primary key(s)
     *
     */
    protected function _initIdentifier(Doctrine_Table $table)
    {
        switch (count($table->getIdentifier())) {
            case 0:
                if ($table->getInheritanceType() == Doctrine::INHERITANCETYPE_JOINED &&
                        count($table->getOption('joinedParents')) > 0) {
                            
                    $root = current($table->getOption('joinedParents'));
                    
                    $rootTable = $table->getConnection()->getTable($root);
                
                    $table->setIdentifier($rootTable->getIdentifier());
                    
                    if ($table->getIdentifierType() !== Doctrine::IDENTIFIER_AUTOINC) {
                        $table->setIdentifierType($rootTable->getIdentifierType());
                    } else {
                        $table->setIdentifierType(Doctrine::IDENTIFIER_NATURAL);
                    }

                    // add all inherited primary keys
                    foreach ((array) $table->getIdentifier() as $id) {
                        $definition = $rootTable->getDefinitionOf($id);

                        // inherited primary keys shouldn't contain autoinc
                        // and sequence definitions
                        unset($definition['autoincrement']);
                        unset($definition['sequence']);

                        // add the inherited primary key column
                        $fullName = $rootTable->getColumnName($id) . ' as ' . $id;
                        $table->setColumn($fullName, $definition['type'], $definition['length'],
                                $definition, true);
                    }
                } else {
                    $definition = array('type' => 'integer',
                                        'length' => 20,
                                        'autoincrement' => true,
                                        'primary' => true);
                    $table->setColumn('id', $definition['type'], $definition['length'], $definition, true);
                    $table->setIdentifier('id');
                    $table->setIdentifierType(Doctrine::IDENTIFIER_AUTOINC);
                }
                break;
            case 1:
                foreach ($table->getIdentifier() as $pk) {
                    $columnName = $table->getColumnName($pk);
                    $thisColumns = $table->getColumns();
                    $e = $thisColumns[$columnName];

                    $found = false;

                    foreach ($e as $option => $value) {
                        if ($found) {
                            break;
                        }

                        $e2 = explode(':', $option);

                        switch (strtolower($e2[0])) {
                            case 'autoincrement':
                            case 'autoinc':
                                $table->setIdentifierType(Doctrine::IDENTIFIER_AUTOINC);
                                $found = true;
                                break;
                            case 'seq':
                            case 'sequence':
                                $table->setIdentifierType(Doctrine::IDENTIFIER_SEQUENCE);
                                $found = true;

                                if ($value) {
                                    $table->setOption('sequenceName', $value);
                                } else {
                                    if (($sequence = $table->getAttribute(Doctrine::ATTR_DEFAULT_SEQUENCE)) !== null) {
                                        $table->setOption('sequenceName', $sequence);
                                    } else {
                                        $table->setOption('sequenceName', $table->getConnection()
                                                ->getSequenceName($this->getOption('tableName')));
                                    }
                                }
                                break;
                        }
                    }
                    $identifierType = $table->getIdentifierType();
                    if ( ! isset($identifierType)) {
                        $table->setIdentifierType(Doctrine::IDENTIFIER_NATURAL);
                    }
                }

                $table->setIdentifier($pk);

                break;
            default:
                $table->setIdentifierType(Doctrine::IDENTIFIER_COMPOSITE);
        }
    }
    
    public static function foobar()
    {
        echo "bar!";
    }
    
}

