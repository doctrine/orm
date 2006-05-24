<?php
require_once("Access.class.php");
/**
 * Doctrine_Record
 */
abstract class Doctrine_Record extends Doctrine_Access implements Countable, IteratorAggregate {
    /**
     * STATE CONSTANTS
     */

    /**
     * DIRTY STATE
     * a Doctrine_Record is in dirty state when its properties are changed
     */
    const STATE_DIRTY       = 1;
    /**
     * TDIRTY STATE
     * a Doctrine_Record is in transient dirty state when it is created and some of its fields are modified
     * but it is NOT yet persisted into database
     */
    const STATE_TDIRTY      = 2;
    /**
     * CLEAN STATE
     * a Doctrine_Record is in clean state when all of its properties are loaded from the database
     * and none of its properties are changed
     */
    const STATE_CLEAN       = 3;
    /**
     * PROXY STATE
     * a Doctrine_Record is in proxy state when its properties are not fully loaded
     */
    const STATE_PROXY       = 4;
    /**
     * NEW TCLEAN
     * a Doctrine_Record is in transient clean state when it is created and none of its fields are modified
     */
    const STATE_TCLEAN      = 5;
    /**
     * DELETED STATE
     * a Doctrine_Record turns into deleted state when it is deleted
     */
    const STATE_DELETED     = 6;

    /**
     * FETCHMODE CONSTANTS
     */

    /**
     * @var object Doctrine_Table $table    the factory that created this data access object
     */
    protected $table;
    /**
     * @var integer $id                     the primary key of this object
     */
    protected $id;
    /**
     * @var array $data                     the record data
     */
    protected $data       = array();

    /**
     * @var array $modified                 an array containing properties that have been modified
     */
    private $modified   = array();
    /**
     * @var integer $state                  the state of this record
     * @see STATE_* constants
     */
    private $state;
    /**
     * @var array $collections              the collections this record is in
     */
    private $collections = array();
    /**
     * @var mixed $references               an array containing all the references
     */
    private $references  = array();
    /**
     * @var mixed $originals                an array containing all the original references
     */
    private $originals   = array();
    /**
     * @var integer $index                  this index is used for creating object identifiers
     */
    private static $index = 1;
    /**
     * @var integer $oid                    object identifier
     */
    private $oid;

