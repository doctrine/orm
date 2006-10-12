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
Doctrine::autoload('Doctrine_Access');
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
     * @var Doctrine_Validator_ErrorStack   error stack object
     */
    protected $errorStack;
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
     * @param Doctrine_Table|null $table       a Doctrine_Table object or null, 
     *                                         if null the table object is retrieved from current connection
     *
     * @throws Doctrine_Connection_Exception   if object is created using the new operator and there are no
     *                                         open connections
     * @throws Doctrine_Record_Exception       if the cleanData operation fails somehow
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
        // If not this record is only used for creating table definition and setting up
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

                // set the default values for this record
                $this->setDefaultValues();

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

            $this->errorStack = new Doctrine_Validator_ErrorStack();

            $repository = $this->table->getRepository();
            $repository->add($this);
        }
    }
    /**
     * initNullObject
     *
     * @param Doctrine_Null $null
     * @return void
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
     * this method is used for setting up relations and attributes
     * it should be implemented by child classes
     *
     * @return void
     */
    public function setUp() { }
    /**
     * getOID
     * returns the object identifier
     *
     * @return integer
     */
    public function getOID() {
        return $this->oid;
    }
    /**
     * isValid
     *
     * @return boolean                          whether or not this record passes all column validations
     */
    public function isValid() {
        if( ! $this->table->getAttribute(Doctrine::ATTR_VLD))
            return true;
        
        // Clear the stack from any previous errors.
        $this->errorStack->clear();
        
        // Run validation process  
        $validator = new Doctrine_Validator();
        $validator->validateRecord($this);
        $this->validate();
        if ($this->state == self::STATE_TDIRTY || $this->state == self::STATE_TCLEAN) {
            $this->validateOnInsert();
        } else {
            $this->validateOnUpdate();
        }
        
        return $this->errorStack->count() == 0 ? true : false;
        //$this->errorStack->merge($validator->getErrorStack());
    }
    /**
     * Emtpy template method to provide concrete Record classes with the possibility
     * to hook into the validation procedure, doing any custom / specialized
     * validations that are neccessary.
     */
    protected function validate() {}
    /**
     * Empty tempalte method to provide concrete Record classes with the possibility
     * to hook into the validation procedure only when the record is going to be
     * updated.
     */
    protected function validateOnUpdate() {}
    /**
     * Empty tempalte method to provide concrete Record classes with the possibility
     * to hook into the validation procedure only when the record is going to be
     * inserted into the data store the first time.
     */
    protected function validateOnInsert() {}
    /**
     * getErrorStack
     *
     * @return Doctrine_Validator_ErrorStack    returns the errorStack associated with this record
     */
    public function getErrorStack() {
        return $this->errorStack;
    }
    /**
     * setDefaultValues
     * sets the default values for records internal data
     *
     * @param boolean $overwrite                whether or not to overwrite the already set values
     * @return boolean
     */
    public function setDefaultValues($overwrite = false) {
        if( ! $this->table->hasDefaultValues())
            return false;
            
        foreach($this->data as $column => $value) {
            $default = $this->table->getDefaultValueOf($column);

            if($default === null)
                $default = self::$null;

            if($value === self::$null || $overwrite) {
                $this->data[$column] = $default;
                $this->modified[]    = $column;
                $this->state = Doctrine_Record::STATE_TDIRTY;
            }
        }
    }
    /**
     * cleanData
     * this method does several things to records internal data
     *
     * 1. It unserializes array and object typed columns
     * 2. Uncompresses gzip typed columns
     * 3. Gets the appropriate enum values for enum typed columns
     * 4. Initializes special null object pointer for null values (for fast column existence checking purposes)
     *
     *
     * example:
     *
     * $data = array("name"=>"John","lastname"=> null, "id" => 1,"unknown" => "unknown");
     * $names = array("name", "lastname", "id");
     * $data after operation:
     * $data = array("name"=>"John","lastname" => Object(Doctrine_Null));
     *
     * here column 'id' is removed since its auto-incremented primary key (read-only)
     *
     * @throws Doctrine_Record_Exception        if unserialization of array/object typed column fails or 
     *                                          if uncompression of gzip typed column fails
     *
     * @return integer
     */
    private function cleanData($debug = false) {
        $tmp = $this->data;

        $this->data = array();

        $count = 0;

        foreach($this->table->getColumnNames() as $name) {
            $type = $this->table->getTypeOf($name);

            if( ! isset($tmp[$name])) {
                $this->data[$name] = self::$null;
            } else {
                switch($type):
                    case "array":
                    case "object":

                        if($tmp[$name] !== self::$null) {
                            if(is_string($tmp[$name])) {
                                $value = unserialize($tmp[$name]);

                                if($value === false)
                                    throw new Doctrine_Record_Exception("Unserialization of $name failed. ".var_dump(substr($tmp[$lower],0,30)."...",true));
                            } else
                                $value = $tmp[$name];

                            $this->data[$name] = $value;
                        }
                    break;
                    case "gzip":

                        if($tmp[$name] !== self::$null) {
                            $value = gzuncompress($tmp[$name]);
                            

                            if($value === false)
                                throw new Doctrine_Record_Exception("Uncompressing of $name failed.");

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
     * prepareIdentifiers
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
     * serialize
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
     * @param string $serialized                Doctrine_Record as serialized string
     * @throws Doctrine_Record_Exception        if the cleanData operation fails somehow
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

        $this->prepareIdentifiers($this->exists());

        $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onWakeUp($this);
    }


    /**
     * addCollection
     *
     * @param Doctrine_Collection $collection
     * @param mixed $key
     */
    final public function addCollection(Doctrine_Collection $collection,$key = null) {
        if($key !== null) {
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
     * @throws Doctrine_Record_Exception        When the refresh operation fails (when the database row 
     *                                          this record represents does not exist anymore)
     * @return boolean
     */
    final public function refresh() {
        $id = $this->obtainIdentifier();
        if( ! is_array($id))
            $id = array($id);

        if(empty($id))
            return false;

        $id = array_values($id);

        $query          = $this->table->getQuery()." WHERE ".implode(" = ? AND ",$this->table->getPrimaryKeys())." = ?";
        $stmt           = $this->table->getConnection()->execute($query,$id);

        $this->data     = $stmt->fetch(PDO::FETCH_ASSOC);


        if( ! $this->data)
            throw new Doctrine_Record_Exception('Failed to refresh. Record does not exist anymore');

        $this->data     = array_change_key_case($this->data, CASE_LOWER);

        $this->modified = array();
        $this->cleanData(true);

        $this->prepareIdentifiers();

        $this->state    = Doctrine_Record::STATE_CLEAN;

        $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onLoad($this);

        return true;
    }
    /**
     * factoryRefresh
     * refreshes the data from outer source (Doctrine_Table)
     *
     * @throws Doctrine_Record_Exception        When the primary key of this record doesn't match the primary key fetched from a collection
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
     * getTable
     * returns the table object for this record
     *
     * @return object Doctrine_Table        a Doctrine_Table object
     */
    final public function getTable() {
        return $this->table;
    }
    /**
     * getData
     * return all the internal data
     *
     * @return array                        an array containing all the properties
     */
    final public function getData() {
        return $this->data;
    }
    /**
     * rawGet
     * returns the value of a property, if the property is not yet loaded
     * this method does NOT load it
     *
     * @param $name                         name of the property
     * @throws Doctrine_Record_Exception    if trying to get an unknown property
     * @return mixed
     */

    public function rawGet($name) {
        if( ! isset($this->data[$name]))
            throw new Doctrine_Record_Exception('Unknown property '. $name);

        if($this->data[$name] === self::$null)
            return null;

        return $this->data[$name];
    }

    /**
     * load
     * loads all the unitialized properties from the database
     *
     * @return boolean
     */
    public function load() {
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

            return true;
        }
        return false;
    }
    /**
     * get
     * returns a value of a property or a related component
     *
     * @param mixed $name                       name of the property or related component
     * @param boolean $invoke                   whether or not to invoke the onGetProperty listener
     * @throws Doctrine_Record_Exception        if trying to get a value of unknown property / related component
     * @return mixed
     */
    public function get($name, $invoke = true) {
        
        $listener = $this->table->getAttribute(Doctrine::ATTR_LISTENER);
        $value    = self::$null;
        $lower    = strtolower($name);

        if(isset($this->data[$lower])) {

            // check if the property is null (= it is the Doctrine_Null object located in self::$null)
            if($this->data[$lower] === self::$null) {
                $this->load();
            }

            if($this->data[$lower] === self::$null)
                $value = null;
            else
                $value = $this->data[$lower];

        }


        if($value !== self::$null) {

            $value = $this->table->invokeGet($this, $name, $value);

            if($invoke && $name !== $this->table->getIdentifier())
                return $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onGetProperty($this, $name, $value);
            else
                return $value;

            return $value;
        }


        if(isset($this->id[$lower]))
            return $this->id[$lower];

        if($name === $this->table->getIdentifier())
            return null;

        $rel = $this->table->getRelation($name);

        try {
            if( ! isset($this->references[$name]))
                $this->loadReference($name);
        } catch(Doctrine_Table_Exception $e) {
            throw new Doctrine_Record_Exception("Unknown property / related component '$name'.");
        }

        return $this->references[$name];
    }

    /**
     * set
     * method for altering properties and Doctrine_Record references
     * if the load parameter is set to false this method will not try to load uninitialized record data
     *
     * @param mixed $name                   name of the property or reference
     * @param mixed $value                  value of the property or reference
     * @param boolean $load                 whether or not to refresh / load the uninitialized record data
     *
     * @throws Doctrine_Record_Exception    if trying to set a value for unknown property / related component
     * @throws Doctrine_Record_Exception    if trying to set a value of wrong type for related component
     *
     * @return Doctrine_Record
     */
    public function set($name, $value, $load = true) {
        $lower = strtolower($name);

        if(isset($this->data[$lower])) {

            if($value instanceof Doctrine_Record) {
                $id = $value->getIncremented();

                if($id !== null)
                    $value = $id;
            }

            if($load)
                $old = $this->get($lower, false);
            else
                $old = $this->data[$lower];

            if($old !== $value) {

                $value = $this->table->invokeSet($this, $name, $value);
                
                $value = $this->table->getAttribute(Doctrine::ATTR_LISTENER)->onSetProperty($this, $name, $value);

                if($value === null)
                    $value = self::$null;

                $this->data[$lower] = $value;
                $this->modified[]   = $lower;
                switch($this->state):
                    case Doctrine_Record::STATE_CLEAN:
                        $this->state = Doctrine_Record::STATE_DIRTY;
                    break;
                    case Doctrine_Record::STATE_TCLEAN:
                        $this->state = Doctrine_Record::STATE_TDIRTY;
                    break;
                endswitch;
            }
        } else {
            try {
                $this->coreSetRelated($name, $value);
            } catch(Doctrine_Table_Exception $e) {
                throw new Doctrine_Record_Exception("Unknown property / related component '$name'.");
            }
        }
    }
    
    public function coreSetRelated($name, $value) {
        $rel = $this->table->getRelation($name);

        // one-to-many or one-to-one relation
        if($rel instanceof Doctrine_Relation_ForeignKey ||
           $rel instanceof Doctrine_Relation_LocalKey) {
            if( ! $rel->isOneToOne()) {
                // one-to-many relation found
                if( ! ($value instanceof Doctrine_Collection))
                    throw new Doctrine_Record_Exception("Couldn't call Doctrine::set(), second argument should be an instance of Doctrine_Collection when setting one-to-many references.");

                $value->setReference($this,$rel);
            } else {
                // one-to-one relation found
                if( ! ($value instanceof Doctrine_Record))
                    throw new Doctrine_Record_Exception("Couldn't call Doctrine::set(), second argument should be an instance of Doctrine_Record when setting one-to-one references.");

                if($rel instanceof Doctrine_Relation_LocalKey) {
                    $this->set($rel->getLocal(), $value, false);
                } else {
                    $value->set($rel->getForeign(), $this, false);
                }
            }

        } elseif($rel instanceof Doctrine_Relation_Association) {
            // join table relation found
            if( ! ($value instanceof Doctrine_Collection))
                throw new Doctrine_Record_Exception("Couldn't call Doctrine::set(), second argument should be an instance of Doctrine_Collection when setting many-to-many references.");
        
        }

        $this->references[$name] = $value;
    }
    /**
     * contains
     *
     * @param string $name
     * @return boolean
     */
    public function contains($name) {
        $lower = strtolower($name);

        if(isset($this->data[$lower]))
            return true;

        if(isset($this->id[$lower]))
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
    final public function save(Doctrine_Connection $conn = null) {
        if ($conn === null) {
            $conn = $this->table->getConnection();
        }
        $conn->beginTransaction();
        
        $saveLater = $conn->saveRelated($this);

        if ($this->isValid()) {
            $conn->save($this);
        } else {
            $conn->getTransaction()->addInvalid($this);
        }

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

        $conn->commit();
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
            
            if($this->data[$v] === self::$null) {
                $a[$v] = null;
                continue;
            }

            switch($type) {
                case 'array':
                case 'object':
                    $a[$v] = serialize($this->data[$v]);
                break;
                case 'gzip':
                    $a[$v] = gzcompress($this->data[$v],5);
                break;
                case 'boolean':
                    $a[$v] = (int) $this->data[$v];
                break;
                case 'enum':
                    $a[$v] = $this->table->enumIndex($v,$this->data[$v]);
                break;
                default:
                    if($this->data[$v] instanceof Doctrine_Record)
                        $this->data[$v] = $this->data[$v]->getIncremented();

                    $a[$v] = $this->data[$v];
            }
        }

        foreach($this->table->getInheritanceMap() as $k => $v) {
            $old = $this->get($k, false);

            if((string) $old !== (string) $v || $old === null) {
                $a[$k] = $v;
                $this->data[$k] = $v;
            }
        }

        return $a;
    }
    /**
     * count
     * this class implements countable interface
     *
     * @return integer                      the number of columns
     */
    public function count() {
        return count($this->data);
    }
    /**
     * alias for count()
     * 
     * @return integer
     */
    public function getColumnCount() {
        return $this->count();
    }
    /**
     * toArray
     * returns the record as an array
     * 
     * @return array
     */
    public function toArray() {
        $a = array();

        foreach($this as $column => $value) {
            $a[$column] = $value;
        }
        if($this->table->getIdentifierType() == Doctrine_Identifier::AUTO_INCREMENT) {
            $i      = $this->table->getIdentifier();
            $a[$i]  = $this->getIncremented();
        }
        return $a;
    }
    /**
     * exists
     * returns true if this record is persistent, otherwise false
     *
     * @return boolean
     */
    public function exists() {
        return ($this->state !== Doctrine_Record::STATE_TCLEAN &&
                $this->state !== Doctrine_Record::STATE_TDIRTY);
    }
    /**
     * method for checking existence of properties and Doctrine_Record references
     * @param mixed $name               name of the property or reference
     * @return boolean
     */
    public function hasRelation($name) {
        if(isset($this->data[$name]) || isset($this->id[$name]))
            return true;
        return $this->table->hasRelation($name);
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
     *
     * save the associations of many-to-many relations
     * this method also deletes associations that do not exist anymore
     *
     * @return void
     */
    final public function saveAssociations() {
        foreach($this->table->getRelations() as $fk) {
            $table   = $fk->getTable();
            $name    = $table->getComponentName();
            $alias   = $this->table->getAlias($name);

            if($fk instanceof Doctrine_Relation_Association) {
                switch($fk->getType()):
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
            } elseif($fk instanceof Doctrine_Relation_ForeignKey ||
                     $fk instanceof Doctrine_Relation_LocalKey) {

                if($fk->isOneToOne()) {
                        if(isset($this->originals[$alias]) && $this->originals[$alias]->obtainIdentifier() != $this->references[$alias]->obtainIdentifier())
                            $this->originals[$alias]->delete();

                } else {
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
                }
            }
        }
    }
    /**
     * getOriginals
     * returns an original collection of related component
     *
     * @return Doctrine_Collection
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
    public function delete(Doctrine_Connection $conn = null) {
        if ($conn == null) {
            $conn = $this->table->getConnection();
        }
        return $conn->delete($this);
    }
    /**
     * copy
     * returns a copy of this object
     *
     * @return Doctrine_Record
     */
    public function copy() {
        return $this->table->create($this->data);
    }
    /**
     * assignIdentifier
     *
     * @param integer $id
     * @return void
     */
    final public function assignIdentifier($id = false) {
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
     * assignOriginals
     *
     * @param string $alias
     * @param Doctrine_Collection $coll
     * @return void
     */
    public function assignOriginals($alias, Doctrine_Collection $coll) {
        $this->originals[$alias] = $coll;
    }
    /**
     * returns the primary keys of this object
     *
     * @return array
     */
    final public function obtainIdentifier() {
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
     * obtainReference
     * 
     * @param string $name
     * @throws Doctrine_Record_Exception        if trying to get an unknown related component
     */
    public function obtainReference($name) {
        if(isset($this->references[$name]))
            return $this->references[$name];
    
        throw new Doctrine_Record_Exception("Unknown reference $name");
    }
    /**
     * initalizes a one-to-many / many-to-many relation
     *
     * @param Doctrine_Collection $coll
     * @param Doctrine_Relation $connector
     * @return boolean
     */
    public function initReference(Doctrine_Collection $coll, Doctrine_Relation $connector) {
        $alias = $connector->getAlias();

        if(isset($this->references[$alias]))
            return false;

        if( ! $connector->isOneToOne()) {
            if( ! ($connector instanceof Doctrine_Relation_Association))
                $coll->setReference($this, $connector);

            $this->references[$alias] = $coll;
            $this->originals[$alias]  = clone $coll;

            return true;
        }
        return false;
    }
    
    public function lazyInitRelated(Doctrine_Collection $coll, Doctrine_Relation $connector) {
                                      	
    }
    /**
     * addReference
     * @param Doctrine_Record $record
     * @param mixed $key
     * @return void
     */
    public function addReference(Doctrine_Record $record, Doctrine_Relation $connector, $key = null) {
        $alias = $connector->getAlias();

        $this->references[$alias]->add($record, $key);
        $this->originals[$alias]->add($record, $key);
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
     * @throws Doctrine_Table_Exception             if trying to load an unknown related component
     * @param string $name
     * @return void
     */
    final public function loadReference($name) {
        $fk      = $this->table->getRelation($name);

        if($fk->isOneToOne()) {
            $this->references[$name] = $fk->fetchRelatedFor($this);
        } else {
            $coll = $fk->fetchRelatedFor($this);

            $this->references[$name] = $coll;
            $this->originals[$name]  = clone $coll;
        }
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
    final public function hasColumn($name, $type, $length = 2147483647, $options = "") {
        $this->table->setColumn($name, $type, $length, $options);
    }
    /**
     * countRelated
     *
     * @param string $name      the name of the related component
     * @return integer
     */
    public function countRelated($name) {
        $rel            = $this->table->getRelation($name);
        $componentName  = $rel->getTable()->getComponentName();
        $alias          = $rel->getTable()->getAlias(get_class($this));
        $query          = new Doctrine_Query();
        $query->from($componentName. '(' . 'COUNT(1)' . ')')->where($componentName. '.' .$alias. '.' . $this->getTable()->getIdentifier(). ' = ?');
        $array = $query->execute(array($this->getIncremented()));
        return $array[0]['COUNT(1)'];
    }
    /**
     * merge
     * merges this record with an array of values
     *
     * @param array $values
     * @return void
     */
    public function merge(array $values) {
        foreach($this->table->getColumnNames() as $value) {
            try {
                if(isset($values[$value]))
                    $this->set($value, $values[$value]);
            } catch(Exception $e) { 
                // silence all exceptions
            }
        }
    }
    public function setAttribute($attr, $value) {
        $this->table->setAttribute($attr, $value);
    }
    public function setTableName($tableName) {
        $this->table->setTableName($tableName);                                            	
    }
    public function setInheritanceMap($map) {
        $this->table->setInheritanceMap($map);
    }
    public function setEnumValues($column, $values) {
        $this->table->setEnumValues($column, $values);
    }
    /**
     * addListener
     *
     * @param Doctrine_DB_EventListener_Interface|Doctrine_Overloadable $listener
     * @return Doctrine_DB
     */
    public function addListener($listener, $name = null) {
        $this->table->addListener($listener, $name = null);
        return $this;
    }
    /**
     * getListener
     * 
     * @return Doctrine_DB_EventListener_Interface|Doctrine_Overloadable
     */
    public function getListener() {
        return $this->table->getListener();
    }
    /**
     * setListener
     *
     * @param Doctrine_DB_EventListener_Interface|Doctrine_Overloadable $listener
     * @return Doctrine_DB
     */
    public function setListener($listener) {
        $this->table->setListener($listener);
        return $this;
    }
    /**
     * call
     *
     * @param string|array $callback    valid callback
     * @param string $column            column name
     * @param mixed arg1 ... argN       optional callback arguments
     * @return Doctrine_Record
     */
    public function call($callback, $column) {
        $args = func_get_args();
        array_shift($args);

        if(isset($args[0])) {
            $column = $args[0];
            $args[0] = $this->get($column);

            $newvalue = call_user_func_array($callback, $args);

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

