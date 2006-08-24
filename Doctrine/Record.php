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

require_once("Access.php");

/**
 * Doctrine_Record
 * All record classes should inherit this super class
 *
 * @author      Konsta Vesterinen
 * @license     LGPL
 * @package     Doctrine
 */

abstract class Doctrine_Record extends Doctrine_Access implements Countable, IteratorAggregate, Serializable {
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
     * CALLBACK CONSTANTS
     */

    /**
     * RAW CALLBACK
     *
     * when using a raw callback and the property if a record is changed using this callback the
     * record state remains untouched
     */
    const CALLBACK_RAW       = 1;
    /**
     * STATE-WISE CALLBACK
     *
     * state-wise callback means that when callback is used and the property is changed the
     * record state is also updated
     */
    const CALLBACK_STATEWISE = 2;

    /**
     * @var object Doctrine_Table $table    the factory that created this data access object
     */
    protected $table;
    /**
     * @var integer $id                     the primary keys of this object
     */
    protected $id           = array();
    /**
     * @var array $data                     the record data
     */
    protected $data         = array();
    /**
     * @var integer $state                  the state of this record
     * @see STATE_* constants
     */
    protected $state;
    /**
     * @var array $modified                 an array containing properties that have been modified
     */
    protected $modified     = array();
    /**
     * @var array $collections              the collections this record is in
     */
    private $collections    = array();
    /**
     * @var array $references               an array containing all the references
     */
    private $references     = array();
    /**
     * @var array $originals                an array containing all the original references
     */
    private $originals      = array();
    /**
     * @var array $filters
     */
    private $filters        = array();
    /**
     * @var integer $index                  this index is used for creating object identifiers
     */
    private static $index   = 1;
    /**
     * @var Doctrine_Null $null             a Doctrine_Null object used for extremely fast
     *                                      null value testing
     */
    private static $null;
    /**
     * @var integer $oid                    object identifier
     */
    private $oid;

