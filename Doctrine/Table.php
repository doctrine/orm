<?php
require_once("Exception/Find.class.php");
require_once("Exception/Mapping.class.php");
require_once("Exception/PrimaryKey.class.php");
require_once("Configurable.php");
/**
 * Doctrine_Table   represents a database table
 *                  each Doctrine_Table holds the information of foreignKeys and associations
 * 
 *
 * @author      Konsta Vesterinen
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 * @version     1.0 alpha
 */
class Doctrine_Table extends Doctrine_Configurable {
    /**
     * @var boolean $isNewEntry                         whether ot not this table created a new record or not, used only internally
     */
    private $isNewEntry       = false;
    /**
     * @var array $data                                 temporary data which is then loaded into Doctrine_Record::$data
     */
    private $data             = array();
    /**
     * @var array $relations                            an array containing all the Doctrine_Relation objects for this table
     */
    private $relations        = array();
    /**
     * @var array $primaryKeys                          an array containing all primary key column names
     */
    private $primaryKeys      = array();
    /**
     * @var mixed $identifier
     */
    private $identifier;
    /**
     * @var integer $identifierType
     */
    private $identifierType;
    /**
     * @var string $query                               cached simple query
     */
    private $query;
    /**
     * @var Doctrine_Session $session                   Doctrine_Session object that created this table
     */
    private $session;
    /**
     * @var string $name                                name of the component, for example component name of the GroupTable is 'Group'
     */
    private $name;
    /**
     * @var string $tableName                           database table name, in most cases this is the same as component name but in some cases
     *                                                  where one-table-multi-class inheritance is used this will be the name of the inherited table
     */
    private $tableName;
    /**
     * @var string $sequenceName                        Some databases need sequences instead of auto incrementation primary keys, you can set specific
     *                                                  sequence for your table by calling setSequenceName()
     */
    private $sequenceName;
    /**
     * @var array $identityMap                          first level cache
     */
    private $identityMap        = array();
    /**
     * @var Doctrine_Repository $repository             record repository
     */
    private $repository;
    
    /**
     * @var Doctrine_Cache $cache                       second level cache
     */
    private $cache;
    /**
     * @var array $columns                              an array of column definitions
     */
    private $columns;
    /**
     * @var array $bound                                bound relations
     */
    private $bound              = array();
    /**
     * @var array $boundAliases                         bound relation aliases
     */
    private $boundAliases       = array();
    /**
     * @var integer $columnCount                        cached column count
     */
    private $columnCount;


    /**
     * @var array $inheritanceMap                       inheritanceMap is used for inheritance mapping, keys representing columns and values
     *                                                  the column values that should correspond to child classes
     */
    private $inheritanceMap     = array();
    /**
     * @var array $parents                              the parent classes of this component
     */
    private $parents            = array();

