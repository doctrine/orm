<?php 

/**
 * A table factory is used to create table objects and load them with meta data.
 *
 * @todo Support different drivers for loading the metadata from different sources.
 * @package Doctrine
 */
class Doctrine_ClassMetadata_Factory
{
    protected $_conn;
    protected $_driver;
    
    /**
     * 
     */
    protected $_loadedMetadata = array();
    
    public function __construct(Doctrine_Connection $conn, $driver)
    {
        $this->_conn = $conn;
        $this->_driver = $driver;
    }
    
    /**
     * Returns the metadata object for a class.
     *
     * @param string $className  The name of the class.
     * @return Doctrine_Metadata
     */
    public function getMetadataFor($className)
    {
        if (isset($this->_loadedMetadata[$className])) {
            return $this->_loadedMetadata[$className];
        }
        $this->_loadClasses($className, $this->_loadedMetadata);
        
        return $this->_loadedMetadata[$className];
    }
    
    /**
     * Loads the metadata of the class in question and all it's ancestors whose metadata
     * is still not loaded.
     *
     * @param string $name   The name of the class for which the metadata should get loaded.
     * @param array  $tables The metadata collection to which the loaded metadata is added.
     */
    protected function _loadClasses($name, array &$classes)
    {
        $parentClass = $name;
        $parentClasses = array();
        $loadedParentClass = false;
        while ($parentClass = get_parent_class($parentClass)) {
            if ($parentClass == 'Doctrine_Record') {
                break;
            }
            if (isset($classes[$parentClass])) {
                $loadedParentClass = $parentClass;
                break;
            }
            $parentClasses[] = $parentClass;
        }
        $parentClasses = array_reverse($parentClasses);
        $parentClasses[] = $name;
        
        if ($loadedParentClass) {
            $class = $classes[$loadedParentClass];
        } else {
            $rootClassOfHierarchy = count($parentClasses) > 0 ? array_shift($parentClasses) : $name;
            $class = new Doctrine_ClassMetadata($rootClassOfHierarchy, $this->_conn);
            $this->_loadMetadata($class, $rootClassOfHierarchy);
            $classes[$rootClassOfHierarchy] = $class;
        }
        
        if (count($parentClasses) == 0) {
            return $class;
        }
        
        // load metadata of subclasses
        // -> child1 -> child2 -> $name
        
        $parent = $class;
        foreach ($parentClasses as $subclassName) {
            $subClass = new Doctrine_ClassMetadata($subclassName, $this->_conn);
            $subClass->setInheritanceType($parent->getInheritanceType(), $parent->getInheritanceOptions());
            $this->_addInheritedFields($subClass, $parent);
            $this->_addInheritedRelations($subClass, $parent);
            $this->_loadMetadata($subClass, $subclassName);
            if ($parent->getInheritanceType() == Doctrine::INHERITANCETYPE_SINGLE_TABLE) {
                //echo "<br />". $subClass->getClassName() . $parent->getTableName() . "<br />";
                $subClass->setTableName($parent->getTableName());
            }
            $classes[$subclassName] = $subClass;
            $parent = $subClass;
        }
    }
    
    protected function _addInheritedFields($subClass, $parentClass)
    {
        foreach ($parentClass->getColumns() as $name => $definition) {
            /*if (isset($definition['autoincrement']) && $definition['autoincrement'] === true) {
                unset($definition['autoincrement']);
            }*/
            $fullName = "$name as " . $parentClass->getFieldName($name);
            $definition['inherited'] = true;
            $subClass->setColumn($fullName, $definition['type'], $definition['length'],
                    $definition);
        }
    }
    
    protected function _addInheritedRelations($subClass, $parentClass) {
        foreach ($parentClass->getRelationParser()->getRelations() as $name => $relation) {
            $subClass->getRelationParser()->addRelation($name, $relation);
        }
    }
    