    /**
     * constructor
     * @param Doctrine_Table $table         a Doctrine_Table object
     * @throws Doctrine_Connection_Exception   if object is created using the new operator and there are no
     *                                      open connections
     */
    public function __construct($table = null) {
        if(isset($table) && $table instanceof Doctrine_Table) {
            $this->table = $table;
            $exists  = ( ! $this->table->isNewEntry());
        } else {
            $this->table = Doctrine_Manager::getInstance()->getCurrentConnection()->getTable(get_class($this));
            $exists  = false;
        }

        // Check if the current connection has the records table in its registry
        // If not this is record is only used for creating table definition and setting up
        // relations.

        if($this->table->getConnection()->hasTable($this->table->getComponentName())) {

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

            // get the column count
            $count = count($this->data);

            // clean data array
            $this->cleanData();

            $this->prepareIdentifiers($exists);

            if( ! $exists) {

                if($count > 0)
                    $this->state = Doctrine_Record::STATE_TDIRTY;
                else
                    $this->state = Doctrine_Record::STATE_TCLEAN;

                // listen the onCreate event
                $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onCreate($this);

            } else {
                $this->state      = Doctrine_Record::STATE_CLEAN;

                if($count < $this->table->getColumnCount()) {
                    $this->state  = Doctrine_Record::STATE_PROXY;
                }

                // listen the onLoad event
                $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onLoad($this);
            }

            $repository = $this->table->getRepository();
            $repository->add($this);
        }
    }
    /**
     * initNullObject
     *
     * @param Doctrine_Null $null
     */
    public static function initNullObject(Doctrine_Null $null) {
        self::$null = $null;
    }
    /**
     * @return Doctrine_Null
     */
    public static function getNullObject() {
        return self::$null;
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
     * $data = array("name"=>"John","lastname"=> null, "id" => 1,"unknown" => "unknown");
     * $names = array("name", "lastname", "id");
     * $data after operation:
     * $data = array("name"=>"John","lastname" => Object(Doctrine_Null));
     *
     * here column 'id' is removed since its auto-incremented primary key (protected)
     *
     * @return integer
     */
    private function cleanData() {
        $tmp  = $this->data;

        $this->data = array();

        $count = 0;

        foreach($this->table->getColumnNames() as $name) {
            $type = $this->table->getTypeOf($name);

            if( ! isset($tmp[$name])) {
                //if($type == 'array') {
                //    $this->data[$name] = array();
                //} else
                    $this->data[$name] = self::$null;
            } else {
                switch($type):
                    case "array":
                    case "object":

                        if($tmp[$name] !== self::$null) {
                            $value = unserialize($tmp[$name]);
                            if($value === false)
                                throw new Doctrine_Exception("Unserialization of $name failed. ".var_dump($tmp[$name],true));

                            $this->data[$name] = $value;
                        }
                    break;
                    case "enum":
                        $this->data[$name] = $this->table->enumValue($name, $tmp[$name]);
                    break;
                    default:
                        $this->data[$name] = $tmp[$name];
                endswitch;
                $count++;
            }
        }

        return $count;
    }
    /**
     * prepares identifiers for later use
     *
     * @param boolean $exists               whether or not this record exists in persistent data store
     * @return void
     */
    private function prepareIdentifiers($exists = true) {
        switch($this->table->getIdentifierType()):
            case Doctrine_Identifier::AUTO_INCREMENT:
            case Doctrine_Identifier::SEQUENCE:
                $name = $this->table->getIdentifier();

                if($exists) {
                    if(isset($this->data[$name]) && $this->data[$name] !== self::$null)
                        $this->id[$name] = $this->data[$name];
                }

                unset($this->data[$name]);

            break;
            case Doctrine_Identifier::NORMAL:
                 $this->id   = array();
                 $name       = $this->table->getIdentifier();

                 if(isset($this->data[$name]) && $this->data[$name] !== self::$null)
                    $this->id[$name] = $this->data[$name];
            break;
            case Doctrine_Identifier::COMPOSITE:
                $names      = $this->table->getIdentifier();


                foreach($names as $name) {
                    if($this->data[$name] === self::$null)
                        $this->id[$name] = null;
                    else
                        $this->id[$name] = $this->data[$name];
                }
            break;
        endswitch;
    }
    /**
     * this method is automatically called when this Doctrine_Record is serialized
     *
     * @return array
     */
    public function serialize() {
        $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onSleep($this);

        $vars = get_object_vars($this);

        unset($vars['references']);
        unset($vars['collections']);
        unset($vars['originals']);
        unset($vars['table']);


        $name = $this->table->getIdentifier();
        $this->data = array_merge($this->data, $this->id);

        foreach($this->data as $k => $v) {
            if($v instanceof Doctrine_Record)
                unset($vars['data'][$k]);
            elseif($v === self::$null) {
                unset($vars['data'][$k]);
            } else {
                switch($this->table->getTypeOf($k)):
                    case "array":
                    case "object":
                        $vars['data'][$k] = serialize($vars['data'][$k]);
                    break;
                endswitch;
            }
        }

        return serialize($vars);
    }
    /**
     * unseralize
     * this method is automatically called everytime a Doctrine_Record object is unserialized
     *
     * @return void
     */
    public function unserialize($serialized) {
        $manager    = Doctrine_Manager::getInstance();
        $connection    = $manager->getCurrentConnection();

        $this->oid  = self::$index;
        self::$index++;

        $this->table = $connection->getTable(get_class($this));


        $array = unserialize($serialized);

        foreach($array as $name => $values) {
            $this->$name = $values;
        }

        $this->table->getRepository()->add($this);

        $this->cleanData();

        $exists = true;

        if($this->state == Doctrine_Record::STATE_TDIRTY ||
           $this->state == Doctrine_Record::STATE_TCLEAN)
            $exists = false;

        $this->prepareIdentifiers($exists);

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

        $query          = $this->table->getQuery()." WHERE ".implode(" = ? AND ",$this->table->getPrimaryKeys())." = ?";
        $this->data     = $this->table->getConnection()->execute($query,$id)->fetch(PDO::FETCH_ASSOC);

        $this->modified = array();
        $this->cleanData();

        $this->prepareIdentifiers();

        $this->state    = Doctrine_Record::STATE_CLEAN;

        $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onLoad($this);

        return true;
    }
    /**
     * factoryRefresh
     * refreshes the data from outer source (Doctrine_Table)
     *
     * @throws Doctrine_Exception
     * @return void
     */
    final public function factoryRefresh() {
        $this->data = $this->table->getData();
        $old  = $this->id;

        $this->cleanData();

        $this->prepareIdentifiers();

        if($this->id != $old)
            throw new Doctrine_Record_Exception("The refreshed primary key doesn't match the one in the record memory.", Doctrine::ERR_REFRESH);

        $this->state    = Doctrine_Record::STATE_CLEAN;
        $this->modified = array();

        $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onLoad($this);
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
     * rawGet
     * returns the value of a property, if the property is not yet loaded
     * this method does NOT load it
     *
     * @param $name                     name of the property
     * @return mixed
     */
    public function rawGet($name) {
        if( ! isset($this->data[$name]))
            throw new InvalidKeyException();

        if($this->data[$name] == self::$null)
            return null;

        return $this->data[$name];
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

            // check if the property is null (= it is the Doctrine_Null object located in self::$null)
            if($this->data[$name] === self::$null) {

                // only load the data from database if the Doctrine_Record is in proxy state
                if($this->state == Doctrine_Record::STATE_PROXY) {
                    if( ! empty($this->collections)) {
                        // delegate the loading operation to collections in which this record resides
                        foreach($this->collections as $collection) {
                            $collection->load($this);
                        }
                    } else {
                        $this->refresh();
                    }
                    $this->state = Doctrine_Record::STATE_CLEAN;
                }

                if($this->data[$name] === self::$null)
                    return null;
            }
            return $this->data[$name];
        }

        if(isset($this->id[$name]))
            return $this->id[$name];

        if($name === $this->table->getIdentifier())
            return null;


        if( ! isset($this->references[$name]))
                $this->loadReference($name);


        return $this->references[$name];
    }
    /**
     * internalSet
     *
     * @param mixed $name
     * @param mixed $value
     */
    final public function internalSet($name, $value) {
        $this->data[$name] = $value;
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
            $id = $value->getIncremented();

        if(isset($id))
            $value = $id;

        if(isset($this->data[$name])) {
            if($this->data[$name] === self::$null) {
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
                $id = $value->getIncremented();

                if($id !== null)
                    $value = $id;
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
                            throw new Doctrine_Record_Exception("Couldn't call Doctrine::set(), second argument should be an instance of Doctrine_Collection when setting one-to-many references.");

                        $value->setReference($this,$fk);
                    break;
                    case Doctrine_Relation::ONE_COMPOSITE:
                    case Doctrine_Relation::ONE_AGGREGATE:
                        // one-to-one relation found
                        if( ! ($value instanceof Doctrine_Record))
                            throw new Doctrine_Record_Exception("Couldn't call Doctrine::set(), second argument should be an instance of Doctrine_Record when setting one-to-one references.");

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
                    throw new Doctrine_Record_Exception("Couldn't call Doctrine::set(), second argument should be an instance of Doctrine_Collection when setting one-to-many references.");
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
        $this->table->getConnection()->beginTransaction();

        $saveLater = $this->table->getConnection()->saveRelated($this);

        $this->table->getConnection()->save($this);

        foreach($saveLater as $fk) {
            $table   = $fk->getTable();
            $alias   = $this->table->getAlias($table->getComponentName());

            if(isset($this->references[$alias])) {
                $obj = $this->references[$alias];
                $obj->save();
            }
        }

        // save the MANY-TO-MANY associations

        $this->saveAssociations();

        $this->table->getConnection()->commit();
    }
    /**
     * returns an array of modified fields and associated values
     * @return array
     */
    final public function getModified() {
        $a = array();

        foreach($this->modified as $k => $v) {
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
    final public function getPrepared(array $array = array()) {
        $a = array();

        if(empty($array))
            $array = $this->modified;

        foreach($array as $k => $v) {
            $type = $this->table->getTypeOf($v);

            if($type == 'array' ||
               $type == 'object') {

                $a[$v] = serialize($this->data[$v]);
                continue;

            } elseif($type == 'enum') {
                $a[$v] = $this->table->enumIndex($v,$this->data[$v]);
                continue;
            }

            if($this->data[$v] instanceof Doctrine_Record) {
                $this->data[$v] = $this->data[$v]->getIncremented();
            }

            $a[$v] = $this->data[$v];
        }

        foreach($this->table->getInheritanceMap() as $k => $v) {
            $old = $this->get($k);

            if((string) $old !== (string) $v || $old === null) {
                $a[$k] = $v;
                $this->data[$k] = $v;
            }
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
     * alias for count()
     */
    public function getColumnCount() {
        return $this->count();
    }
    /**
     * checks if record has data
     * @return boolean
     */
    final public function exists() {
        return $this->state !== Doctrine_Record::STATE_TCLEAN;
    }
    /**
     * method for checking existence of properties and Doctrine_Record references
     * @param mixed $name               name of the property or reference
     * @return boolean
     */
    public function hasRelation($name) {
        if(isset($this->data[$name]) || isset($this->id[$name]))
            return true;
        return $this->table->hasForeignKey($name);
    }
    /**
     * getIterator
     * @return Doctrine_Record_Iterator     a Doctrine_Record_Iterator that iterates through the data
     */
    public function getIterator() {
        return new Doctrine_Record_Iterator($this);
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

                            $r = Doctrine_Relation::getDeleteOperations($this->originals[$alias],$new);

                            foreach($r as $record) {
                                $query = "DELETE FROM ".$asf->getTableName()." WHERE ".$fk->getForeign()." = ?"
                                                                            ." AND ".$fk->getLocal()." = ?";
                                $this->table->getConnection()->execute($query, array($record->getIncremented(),$this->getIncremented()));
                            }

                            $r = Doctrine_Relation::getInsertOperations($this->originals[$alias],$new);
                            foreach($r as $record) {
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

                            $r = Doctrine_Relation::getDeleteOperations($this->originals[$alias], $new);

                            foreach($r as $record) {
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
        return $this->table->getConnection()->delete($this);
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
            $this->id       = array();
            $this->cleanData();
            $this->state    = Doctrine_Record::STATE_TCLEAN;
            $this->modified = array();
        } elseif($id === true) {
            $this->prepareIdentifiers(false);
            $this->state    = Doctrine_Record::STATE_CLEAN;
            $this->modified = array();
        } else {
            $name            = $this->table->getIdentifier();

            $this->id[$name] = $id;
            $this->state     = Doctrine_Record::STATE_CLEAN;
            $this->modified  = array();
        }
    }
    /**
     * returns the primary keys of this object
     *
     * @return array
     */
    final public function getID() {
        return $this->id;
    }
    /**
     * returns the value of autoincremented primary key of this object (if any)
     *
     * @return integer
     */
    final public function getIncremented() {
        $id = current($this->id);
        if($id === false)
            return null;

        return $id;
    }
    /**
     * getLast
     * this method is used internally be Doctrine_Query
     * it is needed to provide compatibility between
     * records and collections
     *
     * @return Doctrine_Record
     */
    public function getLast() {
        return $this;
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
     * initalizes a one-to-one relation
     *
     * @param Doctrine_Record $record
     * @param Doctrine_Relation $connector
     * @return void
     */
    public function initSingleReference(Doctrine_Record $record, Doctrine_Relation $connector) {
        $alias = $connector->getAlias();

        $this->references[$alias] = $record;
    }
    /**
     * initalizes a one-to-many / many-to-many relation
     *
     * @param Doctrine_Collection $coll
     * @param Doctrine_Relation $connector
     * @return void
     */
    public function initReference(Doctrine_Collection $coll, Doctrine_Relation $connector) {
        $alias = $connector->getAlias();

        if( ! ($connector instanceof Doctrine_Association))
            $coll->setReference($this, $connector);

        $this->references[$alias] = $coll;
        $this->originals[$alias]  = clone $coll;
    }
    /**
     * addReference
     * @param Doctrine_Record $record
     * @param mixed $key
     * @return void
     */
    public function addReference(Doctrine_Record $record, Doctrine_Relation $connector, $key = null) {
        $alias = $connector->getAlias();

        $this->references[$alias]->internalAdd($record, $key);
        $this->originals[$alias]->internalAdd($record, $key);
    }
    /**
     * getReferences
     * @return array    all references
     */
    public function getReferences() {
        return $this->references;
    }
    /**
     * setRelated
     *
     * @param string $alias
     * @param Doctrine_Access $coll
     */
    final public function setRelated($alias, Doctrine_Access $coll) {
        $this->references[$alias] = $coll;
        $this->originals[$alias]  = $coll;
    }
    /**
     * loadReference
     * loads a related component
     *
     * @throws InvalidKeyException
     * @param string $name
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

                                $record = $table->find($id);

                                if($record !== false)
                                    $this->references[$name] = $record;
                                else
                                    $this->references[$name] = $table->create();

                                    //$this->set($fk->getLocal(),$this->references[$name]);

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
                        $query   = $fk->getRelationDql(1);

                        // ONE-TO-MANY
                        if($fk instanceof Doctrine_ForeignKey) {
                            $id      = $this->get($local);
                            $coll    = $graph->query($query,array($id));
                            $coll->setReference($this, $fk);
                        } elseif($fk instanceof Doctrine_Association) {
                            $id      = $this->getIncremented();
                            $coll    = $graph->query($query, array($id));
                        }
                        $this->references[$name] = $coll;
                        $this->originals[$name]  = clone $coll;
                 endswitch;
            break;
        endswitch;
    }
    /**
     * filterRelated
     * lazy initializes a new filter instance for given related component
     *
     * @param $componentAlias        alias of the related component
     * @return Doctrine_Filter
     */
    final public function filterRelated($componentAlias) {
        if( ! isset($this->filters[$componentAlias])) {
            $this->filters[$componentAlias] = new Doctrine_Filter($componentAlias);
        }

        return $this->filters[$componentAlias];
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
     * setPrimaryKey
     * @param mixed $key
     */
    final public function setPrimaryKey($key) {
        $this->table->setPrimaryKey($key);
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
     * merge
     *
     * @param array $values
     */
    public function merge(array $values) {
        foreach($this->table->getColumnNames() as $value) {
            try {
                if(isset($values[$value]))
                    $this->set($value, $values[$value]);
            } catch(Exception $e) { }
        }
    }
    /**
     * __call
     * @param string $m
     * @param array $a
     */
    public function __call($m,$a) {
        if(method_exists($this->table, $m))
            return call_user_func_array(array($this->table, $m), $a);

        if( ! function_exists($m))
            throw new Doctrine_Record_Exception("unknown callback '$m'");

        if(isset($a[0])) {
            $column = $a[0];
            $a[0] = $this->get($column);

            $newvalue = call_user_func_array($m, $a);

            $this->data[$column] = $newvalue;
        }
        return $this;
    }
    /**
     * returns a string representation of this object
     */
    public function __toString() {
        return Doctrine_Lib::getRecordAsString($this);
    }
}
?>