    /**
     * constructor
     * @param Doctrine_Table $table         a Doctrine_Table object
     * @throws Doctrine_Session_Exception   if object is created using the new operator and there are no
     *                                      open sessions
     */
    public function __construct($table = null) {
        if(isset($table) && $table instanceof Doctrine_Table) {
            $this->table = $table;
            $exists  = ( ! $this->table->isNewEntry());
        } else {
            $this->table = Doctrine_Manager::getInstance()->getCurrentSession()->getTable(get_class($this));
            $exists  = false;
        }

        // Check if the current session has the records table in its registry
        // If not this is record is only used for creating table definition and setting up
        // relations.

        if($this->table->getSession()->hasTable($this->table->getComponentName())) {

            $this->oid = self::$index;
    
            self::$index++;

            $keys = $this->table->getPrimaryKeys();

            if( ! $exists) {
                // listen the onPreCreate event
                $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onPreCreate($this);
            } else {
                // listen the onPreLoad event
                $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onPreLoad($this);
            }
            // get the data array
            $this->data = $this->table->getData();
    
            // clean data array
            $cols = $this->cleanData();

            $this->prepareIdentifiers($exists);

            if( ! $exists) {
    
                if($cols > 0)
                    $this->state = Doctrine_Record::STATE_TDIRTY;
                else
                    $this->state = Doctrine_Record::STATE_TCLEAN;

                // listen the onCreate event
                $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onCreate($this);

            } else {
                $this->state      = Doctrine_Record::STATE_CLEAN;

                if($cols <= 1)
                    $this->state  = Doctrine_Record::STATE_PROXY;


                // listen the onLoad event
                $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onLoad($this);
            }
            $this->table->getRepository()->add($this);
        }
    }
    /** 
     * setUp
     * implemented by child classes
     */
    public function setUp() { }
    /**
     * return the object identifier
     *
     * @return integer
     */
    public function getOID() {
        return $this->oid;
    }
    /**
     * cleanData
     * modifies data array
     * example:
     *
     * $data = array("name"=>"John","lastname"=> null,"id"=>1,"unknown"=>"unknown");
     * $names = array("name","lastname","id");
     * $data after operation:
     * $data = array("name"=>"John","lastname" => array(),"id"=>1);
     */
    private function cleanData() {
        $cols = 0;
        $tmp  = $this->data;
        
        $this->data = array();

        foreach($this->table->getColumnNames() as $name) {
            if( ! isset($tmp[$name])) {
                $this->data[$name] = array();

            } else {
                $cols++;
                $this->data[$name] = $tmp[$name];
            }
        }

        return $cols;
    }
    /**
     * prepares identifiers
     *
     * @return void
     */
    private function prepareIdentifiers($exists = true) {
        switch($this->table->getIdentifierType()):
            case Doctrine_Identifier::AUTO_INCREMENT:
            case Doctrine_Identifier::SEQUENCE:
                if($exists) {
                    $name = $this->table->getIdentifier();

                    if(isset($this->data[$name]))
                        $this->id = $this->data[$name];
    
                    unset($this->data[$name]);
                }
            break;
            case Doctrine_Identifier::COMPOSITE:
                $names      = $this->table->getIdentifier();
                $this->id   = array();

                foreach($names as $name) {
                    $this->id[$name] = isset($this->data[$name])?$this->data[$name]:null;
                }
            break;
        endswitch;
    }
    /**
     * this method is automatically called when this Doctrine_Record is serialized
     *
     * @return array
     */
    public function __sleep() {
        $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onSleep($this);

        $this->table = $this->table->getComponentName();
        // unset all vars that won't need to be serialized

        unset($this->modified);
        unset($this->associations);
        unset($this->state);
        unset($this->collections);
        unset($this->references);    
        unset($this->originals);
        unset($this->oid);

        foreach($this->data as $k=>$v) {
            if($v instanceof Doctrine_Record)
                $this->data[$k] = array();
        }
        return array_keys(get_object_vars($this));

    }
    /**
     * unseralize
     * this method is automatically called everytime a Doctrine_Record object is unserialized
     *
     * @return void
     */
    public function __wakeup() {

        $this->modified = array();
        $this->state    = Doctrine_Record::STATE_CLEAN;

        $name       = $this->table;

        $manager    = Doctrine_Manager::getInstance();
        $sess       = $manager->getCurrentSession();

        $this->oid  = self::$index;
        self::$index++;

        $this->table = $sess->getTable($name);

        $this->table->getRepository()->add($this);

        $this->cleanData();

        //unset($this->data['id']);
        
        $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onWakeUp($this);

    }
    /**
     * addCollection
     * @param Doctrine_Collection $collection
     * @param mixed $key
     */
    final public function addCollection(Doctrine_Collection $collection,$key = null) {
        if($key !== null) {
            if(isset($this->collections[$key])) 
                throw InvalidKeyException();

            $this->collections[$key] = $collection;
        } else {
            $this->collections[] = $collection;
        }
    }
    /**
     * getCollection
     * @param integer $key
     * @return Doctrine_Collection
     */
    final public function getCollection($key) {
        return $this->collections[$key];
    }
    /**
     * hasCollections
     * whether or not this record is part of a collection
     *
     * @return boolean
     */
    final public function hasCollections() {
        return (! empty($this->collections));
    }
    /**
     * getState
     * returns the current state of the object
     *
     * @see Doctrine_Record::STATE_* constants
     * @return integer
     */
    final public function getState() {
        return $this->state;
    }
    /**
     * refresh   
     * refresh internal data from the database
     *
     * @return boolean
     */
    final public function refresh() {
        $id = $this->getID();
        if( ! is_array($id))
            $id = array($id);

        if(empty($id))
            return false;

        $id = array_values($id);

        $query          = $this->table->getQuery()." WHERE ".implode(" = ? && ",$this->table->getPrimaryKeys())." = ?";
        $this->data     = $this->table->getSession()->execute($query,$id)->fetch(PDO::FETCH_ASSOC);

        $this->modified = array();
        $this->cleanData();

        $this->prepareIdentifiers();

        $this->state    = Doctrine_Record::STATE_CLEAN;

        return true;
    }
    /**
     * factoryRefresh
     * @throws Doctrine_Exception
     * @return void
     */
    final public function factoryRefresh() {
        $data = $this->table->getData();
        $id   = $this->id;
        
        $this->prepareIdentifiers();

        if($this->id != $id)
            throw new Doctrine_Refresh_Exception();

        $this->data     = $data;

        $this->cleanData();

        $this->state    = Doctrine_Record::STATE_CLEAN;
        $this->modified = array();
    }
    /**
     * return the factory that created this data access object
     * @return object Doctrine_Table        a Doctrine_Table object
     */
    final public function getTable() {
        return $this->table;
    }
    /**
     * return all the internal data
     * @return array                    an array containing all the properties
     */
    final public function getData() {
        return $this->data;
    }
    /**
     * get
     * returns a value of a property or a related component 
     *
     * @param $name                     name of the property or related component
     * @throws InvalidKeyException
     * @return mixed
     */
    public function get($name) {
        if(isset($this->data[$name])) {

            // check if the property is not loaded (= it is an empty array)
            if(is_array($this->data[$name])) {

                // no use trying to load the data from database if the Doctrine_Record is not a proxy
                if($this->state == Doctrine_Record::STATE_PROXY) {   
                    if( ! empty($this->collections)) {
                        foreach($this->collections as $collection) {
                            $collection->load($this);
                        }
                    } else {
                        $this->refresh();
                    }
                    $this->state = Doctrine_Record::STATE_CLEAN;
                }

                if(is_array($this->data[$name]))
                    return null;
            }
            return $this->data[$name];
        }

        if($name == $this->table->getIdentifier())
            return $this->id;

        if( ! isset($this->references[$name]))
                $this->loadReference($name);


        return $this->references[$name];
    }
    /**
     * rawSet
     * doctrine uses this function internally, not recommended for developers
     *
     * @param mixed $name               name of the property or reference
     * @param mixed $value              value of the property or reference
     */
    final public function rawSet($name,$value) {
        if($value instanceof Doctrine_Record)
            $id = $value->getID();

        if( ! empty($id))
            $value = $id;
            
        if(isset($this->data[$name])) {
            if( ! is_array($this->data[$name])) {
                if($this->data[$name] !== $value) {
                    switch($this->state):
                        case Doctrine_Record::STATE_CLEAN:
                            $this->state = Doctrine_Record::STATE_DIRTY;
                        break;
                        case Doctrine_Record::STATE_TCLEAN:
                            $this->state = Doctrine_Record::STATE_TDIRTY;
                    endswitch;
                }
            }

            if($this->state == Doctrine_Record::STATE_TCLEAN)
                $this->state = Doctrine_Record::STATE_TDIRTY;

            $this->data[$name] = $value;
            $this->modified[]  = $name;
        }
    }
    /**
     * set
     * method for altering properties and Doctrine_Record references
     *
     * @param mixed $name               name of the property or reference
     * @param mixed $value              value of the property or reference
     * @throws InvalidKeyException
     * @throws InvalidTypeException
     * @return void
     */
    public function set($name,$value) {
        if(isset($this->data[$name])) {

            if($value instanceof Doctrine_Record) {
                $id = $value->getID();
                
                if( ! empty($id)) 
                    $value = $value->getID();
            }
            
            $old = $this->get($name);

            if($old !== $value) {
                $this->data[$name] = $value;
                $this->modified[]  = $name;
                switch($this->state):
                    case Doctrine_Record::STATE_CLEAN:
                    case Doctrine_Record::STATE_PROXY:
                        $this->state = Doctrine_Record::STATE_DIRTY;
                    break;
                    case Doctrine_Record::STATE_TCLEAN:
                        $this->state = Doctrine_Record::STATE_TDIRTY;
                    break;
                endswitch;
            }
        } else {
            // if not found, throws InvalidKeyException

            $fk = $this->table->getForeignKey($name);

            // one-to-many or one-to-one relation
            if($fk instanceof Doctrine_ForeignKey || 
               $fk instanceof Doctrine_LocalKey) {
                switch($fk->getType()):
                    case Doctrine_Relation::MANY_COMPOSITE:
                    case Doctrine_Relation::MANY_AGGREGATE:
                        // one-to-many relation found
                        if( ! ($value instanceof Doctrine_Collection))
                            throw new Doctrine_Exception("Couldn't call Doctrine::set(), second argument should be an instance of Doctrine_Collection when setting one-to-many references.");

                        $value->setReference($this,$fk);
                    break;
                    case Doctrine_Relation::ONE_COMPOSITE:
                    case Doctrine_Relation::ONE_AGGREGATE:
                        // one-to-one relation found
                        if( ! ($value instanceof Doctrine_Record))
                            throw new Doctrine_Exception("Couldn't call Doctrine::set(), second argument should be an instance of Doctrine_Record when setting one-to-one references.");

                        if($fk->getLocal() == $this->table->getIdentifier()) {
                            $this->references[$name]->set($fk->getForeign(),$this);
                        } else {
                            $this->set($fk->getLocal(),$value);
                        }
                    break;
                endswitch;

            } elseif($fk instanceof Doctrine_Association) {
                // join table relation found
                if( ! ($value instanceof Doctrine_Collection))
                    throw new Doctrine_Exception("Couldn't call Doctrine::set(), second argument should be an instance of Doctrine_Collection when setting one-to-many references.");
            }

            $this->references[$name] = $value;
        }
    }
    /**
     * __isset
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name) {
        if(isset($this->data[$name]))
            return true;

        if(isset($this->references[$name]))
            return true;

        return false;
    }
    /**
     * @param string $name
     * @return void
     */
    public function __unset($name) {
        if(isset($this->data[$name]))
            $this->data[$name] = array();

        // todo: what to do with references ?
    }
    /**
     * applies the changes made to this object into database
     * this method is smart enough to know if any changes are made
     * and whether to use INSERT or UPDATE statement
     *
     * this method also saves the related composites
     *
     * @return void
     */
    final public function save() {
        $this->table->getSession()->beginTransaction();

        // listen the onPreSave event
        $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onPreSave($this);

        $saveLater = $this->table->getSession()->saveRelated($this);

        $this->table->getSession()->save($this);

        foreach($saveLater as $fk) {

            $table = $fk->getTable();
            $foreign = $fk->getForeign();
            $local   = $fk->getLocal();

            $alias   = $this->table->getAlias($table->getComponentName());

            if(isset($this->references[$alias])) {
                $obj = $this->references[$alias];
                $obj->save();
            }
        }

        // save the MANY-TO-MANY associations

        $this->saveAssociations();
            
        $this->table->getSession()->commit();
    }
    /**
     * returns an array of modified fields and associated values
     * @return array
     */
    final public function getModified() {
        $a = array();

        foreach($this->modified as $k=>$v) {
            $a[$v] = $this->data[$v];
        }
        return $a;
    }
    /**
     * returns an array of modified fields and values with data preparation
     * adds column aggregation inheritance and converts Records into primary key values
     *
     * @return array
     */
    final public function getPrepared() {
        $a = array();

        foreach($this->table->getInheritanceMap() as $k => $v) {
            $this->set($k,$v);                                                       	
        }

        foreach($this->modified as $k => $v) {
            if($this->data[$v] instanceof Doctrine_Record) {
                $this->data[$v] = $this->data[$v]->getID();
            }
            $a[$v] = $this->data[$v];
        }

        return $a;
    }
    /**
     * this class implements countable interface
     * @return integer                      the number of columns
     */
    public function count() {
        return count($this->data);
    }
    /**
     * getIterator
     * @return ArrayIterator                an ArrayIterator that iterates through the data
     */
    public function getIterator() {
        return new ArrayIterator($this->data);
    }
    /**
     * saveAssociations
     * save the associations of many-to-many relations
     * this method also deletes associations that do not exist anymore
     * @return void
     */
    final public function saveAssociations() {
        foreach($this->table->getForeignKeys() as $fk):
            $table   = $fk->getTable();
            $name    = $table->getComponentName();
            $alias   = $this->table->getAlias($name);

            if($fk instanceof Doctrine_Association) {
                switch($fk->getType()):
                    case Doctrine_Relation::MANY_COMPOSITE:

                    break;
                    case Doctrine_Relation::MANY_AGGREGATE:
                        $asf     = $fk->getAssociationFactory();

                        if(isset($this->references[$alias])) {

                            $new = $this->references[$alias];

                            if( ! isset($this->originals[$alias])) {
                                $this->loadReference($alias);
                            }

                            $r = $this->getRelationOperations($alias,$new);

                            foreach($r["delete"] as $record) {
                                $query = "DELETE FROM ".$asf->getTableName()." WHERE ".$fk->getForeign()." = ?"
                                                                            ." && ".$fk->getLocal()." = ?";
                                $this->table->getSession()->execute($query, array($record->getID(),$this->getID()));
                            }
                            foreach($r["add"] as $record) {
                                $reldao = $asf->create();
                                $reldao->set($fk->getForeign(),$record);
                                $reldao->set($fk->getLocal(),$this);
                                $reldao->save();
                            }  
                            $this->originals[$alias] = clone $this->references[$alias];
                        }
                    break;
                endswitch;
            } elseif($fk instanceof Doctrine_ForeignKey || 
                     $fk instanceof Doctrine_LocalKey) {
                
                switch($fk->getType()):
                    case Doctrine_Relation::ONE_COMPOSITE:
                        if(isset($this->originals[$alias]) && $this->originals[$alias]->getID() != $this->references[$alias]->getID())
                            $this->originals[$alias]->delete();
                    
                    break;
                    case Doctrine_Relation::MANY_COMPOSITE:
                        if(isset($this->references[$alias])) {
                            $new = $this->references[$alias];

                            if( ! isset($this->originals[$alias]))
                                $this->loadReference($alias);

                            $r = $this->getRelationOperations($alias,$new);

                            foreach($r["delete"] as $record) {
                                $record->delete();
                            }
                            
                            $this->originals[$alias] = clone $this->references[$alias];
                        }
                    break;
                endswitch;
            }
        endforeach;
    }
    /**
     * get the records that need to be added
     * and/or deleted in order to change the old collection
     * to the new one
     *
     * The algorithm here is very simple and definitely not
     * the fastest one, since we have to iterate through the collections twice.
     * the complexity of this algorithm is O(n^2)
     *
     * First we iterate through the new collection and get the
     * records that do not exist in the old collection (Doctrine_Records that need to be added).
     *
     * Then we iterate through the old collection and get the records
     * that do not exists in the new collection (Doctrine_Records that need to be deleted).
     */
    final public function getRelationOperations($name, Doctrine_Collection $new) {
        $r["add"]    = array();
        $r["delete"] = array();



        foreach($new as $k=>$record) {

            $found = false;

            if($record->getID() !== null) {
                foreach($this->originals[$name] as $k2 => $record2) {
                    if($record2->getID() == $record->getID()) {
                        $found = true;
                        break;
                    }
                }
            }
            if( ! $found) {
                $this->originals[$name][] = $record;
                $r["add"][] = $record;
            }
        }

        foreach($this->originals[$name] as $k => $record) {
            if($record->getID() === null)
                continue;

            $found = false;
            foreach($new as $k2=>$record2) {
                if($record2->getID() == $record->getID()) {
                    $found = true;
                    break;
                }
            }

            if( ! $found)  {
                $r["delete"][] = $record;
                unset($this->originals[$name][$k]);
            }
        }

        return $r;
    }
    /**
     * getOriginals
     */
    final public function getOriginals($name) {
        if( ! isset($this->originals[$name]))
            throw new InvalidKeyException();

        return $this->originals[$name];
    }
    /**
     * deletes this data access object and all the related composites
     * this operation is isolated by a transaction
     *
     * this event can be listened by the onPreDelete and onDelete listeners
     *
     * @return boolean      true on success, false on failure
     */
    public function delete() {
        $this->table->getSession()->delete($this);
    }
    /**
     * returns a copy of this object
     * @return DAO
     */
    public function copy() {
        return $this->table->create($this->data);
    }
    /**
     * @param integer $id
     * @return void
     */
    final public function setID($id = false) {
        if($id === false) {
            $this->id       = false;
            $this->cleanData();
            $this->state    = Doctrine_Record::STATE_TCLEAN;
            $this->modified = array();
        } elseif($id === true) {
            $this->prepareIdentifiers(false);
            $this->state    = Doctrine_Record::STATE_CLEAN;
            $this->modified = array();
        } else {
            $this->id       = $id;
            $this->state    = Doctrine_Record::STATE_CLEAN;
            $this->modified = array();
        }
    }
    /**
     * return the primary key(s) this object is pointing at
     * @return mixed id
     */
    final public function getID() {
        return $this->id;
    }
    /**
     * hasRefence
     * @param string $name
     * @return boolean
     */
    public function hasReference($name) {
        return isset($this->references[$name]);
    }
    /**
     * @param Doctrine_Collection $coll
     * @param string $connectorField
     */
    public function initReference(Doctrine_Collection $coll, Doctrine_Relation $connector) {
        $name = $this->table->getAlias($coll->getTable()->getComponentName());
        $coll->setReference($this, $connector);
        $this->references[$name] = $coll;
        $this->originals[$name]  = clone $coll;
    }
    /**
     * addReference
     * @param Doctrine_Record $record
     * @param mixed $key
     * @return void
     */
    public function addReference(Doctrine_Record $record, $key = null) {
        $name = $this->table->getAlias($record->getTable()->getComponentName());

        $this->references[$name]->add($record, $key);
        $this->originals[$name]->add($record, $key);
    }
    /**
     * getReferences
     * @return array    all references
     */
    public function getReferences() {
        return $this->references;
    }