    /**
     * Current code driver.
     */
    protected function _loadMetadata(Doctrine_ClassMetadata $class, $name)
    {
        if ( ! class_exists($name) || empty($name)) {
            /*try {
                throw new Exception();
            } catch (Exception $e) {
                echo $e->getTraceAsString();
            }*/
            throw new Doctrine_Exception("Couldn't find class " . $name . ".");
        }

        $names = array();
        $className = $name;
        // get parent classes
        do {
            if ($className === 'Doctrine_Record') {
                break;
            } else if ($className == $name) {
                continue;
            }
            $names[] = $className;
        } while ($className = get_parent_class($className));

        if ($className === false) {
            throw new Doctrine_ClassMetadata_Factory_Exception("Unknown component '$className'.");
        }

        // save parents
        $class->setOption('parents', $names);

        // load further metadata
        $this->_driver->loadMetadataForClass($name, $class);
        
        $tableName = $class->getTableName();
        if ( ! isset($tableName)) {
            $class->setTableName(Doctrine::tableize($class->getClassName()));
        }
        
        $this->_initIdentifier($class);
        
        return $class;
    }
    
    /**
     * Code driver.
     *
     * @todo Move to code driver.
     */
    /*protected function _loadMetadataFromCode($class, $name)
    {
        call_user_func_array(array($name, 'initMetadata'), array($class));
    }*/
    
    /**
     * Initializes the class identifier(s)/primary key(s).
     *
     * @param Doctrine_Metadata  The metadata container of the class in question.
     */
    protected function _initIdentifier(Doctrine_ClassMetadata $class)
    {
        switch (count($class->getIdentifier())) {
            case 0:
                if ($class->getInheritanceType() == Doctrine::INHERITANCETYPE_JOINED &&
                        count($class->getOption('parents')) > 0) {
                            
                    $parents = $class->getOption('parents');
                    $root = end($parents);
                    $rootClass = $class->getConnection()->getMetadata($root);
                    $class->setIdentifier($rootClass->getIdentifier());
                    
                    if ($class->getIdentifierType() !== Doctrine::IDENTIFIER_AUTOINC) {
                        $class->setIdentifierType($rootClass->getIdentifierType());
                    } else {
                        $class->setIdentifierType(Doctrine::IDENTIFIER_NATURAL);
                    }

                    // add all inherited primary keys
                    foreach ((array) $class->getIdentifier() as $id) {
                        $definition = $rootClass->getDefinitionOf($id);

                        // inherited primary keys shouldn't contain autoinc
                        // and sequence definitions
                        unset($definition['autoincrement']);
                        unset($definition['sequence']);

                        // add the inherited primary key column
                        $fullName = $rootClass->getColumnName($id) . ' as ' . $id;
                        $class->setColumn($fullName, $definition['type'], $definition['length'],
                                $definition, true);
                    }
                } else {
                    $definition = array('type' => 'integer',
                                        'length' => 20,
                                        'autoincrement' => true,
                                        'primary' => true);
                    $class->setColumn('id', $definition['type'], $definition['length'], $definition, true);
                    $class->setIdentifier('id');
                    $class->setIdentifierType(Doctrine::IDENTIFIER_AUTOINC);
                }
                break;
            case 1:
                foreach ($class->getIdentifier() as $pk) {
                    $columnName = $class->getColumnName($pk);
                    $thisColumns = $class->getColumns();
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
                                $class->setIdentifierType(Doctrine::IDENTIFIER_AUTOINC);
                                $found = true;
                                break;
                            case 'seq':
                            case 'sequence':
                                $class->setIdentifierType(Doctrine::IDENTIFIER_SEQUENCE);
                                $found = true;

                                if ($value) {
                                    $class->setOption('sequenceName', $value);
                                } else {
                                    if (($sequence = $class->getAttribute(Doctrine::ATTR_DEFAULT_SEQUENCE)) !== null) {
                                        $class->setOption('sequenceName', $sequence);
                                    } else {
                                        $class->setOption('sequenceName', $class->getConnection()
                                                ->getSequenceName($class->getOption('tableName')));
                                    }
                                }
                                break;
                        }
                    }
                    $identifierType = $class->getIdentifierType();
                    if ( ! isset($identifierType)) {
                        $class->setIdentifierType(Doctrine::IDENTIFIER_NATURAL);
                    }
                }

                $class->setIdentifier($pk);

                break;
            default:
                $class->setIdentifierType(Doctrine::IDENTIFIER_COMPOSITE);
        }
    }
    
}