    /**
     * the constructor
     * @throws Doctrine_ManagerException        if there are no opened sessions
     * @throws Doctrine_TableException          if there is already an instance of this table
     * @return void
     */
    public function __construct($name) {
        $this->session = Doctrine_Manager::getInstance()->getCurrentSession();

        $this->setParent($this->session);

        $this->name = $name;

        if( ! class_exists($name) || empty($name))
            throw new Doctrine_Exception("Couldn't find class $name");

        $record = new $name($this);


        $names = array();

        $class = $name;
        


        // get parent classes

        do {
            if($class == "Doctrine_Record") break;

           	$name  = $class;
            $names[] = $name;
        } while($class = get_parent_class($class));

        // reverse names
        $names = array_reverse($names);


        // create database table
        if(method_exists($record,"setTableDefinition")) {
            $record->setTableDefinition();

            $this->columnCount = count($this->columns);

            if(isset($this->columns)) {
                $method    = new ReflectionMethod($this->name,"setTableDefinition");
                $class     = $method->getDeclaringClass();

                if( ! isset($this->tableName))
                    $this->tableName = strtolower($class->getName());

                switch(count($this->primaryKeys)):
                    case 0:
                        $this->columns = array_merge(array("id" => array("integer",11,"autoincrement|primary")), $this->columns);
                        $this->primaryKeys[] = "id";
                        $this->identifier = "id";
                        $this->identifierType = Doctrine_Identifier::AUTO_INCREMENT;
                        $this->columnCount++;
                    break;
                    default:
                        if(count($this->primaryKeys) > 1) {
                            $this->identifier = $this->primaryKeys;
                            $this->identifierType = Doctrine_Identifier::COMPOSITE;
                                                         	
                        } else {
                            foreach($this->primaryKeys as $pk) {
                                $o = $this->columns[$pk][2];
                                $e = explode("|",$o);
                                $found = false;
    

                                foreach($e as $option) {
                                    if($found)
                                        break;
    
                                    $e2 = explode(":",$option);
    
                                    switch(strtolower($e2[0])):
                                        case "unique":
                                            $this->identifierType = Doctrine_Identifier::UNIQUE;
                                            $found = true;
                                        break;
                                        case "autoincrement":
                                            $this->identifierType = Doctrine_Identifier::AUTO_INCREMENT;
                                            $found = true;
                                        break;
                                        case "seq":
                                            $this->identifierType = Doctrine_Identifier::SEQUENCE;
                                            $found = true;
                                        break;
                                    endswitch;
                                }
                                if( ! isset($this->identifierType))
                                    $this->identifierType = Doctrine_Identifier::NORMAL;
                                     
                                $this->identifier = $pk;
                            }
                        }
                endswitch;

                if($this->getAttribute(Doctrine::ATTR_CREATE_TABLES)) {
                    $dict      = new Doctrine_DataDict($this->getSession()->getDBH());
                    $dict->createTable($this->tableName, $this->columns);
                }

            }
        } else {
            throw new Doctrine_Exception("Class '$name' has no table definition.");
        }
        
        $record->setUp();

        // save parents
        array_pop($names);
        $this->parents   = $names;

        $this->query     = "SELECT ".implode(", ",array_keys($this->columns))." FROM ".$this->getTableName();

        // check if an instance of this table is already initialized
        if( ! $this->session->addTable($this))
            throw new Doctrine_Table_Exception();

        $this->initComponents();
    }
    /**
     * initializes components this table uses
     *
     * @return void
     */
    final public function initComponents() {
        $this->repository = new Doctrine_Repository($this);
        switch($this->getAttribute(Doctrine::ATTR_CACHE)):
            case Doctrine::CACHE_SQLITE:
                $this->cache       = new Doctrine_Cache_Sqlite($this);
            break;
            case Doctrine::CACHE_NONE:
                $this->cache       = new Doctrine_Cache($this);
            break;
        endswitch;
    }
    /**
     * @return Doctrine_Repository
     */
    public function getRepository() {
        return $this->repository;
    }
    /**
     * setColumn
     * @param string $name
     * @param string $type
     * @param integer $length
     * @param mixed $options
     * @return void
     */
    final public function setColumn($name, $type, $length, $options = "") {
        $this->columns[$name] = array($type,$length,$options);
        
        $e = explode("|",$options);
        if(in_array("primary",$e)) {
            $this->primaryKeys[] = $name;
        }
    }
    /**
     * @return mixed
     */
    final public function getIdentifier() {
        return $this->identifier;
    }
    /**
     * @return integer
     */
    final public function getIdentifierType() {
        return $this->identifierType;
    }
    /**
     * hasColumn
     * @return boolean
     */
    final public function hasColumn($name) {
        return isset($this->columns[$name]);
    }
    /**
     * @param mixed $key
     * @return void
     */
    final public function setPrimaryKey($key) {
        switch(gettype($key)):
            case "array":
                $this->primaryKeys = array_values($key);
            break;
            case "string":
                $this->primaryKeys[] = $key;
            break;
        endswitch;
    }
    /**
     * returns all primary keys
     * @return array
     */
    final public function getPrimaryKeys() {
        return $this->primaryKeys;
    }
    /**
     * @return boolean
     */
    final public function hasPrimaryKey($key) {
        return in_array($key,$this->primaryKeys);
    }
    /**
     * @param $sequence
     * @return void
     */
    final public function setSequenceName($sequence) {
        $this->sequenceName = $sequence;
    }
    /**
     * @return string   sequence name
     */
    final public function getSequenceName() {
        return $this->sequenceName;
    }
    /**
     * setInheritanceMap
     * @param array $inheritanceMap
     * @return void
     */
    final public function setInheritanceMap(array $inheritanceMap) {
        $this->inheritanceMap = $inheritanceMap;
    }
    /**
     * @return array        inheritance map (array keys as fields)
     */
    final public function getInheritanceMap() {
        return $this->inheritanceMap;
    }
    /**
     * return all composite paths in the form [component1].[component2]. . .[componentN]
     * @return array
     */
    final public function getCompositePaths() {
        $array = array();
        $name  = $this->getComponentName();
        foreach($this->bound as $k=>$a) {
            try {
            $fk = $this->getForeignKey($k);
            switch($fk->getType()):
                case Doctrine_Relation::ONE_COMPOSITE:
                case Doctrine_Relation::MANY_COMPOSITE:
                    $n = $fk->getTable()->getComponentName();
                    $array[] = $name.".".$n;
                    $e = $fk->getTable()->getCompositePaths();
                    if( ! empty($e)) {
                        foreach($e as $name) {
                            $array[] = $name.".".$n.".".$name;
                        }
                    }
                break;
            endswitch;
            } catch(InvalidKeyException $e) {
                                            	
            }
        }
        return $array;
    }
    /**
     * returns all bound relations
     *
     * @return array
     */
    final public function getBounds() {
        return $this->bound;
    }
    /**
     * returns a bound relation array
     *
     * @param string $name
     * @return array
     */
    final public function getBound($name) {
        if( ! isset($this->bound[$name])) 
            throw new InvalidKeyException();

        return $this->bound[$name];
    }
    /**
     * returns a bound relation array
     *
     * @param string $name
     * @return array
     */
    final public function getBoundForName($name) {
        foreach($this->bound as $k => $bound) {
            if($bound[3] == $name) {
                return $this->bound[$k];
            }
        }
        throw new InvalidKeyException();
    }
    /**
     * returns the alias for given component name
     *
     * @param string $name
     * @return string
     */
    final public function getAlias($name) {
        if(isset($this->boundAliases[$name]))
            return $this->boundAliases[$name];
            
        return $name;
    }
    /**
     * returns component name for given alias
     * 
     * @param string $alias
     * @return string
     */
    final public function getAliasName($alias) {
        if($name = array_search($this->boundAliases,$alias))
            return $name;
            
        throw new InvalidKeyException();
    }
    /**
     * unbinds all relations
     * 
     * @return void
     */
    final public function unbindAll() {
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
    final public function unbind() {
        if( ! isset($this->bound[$name]))
            return false;
        
        unset($this->bound[$name]);

        if(isset($this->relations[$name]))
            unset($this->relations[$name]);

        if(isset($this->boundAliases[$name]))
            unset($this->boundAliases[$name]);

        return true;
    }
    /**
     * binds a relation
     *
     * @param string $name
     * @param string $field
     * @return void
     */
    final public function bind($name,$field,$type,$localKey) {
        if(isset($this->relations[$name]))
            throw new InvalidKeyException();

        $e          = explode(" as ",$name);
        $name       = $e[0];

        if(isset($e[1])) {
            $alias = $e[1];
            $this->boundAliases[$name] = $alias;
        } else
            $alias = $name;


        $this->bound[$alias] = array($field,$type,$localKey,$name);
    }
    /**
     * getComponentName
     * @return string                   the component name
     */
    final public function getComponentName() {
        return $this->name;
    }
    /**
     * @return Doctrine_Session
     */
    final public function getSession() {
        return $this->session;
    }
    /**
     * @return Doctrine_Cache
     */
    final public function getCache() {
        return $this->cache;
    }
    /**
     * @param string $name              component name of which a foreign key object is bound
     * @return Doctrine_Relation
     */
    final public function getForeignKey($name) {
        if(isset($this->relations[$name]))
            return $this->relations[$name];

        if(isset($this->bound[$name])) {
            $type       = $this->bound[$name][1];
            $local      = $this->bound[$name][2];
            list($component, $foreign) = explode(".",$this->bound[$name][0]);
            $alias      = $name;
            $name       = $this->bound[$alias][3];

            $table      = $this->session->getTable($name);

            if($component == $this->name || in_array($component, $this->parents)) {

                // ONE-TO-ONE
                if($type == Doctrine_Relation::ONE_COMPOSITE ||
                   $type == Doctrine_Relation::ONE_AGGREGATE) {
                    if( ! isset($local))
                        $local = $table->getIdentifier();

                    $relation = new Doctrine_LocalKey($table,$foreign,$local,$type);
                } else
                    throw new Doctrine_Mapping_Exception("Only one-to-one relations are possible when local reference key is used.");

            } elseif($component == $name || ($component == $alias && $name == $this->name)) {
                if( ! isset($local))
                    $local = $this->identifier;

                // ONE-TO-MANY or ONE-TO-ONE
                $relation = new Doctrine_ForeignKey($table,$local,$foreign,$type);

            } else {
                // MANY-TO-MANY
                // only aggregate relations allowed

                if($type != Doctrine_Relation::MANY_AGGREGATE) 
                    throw new Doctrine_Mapping_Exception("Only aggregate relations are allowed for many-to-many relations");

                $classes = array_merge($this->parents, array($this->name));

                foreach(array_reverse($classes) as $class) {
                    try {
                        $bound = $table->getBoundForName($class);
                        break;
                    } catch(InvalidKeyException $exc) { }

                }
                if( ! isset($local))
                    $local = $this->identifier;

                $e2    = explode(".",$bound[0]);
                $fields = explode("-",$e2[1]);

                if($e2[0] != $component)
                    throw new Doctrine_Mapping_Exception($e2[0]." doesn't match ".$component);

                $associationTable = $this->session->getTable($e2[0]);

                if(count($fields) > 1) {
                    // SELF-REFERENCING THROUGH JOIN TABLE
                    $this->relations[$e2[0]] = new Doctrine_ForeignKey($associationTable,$local,$fields[0],Doctrine_Relation::MANY_COMPOSITE);
                    
                    $relation = new Doctrine_Association($table,$associationTable,$fields[0],$fields[1],$type);
                } else {
                    // NORMAL MANY-TO-MANY RELATIONSHIP
                    $this->relations[$e2[0]] = new Doctrine_ForeignKey($associationTable,$local,$e2[1],Doctrine_Relation::MANY_COMPOSITE);

                    $relation = new Doctrine_Association($table,$associationTable,$e2[1],$foreign,$type);
                }

            }
            $this->relations[$alias] = $relation;
            return $this->relations[$alias];
        }
        throw new InvalidKeyException();
    }
    /**
     * returns an array containing all foreign key objects
     *
     * @return array
     */
    final public function getForeignKeys() {
        $a = array();
        foreach($this->bound as $k=>$v) {
            $this->getForeignKey($k);
        }

        return $this->relations;
    }
    /**
     * sets the database table name
     *
     * @param string $name              database table name
     * @return void
     */
    final public function setTableName($name) {
        $this->tableName = $name;
    }

    /**
     * returns the database table name
     *
     * @return string
     */
    final public function getTableName() {
        return $this->tableName;
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
        $this->isNewEntry   = true;
        $record = new $this->name($this);
        $this->isNewEntry   = false;
        $this->data         = array();
        return $record;
    }
    /**
     * finds a record by its identifier
     *
     * @param $id                       database row id
     * @throws Doctrine_Find_Exception
     * @return Doctrine_Record          a record for given database identifier
     */
    public function find($id) {
        if($id !== null) {
            if( ! is_array($id))
                $id = array($id);
            else 
                $id = array_values($id);

            $query  = $this->query." WHERE ".implode(" = ? AND ",$this->primaryKeys)." = ?";
            $query  = $this->applyInheritance($query);


            $params = array_merge($id, array_values($this->inheritanceMap));

            $stmt  = $this->session->execute($query,$params);

            $this->data = $stmt->fetch(PDO::FETCH_ASSOC);

            if($this->data === false)
                return false;
        }
        return $this->getRecord();
    }
    /**
     * applyInheritance
     * @param $where                    query where part to be modified
     * @return string                   query where part with column aggregation inheritance added
     */
    final public function applyInheritance($where) {
        if( ! empty($this->inheritanceMap)) {
            $a = array();
            foreach($this->inheritanceMap as $field => $value) {
                $a[] = $field." = ?";
            }
            $i = implode(" AND ",$a);
            $where .= " AND $i";
        }
        return $where;
    }
    /**
     * findAll
     * returns a collection of records
     *
     * @return Doctrine_Collection
     */
    public function findAll() {
        $graph = new Doctrine_Query($this->session);
        $users = $graph->query("FROM ".$this->name);
        return $users;
    }
    /**
     * findBySql
     * finds records with given sql where clause
     * returns a collection of records
     *
     * @param string $sql               SQL after WHERE clause
     * @param array $params             query parameters
     * @return Doctrine_Collection
     */
    public function findBySql($sql, array $params = array()) {
        $q = new Doctrine_Query($this->session);
        $users = $q->query("FROM ".$this->name." WHERE ".$sql, $params);
        return $users;
    }
    /**
     * clear
     * clears the first level cache (identityMap)
     *
     * @return void
     */
    public function clear() {
        $this->identityMap = array();
    }
    /**
     * getRecord
     * first checks if record exists in identityMap, if not
     * returns a new record
     *
     * @return Doctrine_Record
     */
    public function getRecord() {
        $key = $this->getIdentifier();

        if( ! is_array($key))
            $key = array($key);


        foreach($key as $k) {
            if( ! isset($this->data[$k]))
                throw new Doctrine_Exception("No primary key found");

            $id[] = $this->data[$k];
        }

        $id = implode(' ', $id);

        if(isset($this->identityMap[$id]))
            $record = $this->identityMap[$id];
        else {
        /**
            if($this->createsChildren) {

            }
         */
            $record = new $this->name($this);
            $this->identityMap[$id] = $record;
        }
        $this->data = array();

        return $record;
    }
    /**
     * @param $id                       database row id
     * @throws Doctrine_Find_Exception
     * @return DAOProxy                 a proxy for given identifier
     */
    final public function getProxy($id = null) {
        if($id !== null) {
            $query = "SELECT ".implode(", ",$this->primaryKeys)." FROM ".$this->getTableName()." WHERE ".implode(" = ? && ",$this->primaryKeys)." = ?";
            $query = $this->applyInheritance($query);
            
            $params = array_merge(array($id), array_values($this->inheritanceMap));

            $this->data = $this->session->execute($query,$params)->fetch(PDO::FETCH_ASSOC);

            if($this->data === false)
                throw new Doctrine_Find_Exception();
        }
        return $this->getRecord();
    }
    /**
     * getTableDescription
     * @return Doctrine_Table_Description               
     */
    final public function getTableDescription() {
        return $this->columns;
    }
    /**
     * @return Doctrine_Query                           a Doctrine_Query object
     */
    public function getQueryObject() {
        $graph = new Doctrine_Query($this->getSession());
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
        $query = $this->session->modifyLimitQuery($query,$limit,$offset);
        if( ! empty($array)) {
            $stmt = $this->session->getDBH()->prepare($query);
            $stmt->execute($array);
        } else {
            $stmt = $this->session->getDBH()->query($query);
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach($data as $row) {
            $this->data = $row;
            $record = $this->getRecord();
            $coll->add($record);
        }
        return $coll;
    }
    /**
     * @return integer
     */
    final public function getColumnCount() {
        return $this->columnCount;
    }
    /**
     * returns all columns and their definitions
     *
     * @return array
     */
    final public function getColumns() {
        return $this->columns;
    }
    /**
     * returns an array containing all the column names
     *
     * @return array
     */
    public function getColumnNames() {
        return array_keys($this->columns);
    }
    /**
     * getTypeOf
     */
    public function getTypeOf($column) {
        if(isset($this->columns[$column]))
            return $this->columns[$column][0];
    }
    /**
     * setData
     * doctrine uses this function internally
     * users are strongly discouraged to use this function
     *
     * @param array $data               internal data
     * @return void
     */
    public function setData(array $data) {
        $this->data = $data;
    }
    /**
     * returns the maximum primary key value
     *
     * @return integer
     */
    final public function getMaxIdentifier() {
        $sql  = "SELECT MAX(".$this->getIdentifier().") FROM ".$this->getTableName();
        $stmt = $this->session->getDBH()->query($sql);
        $data = $stmt->fetch(PDO::FETCH_NUM);
        return isset($data[0])?$data[0]:1;
    }
    /**
     * return whether or not a newly created object is new or not
     *
     * @return boolean
     */
    final public function isNewEntry() {
        return $this->isNewEntry;
    }
    /**
     * returns simple cached query
     *
     * @return string
     */
    final public function getQuery() {
        return $this->query;
    }
    /**
     * returns internal data, used by Doctrine_Record instances 
     * when retrieving data from database
     *
     * @return array
     */
    final public function getData() {
        return $this->data;
    }
    /**
     * returns a string representation of this object
     *
     * @return string
     */
    public function __toString() {
        return Doctrine_Lib::getTableAsString($this);
    }
}
?>