    /**
     * @throws InvalidKeyException
     * @param name
     * @return void
     */
    final public function loadReference($name) {
        $fk      = $this->table->getForeignKey($name);
        $table   = $fk->getTable();

        $local   = $fk->getLocal();
        $foreign = $fk->getForeign();
        $graph   = $table->getQueryObject();
        $type    = $fk->getType();

        switch($this->getState()):
            case Doctrine_Record::STATE_TDIRTY:
            case Doctrine_Record::STATE_TCLEAN:

                if($type == Doctrine_Relation::ONE_COMPOSITE ||
                   $type == Doctrine_Relation::ONE_AGGREGATE) {

                    // ONE-TO-ONE
                    $this->references[$name] = $table->create();

                    if($fk instanceof Doctrine_ForeignKey) {
                        $this->references[$name]->set($fk->getForeign(),$this);
                    } else {
                        $this->set($fk->getLocal(),$this->references[$name]);
                    }
                } else {
                    $this->references[$name] = new Doctrine_Collection($table);
                    if($fk instanceof Doctrine_ForeignKey) {
                        // ONE-TO-MANY
                        $this->references[$name]->setReference($this,$fk);
                    }
                    $this->originals[$name]  = new Doctrine_Collection($table);
                }
            break;
            case Doctrine_Record::STATE_DIRTY:
            case Doctrine_Record::STATE_CLEAN:
            case Doctrine_Record::STATE_PROXY:

                 switch($fk->getType()):
                    case Doctrine_Relation::ONE_COMPOSITE:
                    case Doctrine_Relation::ONE_AGGREGATE:

                        // ONE-TO-ONE
                        $id      = $this->get($local);

                        if($fk instanceof Doctrine_LocalKey) {

                            if(empty($id)) {
                                $this->references[$name] = $table->create();
                                $this->set($fk->getLocal(),$this->references[$name]);
                            } else {
                                try {
                                    $this->references[$name] = $table->find($id);
                                } catch(Doctrine_Find_Exception $e) {

                                }
                            }

                        } elseif ($fk instanceof Doctrine_ForeignKey) {

                            if(empty($id)) {
                                $this->references[$name] = $table->create();
                                $this->references[$name]->set($fk->getForeign(), $this);
                            } else {
                                $dql  = "FROM ".$table->getComponentName()." WHERE ".$table->getComponentName().".".$fk->getForeign()." = ?";
                                $coll = $graph->query($dql, array($id));
                                $this->references[$name] = $coll[0];
                                $this->references[$name]->set($fk->getForeign(), $this);
                            }
                        }
                    break;
                    default:
                        // ONE-TO-MANY
                        if($fk instanceof Doctrine_ForeignKey) {
                            $id      = $this->get($local);
                            $query = "FROM ".$table->getComponentName()." WHERE ".$table->getComponentName().".".$fk->getForeign()." = ?";
                            $coll = $graph->query($query,array($id));

                            $this->references[$name] = $coll;
                            $this->references[$name]->setReference($this, $fk);
    
                            $this->originals[$name]  = clone $coll;

                        } elseif($fk instanceof Doctrine_Association) {            
                            $asf     = $fk->getAssociationFactory();
                            $query   = "SELECT ".$foreign." FROM ".$asf->getTableName()." WHERE ".$local." = ?";
        
                            $graph   = new Doctrine_Query($table->getSession());
                            $query   = "FROM ".$table->getComponentName()." WHERE ".$table->getComponentName().".".$table->getIdentifier()." IN ($query)";

                            $coll    = $graph->query($query, array($this->getID()));
        
                            $this->references[$name] = $coll;
                            $this->originals[$name]  = clone $coll;

                        }
                 endswitch;
            break;
        endswitch;
    }

