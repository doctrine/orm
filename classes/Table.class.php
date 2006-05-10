<?php
require_once("Configurable.class.php");
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
     * @var array $foreignKeys                          an array containing all the Doctrine_ForeignKey objects for this table
     */
    private $foreignKeys      = array();
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
     * @var Doctrine_Repository $repository             first level cache
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

           	$name  = ucwords(strtolower($class));
            $names[] = $name;
        } while($class = get_parent_class($class));

        // reverse names
        $names = array_reverse($names);


        // create database table
        if(method_exists($record,"setTableDefinition")) {
            $record->setTableDefinition();

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
                    break;
                    default:
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
        $this->repository  = new Doctrine_Repository($this);

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
     * @var array           bound relations
     */
    final public function getBounds() {
        return $this->bound;
    }
    /**
     * @param string $name
     */
    final public function getBound($name) {
        if( ! isset($this->bound[$name])) 
            throw new InvalidKeyException();

        return $this->bound[$name];
    }
    /**
     * @param string $objTableName
     * @param string $field
     * @return void
     */
    final public function bind($objTableName,$field,$type,$localKey) {
        $name  = (string) $objTableName;
        $field = (string) $field;

        if(isset($this->foreignKeys[$name]))
            throw new InvalidKeyException();

        $e = explode(".", $field);
        
        // is reference table used?
        if($e[0] != $name && $e[0] == $this->name)
            $this->bound[$name] = array($field,Doctrine_Relation::MANY_COMPOSITE);



        $this->bound[$name] = array($field,$type,$localKey);
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
     * @return Doctrine_Repository
     */
    final public function getRepository() {
        return $this->repository;
    }
    /**
     * @param string $name              component name of which a foreign key object is bound
     * @return Doctrine_Relation
     */
    final public function getForeignKey($name) {
        if(isset($this->foreignKeys[$name]))
            return $this->foreignKeys[$name];

        if(isset($this->bound[$name])) {

            $field   = $this->bound[$name][0];
            $type    = $this->bound[$name][1];
            $local   = $this->bound[$name][2];
            $e       = explode(".",$field);
            $objTable = $this->session->getTable($name);

            switch($e[0]):
                case $name:
                    if( ! isset($local))
                        $local = $this->identifier;

                    // ONE-TO-MANY or ONE-TO-ONE
                    $foreignKey = new Doctrine_ForeignKey($objTable,$local,$e[1],$type);
                break;
                case $this->name:
                    // ONE-TO-ONE

                    if($type <= Doctrine_Relation::ONE_COMPOSITE) {
                        if( ! isset($local))
                            $local = $objTable->getIdentifier();

                        $foreignKey = new Doctrine_LocalKey($objTable,$e[1],$local,$type);
                    } else
                        throw new Doctrine_Mapping_Exception();
                break;
                default:
                    if(in_array($e[0], $this->parents)) {
                        // ONE-TO-ONE

                        if($type <= Doctrine_Relation::ONE_COMPOSITE) {
                            if( ! isset($local))
                                $local = $objTable->getIdentifier();

                            $foreignKey = new Doctrine_LocalKey($objTable,$e[1],$local,$type);
                        } else
                            throw new Doctrine_Mapping_Exception();
                    } else {
                        // POSSIBLY MANY-TO-MANY

                        $classes = array_merge($this->parents, array($this->name));

                        foreach($classes as $class) {
                            try {
                                $bound = $objTable->getBound($class);
                                break;
                            } catch(InvalidKeyException $exc) {

                            }
                        }
                        if( ! isset($local))
                            $local = $this->identifier;

                        $e2    = explode(".",$bound[0]);

                        if($e2[0] != $e[0])
                            throw new Doctrine_Mapping_Exception();

                        $associationTable = $this->session->getTable($e2[0]);

                        $this->foreignKeys[$e2[0]] = new Doctrine_ForeignKey($associationTable,$local,$e2[1],Doctrine_Relation::MANY_COMPOSITE);

                        $foreignKey         = new Doctrine_Association($objTable,$associationTable,$e2[1],$e[1],$type);
                    }
            endswitch;
            $this->foreignKeys[$name] = $foreignKey;
            return $this->foreignKeys[$name];
        } else {
            throw new InvalidKeyException();
        }
    }
    /**
     * @return array                    an array containing all foreign key objects
     */
    final public function getForeignKeys() {
        $a = array();
        foreach($this->bound as $k=>$v) {
            $a[$k] = $this->getForeignKey($k);
        }
        return $a;
    }
    /**
     * @return void
     */
    final public function setTableName($name) {
        $this->tableName = $name;
    }

    /**
     * @return string                   database table name this class represents
     */
    final public function getTableName() {
        return $this->tableName;
    }
    /**
     * createDAO
     * @param $array                    an array where keys are field names and values representing field values
     * @return Doctrine_Record                      A new Data Access Object. Uses an sql insert statement when saved
     */
    public function create(array $array = array()) {
        $this->data         = $array;
        $this->isNewEntry   = true;
        $record = $this->getRecord();
        $this->isNewEntry   = false;
        return $record;
    }
    /**
     * @param $id                       database row id
     * @throws Doctrine_Find_Exception
     * @return Doctrine_Record          a record for given database identifier
     */
    public function find($id = null) {
        if($id !== null) {
            $query  = $this->query." WHERE ".implode(" = ? AND ",$this->primaryKeys)." = ?";
            $query  = $this->applyInheritance($query);
            
            $params = array_merge(array($id), array_values($this->inheritanceMap));

            $this->data = $this->session->execute($query,$params)->fetch(PDO::FETCH_ASSOC);

            if($this->data === false)
                throw new Doctrine_Find_Exception();
        }
        return new $this->name($this);
    }
    /**
     * applyInheritance
     * @param $where                    query where part to be modified
     * @return                          query where part with column aggregation inheritance added
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
     * @return Doctrine_Collection            a collection of all data access objects
     */
    public function findAll() {
        $graph = new Doctrine_Query($this->session);
        $users = $graph->query("FROM ".$this->name);
        return $users;
    }
    /**
     * findBySql
     * @return Doctrine_Collection            a collection of data access objects
     */
    public function findBySql($sql, array $params = array()) {
        $graph = new Doctrine_Query($this->session);
        $users = $graph->query("FROM ".$this->name." WHERE ".$sql, $params);
        return $users;
    }
    /**
     * getRecord
     * @return Doctrine_Record
     */
    public function getRecord() {
        return new $this->name($this);
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
     * @return Doctrine_Table_Description               the columns object for this factory
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
     * @return array
     */
    final public function getColumns() {
        return $this->columns;
    }
    /**
     * @return array                    an array containing all the column names
     */
    public function getColumnNames() {
        return array_keys($this->columns);
    }
    /**
     * setData
     * @param array $data               internal data, users are strongly discouraged to use this function
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
     * @return boolean                  whether or not a newly created object is new or not
     */
    final public function isNewEntry() {
        return $this->isNewEntry;
    }
    /**
     * @return string                   simple cached query
     */
    final public function getQuery() {
        return $this->query;
    }
    /**
     * @return array                    internal data, used by Doctrine_Record instances when retrieving data from database
     */
    final public function getData() {
        return $this->data;
    }
    /**
     * @return string                   string representation of this object
     */
    public function __toString() {
        return Doctrine_Lib::getTableAsString($this);
    }
}
?>