    /**
     * binds One-to-One composite relation
     *
     * @param string $objTableName
     * @param string $fkField
     * @return void
     */
    final public function ownsOne($componentName,$foreignKey, $localKey = null) {
        $this->table->bind($componentName,$foreignKey,Doctrine_Relation::ONE_COMPOSITE, $localKey);
    }
    /**
     * binds One-to-Many composite relation
     *
     * @param string $objTableName
     * @param string $fkField
     * @return void
     */
    final public function ownsMany($componentName,$foreignKey, $localKey = null) {
        $this->table->bind($componentName,$foreignKey,Doctrine_Relation::MANY_COMPOSITE, $localKey);
    }
    /**
     * binds One-to-One aggregate relation
     *
     * @param string $objTableName
     * @param string $fkField
     * @return void
     */
    final public function hasOne($componentName,$foreignKey, $localKey = null) {
        $this->table->bind($componentName,$foreignKey,Doctrine_Relation::ONE_AGGREGATE, $localKey);
    }
    /**
     * binds One-to-Many aggregate relation
     *
     * @param string $objTableName
     * @param string $fkField
     * @return void
     */
    final public function hasMany($componentName,$foreignKey, $localKey = null) {
        $this->table->bind($componentName,$foreignKey,Doctrine_Relation::MANY_AGGREGATE, $localKey);
    }
    /**
     * setInheritanceMap
     * @param array $inheritanceMap
     * @return void
     */
    final public function setInheritanceMap(array $inheritanceMap) {
        $this->table->setInheritanceMap($inheritanceMap);
    }
    /**
     * setPrimaryKey
     * @param mixed $key
     */
    final public function setPrimaryKey($key) {
        $this->table->setPrimaryKey($key);
    }
    /**
     * setTableName
     * @param string $name              table name
     * @return void
     */
    final public function setTableName($name) {
        $this->table->setTableName($name);
    }
    /**
     * setAttribute
     * @param integer $attribute
     * @param mixed $value
     * @see Doctrine::ATTR_* constants
     * @return void
     */
    final public function setAttribute($attribute, $value) {
        $this->table->setAttribute($attribute,$value);
    }
    /**
     * hasColumn
     * sets a column definition
     *
     * @param string $name
     * @param string $type
     * @param integer $length
     * @param mixed $options
     * @return void
     */
    final public function hasColumn($name, $type, $length = 20, $options = "") {
        $this->table->setColumn($name, $type, $length, $options);
    }
    /**
     * returns a string representation of this object
     */
    public function __toString() {
        return Doctrine_Lib::getRecordAsString($this);
    }
}
?>
