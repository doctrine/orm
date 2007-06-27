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
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
abstract class Doctrine_Record extends Doctrine_Access implements Countable, IteratorAggregate, Serializable
{
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
     * @var object Doctrine_Table $_table   the factory that created this data access object
     */
    protected $_table;
    /**
     * @var Doctrine_Node_<TreeImpl>        node object
     */
    protected $_node;
    /**
     * @var integer $_id                    the primary keys of this object
     */
    protected $_id           = array();
    /**
     * @var array $_data                    the record data
     */
    protected $_data         = array();
    /**
     * @var array $_values                  the values array, aggregate values and such are mapped into this array
     */
    protected $_values       = array();
    /**
     * @var integer $_state                 the state of this record
     * @see STATE_* constants
     */
    protected $_state;
    /**
     * @var array $_modified                an array containing properties that have been modified
     */
    protected $_modified     = array();
    /**
     * @var Doctrine_Validator_ErrorStack   error stack object
     */
    protected $_errorStack;
    /**
     * @var Doctrine_Record_Filter          the filter object
     */
    protected $_filter;
    /**
     * @var array $_references              an array containing all the references
     */
    protected $_references     = array();
    /**
     * @var integer $index                  this index is used for creating object identifiers
     */
    private static $_index = 1;
    /**
     * @var integer $oid                    object identifier, each Record object has a unique object identifier
     */
    private $_oid;

    /**
     * constructor
     * @param Doctrine_Table|null $table       a Doctrine_Table object or null,
     *                                         if null the table object is retrieved from current connection
     *
     * @param boolean $isNewEntry              whether or not this record is transient
     *
     * @throws Doctrine_Connection_Exception   if object is created using the new operator and there are no
     *                                         open connections
     * @throws Doctrine_Record_Exception       if the cleanData operation fails somehow
     */
    public function __construct($table = null, $isNewEntry = false)
    {
        if (isset($table) && $table instanceof Doctrine_Table) {
            $this->_table = $table;
            $exists = ( ! $isNewEntry);
        } else {
            $class  = get_class($this);
            // get the table of this class
            $this->_table = Doctrine_Manager::getInstance()
                            ->getTable(get_class($this));
            $exists = false;
        }

        // initialize the filter object
        $this->_filter = new Doctrine_Record_Filter($this);

        // Check if the current connection has the records table in its registry
        // If not this record is only used for creating table definition and setting up
        // relations.

        if ($this->_table->getConnection()->hasTable($this->_table->getComponentName())) {
            $this->_oid = self::$_index;

            self::$_index++;

            $keys = $this->_table->getPrimaryKeys();

            // get the data array
            $this->_data = $this->_table->getData();

            // get the column count
            $count = count($this->_data);

            // clean data array
            $this->_data = $this->_filter->cleanData($this->_data);

            $this->prepareIdentifiers($exists);

            if ( ! $exists) {
                if ($count > 0) {
                    $this->_state = Doctrine_Record::STATE_TDIRTY;
                } else {
                    $this->_state = Doctrine_Record::STATE_TCLEAN;
                }

                // set the default values for this record
                $this->assignDefaultValues();
            } else {
                $this->_state      = Doctrine_Record::STATE_CLEAN;

                if ($count < $this->_table->getColumnCount()) {
                    $this->_state  = Doctrine_Record::STATE_PROXY;
                }
            }

            $this->_errorStack = new Doctrine_Validator_ErrorStack();

            $repository = $this->_table->getRepository();
            $repository->add($this);
        }
        $this->construct();
    }
    /**
     * _index
     *
     * @return integer
     */
    public static function _index()
    {
        return self::$_index;
    }
    /**
     * setUp
     * this method is used for setting up relations and attributes
     * it should be implemented by child classes
     *
     * @return void
     */
    public function setUp()
    { }
    /**
     * construct
     * Empty tempalte method to provide concrete Record classes with the possibility
     * to hook into the constructor procedure
     *
     * @return void
     */
    public function construct()
    { }
    /**
     * getOid
     * returns the object identifier
     *
     * @return integer
     */
    public function getOid()
    {
        return $this->_oid;
    }
    /**
     * isValid
     *
     * @return boolean                          whether or not this record passes all column validations
     */
    public function isValid()
    {
        if ( ! $this->_table->getAttribute(Doctrine::ATTR_VLD)) {
            return true;
        }
        // Clear the stack from any previous errors.
        $this->_errorStack->clear();

        // Run validation process
        $validator = new Doctrine_Validator();
        $validator->validateRecord($this);
        $this->validate();
        if ($this->_state == self::STATE_TDIRTY || $this->_state == self::STATE_TCLEAN) {
            $this->validateOnInsert();
        } else {
            $this->validateOnUpdate();
        }

        return $this->_errorStack->count() == 0 ? true : false;
    }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the validation procedure, doing any custom / specialized
     * validations that are neccessary.
     */
    protected function validate()
    { }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the validation procedure only when the record is going to be
     * updated.
     */
    protected function validateOnUpdate()
    { }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the validation procedure only when the record is going to be
     * inserted into the data store the first time.
     */
    protected function validateOnInsert()
    { }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the serializing procedure.
     */
    public function preSerialize($event)
    { }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the serializing procedure.
     */
    public function postSerialize($event)
    { }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the serializing procedure.
     */
    public function preUnserialize($event)
    { }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the serializing procedure.
     */
    public function postUnserialize($event)
    { }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure.
     */
    public function preSave($event)
    { }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure.
     */
    public function postSave($event)
    { }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the deletion procedure.
     */
    public function preDelete($event)
    { }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the deletion procedure.
     */
    public function postDelete($event)
    { }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure only when the record is going to be
     * updated.
     */
    public function preUpdate($event)
    { }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure only when the record is going to be
     * updated.
     */
    public function postUpdate($event)
    { }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure only when the record is going to be
     * inserted into the data store the first time.
     */
    public function preInsert($event)
    { }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure only when the record is going to be
     * inserted into the data store the first time.
     */
    public function postInsert($event)
    { }
    /**
     * getErrorStack
     *
     * @return Doctrine_Validator_ErrorStack    returns the errorStack associated with this record
     */
    public function getErrorStack()
    {
        return $this->_errorStack;
    }
    /**
     * errorStack
     * assigns / returns record errorStack
     *
     * @param Doctrine_Validator_ErrorStack          errorStack to be assigned for this record
     * @return void|Doctrine_Validator_ErrorStack    returns the errorStack associated with this record
     */
    public function errorStack($stack = null)
    {
        if($stack !== null) {
            if( ! ($stack instanceof Doctrine_Validator_ErrorStack)) {
               throw new Doctrine_Record_Exception('Argument should be an instance of Doctrine_Validator_ErrorStack.');
            }
            $this->_errorStack = $stack;
        } else {
            return $this->_errorStack;
        }
    }
    /**
     * setDefaultValues
     * sets the default values for records internal data
     *
     * @param boolean $overwrite                whether or not to overwrite the already set values
     * @return boolean
     */
    public function assignDefaultValues($overwrite = false)
    {
        if ( ! $this->_table->hasDefaultValues()) {
            return false;
        }
        foreach ($this->_data as $column => $value) {
            $default = $this->_table->getDefaultValueOf($column);

            if ($default === null) {
                $default = self::$_null;
            }

            if ($value === self::$_null || $overwrite) {
                $this->_data[$column] = $default;
                $this->_modified[]    = $column;
                $this->_state = Doctrine_Record::STATE_TDIRTY;
            }
        }
    }
    /**
     * hydrate
     * hydrates this object from given array
     *
     * @param array $data
     * @return boolean
     */
    public function hydrate(array $data)
    {
        foreach ($data as $k => $v) {
            $this->_data[$k] = $v;
        }
        $this->_data = $this->_filter->cleanData($this->_data);
        $this->prepareIdentifiers(true);
    }
    /**
     * prepareIdentifiers
     * prepares identifiers for later use
     *
     * @param boolean $exists               whether or not this record exists in persistent data store
     * @return void
     */
    private function prepareIdentifiers($exists = true)
    {
        switch ($this->_table->getIdentifierType()) {
            case Doctrine::IDENTIFIER_AUTOINC:
            case Doctrine::IDENTIFIER_SEQUENCE:
                $name = $this->_table->getIdentifier();

                if ($exists) {
                    if (isset($this->_data[$name]) && $this->_data[$name] !== self::$_null) {
                        $this->_id[$name] = $this->_data[$name];
                    }
                }

                unset($this->_data[$name]);

                break;
            case Doctrine::IDENTIFIER_NATURAL:
                $this->_id   = array();
                $name       = $this->_table->getIdentifier();

                if (isset($this->_data[$name]) && $this->_data[$name] !== self::$_null) {
                    $this->_id[$name] = $this->_data[$name];
                }
                break;
            case Doctrine::IDENTIFIER_COMPOSITE:
                $names      = $this->_table->getIdentifier();

                foreach ($names as $name) {
                    if ($this->_data[$name] === self::$_null) {
                        $this->_id[$name] = null;
                    } else {
                        $this->_id[$name] = $this->_data[$name];
                    }
                }
                break;
        }
    }
    /**
     * serialize
     * this method is automatically called when this Doctrine_Record is serialized
     *
     * @return array
     */
    public function serialize()
    {
    	$event = new Doctrine_Event($this, Doctrine_Event::RECORD_SERIALIZE);

        $this->preSerialize($event);

        $vars = get_object_vars($this);

        unset($vars['_references']);
        unset($vars['_table']);
        unset($vars['_errorStack']);
        unset($vars['_filter']);
        unset($vars['_modified']);
        unset($vars['_node']);

        $name = $this->_table->getIdentifier();
        $this->_data = array_merge($this->_data, $this->_id);

        foreach ($this->_data as $k => $v) {
            if ($v instanceof Doctrine_Record) {
                unset($vars['_data'][$k]);
            } elseif ($v === self::$_null) {
                unset($vars['_data'][$k]);
            } else {
                switch ($this->_table->getTypeOf($k)) {
                    case 'array':
                    case 'object':
                        $vars['_data'][$k] = serialize($vars['_data'][$k]);
                        break;
                    case 'gzip':
                        $vars['_data'][$k] = gzcompress($vars['_data'][$k]);
                        break;
                    case 'enum':
                        $vars['_data'][$k] = $this->_table->enumIndex($k, $vars['_data'][$k]);
                        break;
                }
            }
        }

        $str = serialize($vars);
        
        $this->postSerialize($event);

        return $str;
    }
    /**
     * unseralize
     * this method is automatically called everytime a Doctrine_Record object is unserialized
     *
     * @param string $serialized                Doctrine_Record as serialized string
     * @throws Doctrine_Record_Exception        if the cleanData operation fails somehow
     * @return void
     */
    public function unserialize($serialized)
    {
        $manager    = Doctrine_Manager::getInstance();
        $connection = $manager->getConnectionForComponent(get_class($this));

        $this->_oid = self::$_index;
        self::$_index++;

        $this->_table = $connection->getTable(get_class($this));

        $array = unserialize($serialized);

        foreach($array as $k => $v) {
            $this->$k = $v;
        }

        $this->_table->getRepository()->add($this);
        $this->_filter = new Doctrine_Record_Filter($this);

        $this->_data = $this->_filter->cleanData($this->_data);

        $this->prepareIdentifiers($this->exists());

        $this->_table->getAttribute(Doctrine::ATTR_LISTENER)->onWakeUp($this);
    }
    /**
     * getState
     * returns the current state of the object
     *
     * @see Doctrine_Record::STATE_* constants
     * @return integer
     */
    public function getState()
    {
        return $this->_state;
    }
    /**
     * state
     * returns / assigns the state of this record
     *
     * @param integer|string $state                 if set, this method tries to set the record state to $state
     * @see Doctrine_Record::STATE_* constants
     *
     * @throws Doctrine_Record_State_Exception      if trying to set an unknown state
     * @return null|integer
     */
    public function state($state = null)
    {
        if ($state == null) {
            return $this->_state;
        }
        $err = false;
        if (is_integer($state)) {
            if ($state >= 1 && $state <= 6) {
                $this->_state = $state;
            } else {
                $err = true;
            }
        } elseif (is_string($state)) {
            $upper = strtoupper($state);
            switch ($upper) {
                case 'DIRTY':
                case 'CLEAN':
                case 'TDIRTY':
                case 'TCLEAN':
                case 'PROXY':
                case 'DELETED':
                    $this->_state = constant('Doctrine_Record::STATE_' . $upper);
                    break;
                default:
                    $err = true;
            }
        }

        if ($this->_state === Doctrine_Record::STATE_TCLEAN ||
            $this->_state === Doctrine_Record::STATE_CLEAN) {

            $this->_modified = array();
        }

        if ($err) {
            throw new Doctrine_Record_State_Exception('Unknown record state ' . $state);
        }
    }
    /**
     * refresh
     * refresh internal data from the database
     *
     * @throws Doctrine_Record_Exception        When the refresh operation fails (when the database row
     *                                          this record represents does not exist anymore)
     * @return boolean
     */
    public function refresh()
    {
        $id = $this->obtainIdentifier();
        if ( ! is_array($id)) {
            $id = array($id);
        }
        if (empty($id)) {
            return false;
        }
        $id = array_values($id);

        $query = 'SELECT * FROM ' . $this->_table->getOption('tableName') . ' WHERE ' . implode(' = ? AND ', $this->_table->getPrimaryKeys()) . ' = ?';
        $stmt  = $this->_table->getConnection()->execute($query,$id);

        $this->_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ( ! $this->_data) {
            throw new Doctrine_Record_Exception('Failed to refresh. Record does not exist anymore');
        }

        $this->_data     = array_change_key_case($this->_data, CASE_LOWER);

        $this->_modified = array();
        $this->_data     = $this->_filter->cleanData($this->_data);

        $this->prepareIdentifiers();

        $this->_state    = Doctrine_Record::STATE_CLEAN;

        $this->_table->getAttribute(Doctrine::ATTR_LISTENER)->onLoad($this);

        return true;
    }
    /**
     * factoryRefresh
     * refreshes the data from outer source (Doctrine_Table)
     *
     * @throws Doctrine_Record_Exception        When the primary key of this record doesn't match the primary key fetched from a collection
     * @return void
     */
    public function factoryRefresh()
    {
        $this->_data = $this->_table->getData();
        $old  = $this->_id;

        $this->_data = $this->_filter->cleanData($this->_data);

        $this->prepareIdentifiers();

        if ($this->_id != $old)
            throw new Doctrine_Record_Exception("The refreshed primary key doesn't match the one in the record memory.", Doctrine::ERR_REFRESH);

        $this->_state    = Doctrine_Record::STATE_CLEAN;
        $this->_modified = array();

        $this->_table->getAttribute(Doctrine::ATTR_LISTENER)->onLoad($this);
    }
    /**
     * getTable
     * returns the table object for this record
     *
     * @return object Doctrine_Table        a Doctrine_Table object
     */
    public function getTable()
    {
        return $this->_table;
    }
    /**
     * getData
     * return all the internal data
     *
     * @return array                        an array containing all the properties
     */
    public function getData()
    {
        return $this->_data;
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

    public function rawGet($name)
    {
        if ( ! isset($this->_data[$name])) {
            throw new Doctrine_Record_Exception('Unknown property '. $name);
        }
        if ($this->_data[$name] === self::$_null)
            return null;

        return $this->_data[$name];
    }

    /**
     * load
     * loads all the unitialized properties from the database
     *
     * @return boolean
     */
    public function load()
    {
        // only load the data from database if the Doctrine_Record is in proxy state
        if ($this->_state == Doctrine_Record::STATE_PROXY) {
            $this->refresh();

            $this->_state = Doctrine_Record::STATE_CLEAN;

            return true;
        }
        return false;
    }
    /**
     * get
     * returns a value of a property or a related component
     *
     * @param mixed $name                       name of the property or related component
     * @param boolean $load                     whether or not to invoke the loading procedure
     * @throws Doctrine_Record_Exception        if trying to get a value of unknown property / related component
     * @return mixed
     */
    public function get($name, $load = true)
    {
        $value    = self::$_null;
        $lower    = strtolower($name);

        $lower    = $this->_table->getColumnName($lower);

        if (isset($this->_data[$lower])) {
            // check if the property is null (= it is the Doctrine_Null object located in self::$_null)
            if ($this->_data[$lower] === self::$_null) {
                $this->load();
            }

            if ($this->_data[$lower] === self::$_null) {
                $value = null;
            } else {
                $value = $this->_data[$lower];
            }
            return $value;
        }

        if (isset($this->_id[$lower])) {
            return $this->_id[$lower];
        }
        if ($name === $this->_table->getIdentifier()) {
            return null;
        }
        if (isset($this->_values[$lower])) {
            return $this->_values[$lower];
        }

        try {

            if ( ! isset($this->_references[$name]) && $load) {

                $rel = $this->_table->getRelation($name);

                $this->_references[$name] = $rel->fetchRelatedFor($this);
            }

        } catch(Doctrine_Table_Exception $e) { 
            throw new Doctrine_Record_Exception("Unknown property / related component '$name'.");
        }

        return $this->_references[$name];
    }
    /**
     * mapValue
     * This simple method is used for mapping values to $values property.
     * Usually this method is used internally by Doctrine for the mapping of
     * aggregate values.
     *
     * @param string $name                  the name of the mapped value
     * @param mixed $value                  mixed value to be mapped
     * @return void
     */
    public function mapValue($name, $value)
    {
        $name = strtolower($name);
        $this->_values[$name] = $value;
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
    public function set($name, $value, $load = true)
    {
        $lower = strtolower($name);

        $lower = $this->_table->getColumnName($lower);

        if (isset($this->_data[$lower])) {
            if ($value instanceof Doctrine_Record) {
                $id = $value->getIncremented();

                if ($id !== null) {
                    $value = $id;
                }
            }

            if ($load) {
                $old = $this->get($lower, false);
            } else {
                $old = $this->_data[$lower];
            }

            if ($old !== $value) {
                if ($value === null) {
                    $value = self::$_null;
                }

                $this->_data[$lower] = $value;
                $this->_modified[]   = $lower;
                switch ($this->_state) {
                    case Doctrine_Record::STATE_CLEAN:
                        $this->_state = Doctrine_Record::STATE_DIRTY;
                        break;
                    case Doctrine_Record::STATE_TCLEAN:
                        $this->_state = Doctrine_Record::STATE_TDIRTY;
                        break;
                }
            }
        } else {
            try {
                $this->coreSetRelated($name, $value);
            } catch(Doctrine_Table_Exception $e) {
                throw new Doctrine_Record_Exception("Unknown property / related component '$name'.");
            }
        }
    }

    public function coreSetRelated($name, $value)
    {
        $rel = $this->_table->getRelation($name);

        // one-to-many or one-to-one relation
        if ($rel instanceof Doctrine_Relation_ForeignKey ||
            $rel instanceof Doctrine_Relation_LocalKey) {
            if ( ! $rel->isOneToOne()) {
                // one-to-many relation found
                if ( ! ($value instanceof Doctrine_Collection)) {
                    throw new Doctrine_Record_Exception("Couldn't call Doctrine::set(), second argument should be an instance of Doctrine_Collection when setting one-to-many references.");
                }
                if (isset($this->_references[$name])) {
                    $this->_references[$name]->setData($value->getData());
                    return $this;
                }
            } else {
                if ($value !== self::$_null) {
                    // one-to-one relation found
                    if ( ! ($value instanceof Doctrine_Record)) {
                        throw new Doctrine_Record_Exception("Couldn't call Doctrine::set(), second argument should be an instance of Doctrine_Record or Doctrine_Null when setting one-to-one references.");
                    }
                    if ($rel instanceof Doctrine_Relation_LocalKey) {
                        $this->set($rel->getLocal(), $value, false);
                    } else {
                        $value->set($rel->getForeign(), $this, false);
                    }                            
                }
            }

        } elseif ($rel instanceof Doctrine_Relation_Association) {
            // join table relation found
            if ( ! ($value instanceof Doctrine_Collection)) {
                throw new Doctrine_Record_Exception("Couldn't call Doctrine::set(), second argument should be an instance of Doctrine_Collection when setting many-to-many references.");
            }
        }

        $this->_references[$name] = $value;
    }
    /**
     * contains
     *
     * @param string $name
     * @return boolean
     */
    public function contains($name)
    {
        $lower = strtolower($name);

        if (isset($this->_data[$lower])) {
            return true;
        }
        if (isset($this->_id[$lower])) {
            return true;
        }
        if (isset($this->_references[$name]) && 
            $this->_references[$name] !== self::$_null) {

            return true;
        }
        return false;
    }
    /**
     * @param string $name
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->_data[$name])) {
            $this->_data[$name] = array();
        }
        // todo: what to do with references ?
    }
    /**
     * applies the changes made to this object into database
     * this method is smart enough to know if any changes are made
     * and whether to use INSERT or UPDATE statement
     *
     * this method also saves the related components
     *
     * @param Doctrine_Connection $conn                 optional connection parameter
     * @return void
     */
    public function save(Doctrine_Connection $conn = null)
    {
        if ($conn === null) {
            $conn = $this->_table->getConnection();
        }
        $conn->unitOfWork->saveGraph($this);
    }
    /**
     * Tries to save the object and all its related components.
     * In contrast to Doctrine_Record::save(), this method does not
     * throw an exception when validation fails but returns TRUE on
     * success or FALSE on failure.
     * 
     * @param Doctrine_Connection $conn                 optional connection parameter
     * @return TRUE if the record was saved sucessfully without errors, FALSE otherwise.
     */
    public function trySave(Doctrine_Connection $conn = null) {
        try {
            $this->save($conn);
            return true;
        } catch (Doctrine_Validator_Exception $ignored) {
            return false;
        }
    }
    /**
     * replace
     * Execute a SQL REPLACE query. A REPLACE query is identical to a INSERT
     * query, except that if there is already a row in the table with the same
     * key field values, the REPLACE query just updates its values instead of
     * inserting a new row.
     *
     * The REPLACE type of query does not make part of the SQL standards. Since
     * practically only MySQL and SQLIte implement it natively, this type of
     * query isemulated through this method for other DBMS using standard types
     * of queries inside a transaction to assure the atomicity of the operation.
     *
     * @param Doctrine_Connection $conn             optional connection parameter
     * @throws Doctrine_Connection_Exception        if some of the key values was null
     * @throws Doctrine_Connection_Exception        if there were no key fields
     * @throws PDOException                         if something fails at PDO level
     * @return integer                              number of rows affected
     */
    public function replace(Doctrine_Connection $conn = null)
    {
        if ($conn === null) {
            $conn = $this->_table->getConnection();
        }

        return $conn->replace($this->_table->getTableName(), $this->getPrepared(), $this->id);
    }
    /**
     * returns an array of modified fields and associated values
     * @return array
     */
    public function getModified()
    {
        $a = array();

        foreach ($this->_modified as $k => $v) {
            $a[$v] = $this->_data[$v];
        }
        return $a;
    }
    /**
     * getPrepared
     *
     * returns an array of modified fields and values with data preparation
     * adds column aggregation inheritance and converts Records into primary key values
     *
     * @param array $array
     * @return array
     */
    public function getPrepared(array $array = array()) {
        $a = array();

        if (empty($array)) {
            $array = $this->_modified;
        }
        foreach ($array as $k => $v) {
            $type = $this->_table->getTypeOf($v);

            if ($this->_data[$v] === self::$_null) {
                $a[$v] = null;
                continue;
            }

            switch ($type) {
                case 'array':
                case 'object':
                    $a[$v] = serialize($this->_data[$v]);
                    break;
                case 'gzip':
                    $a[$v] = gzcompress($this->_data[$v],5);
                    break;
                case 'boolean':
                    $a[$v] = $this->getTable()->getConnection()->convertBooleans($this->_data[$v]);
                break;
                case 'enum':
                    $a[$v] = $this->_table->enumIndex($v,$this->_data[$v]);
                    break;
                default:
                    if ($this->_data[$v] instanceof Doctrine_Record) {
                        $this->_data[$v] = $this->_data[$v]->getIncremented();
                    }

                    $a[$v] = $this->_data[$v];
            }
        }
        $map = $this->_table->inheritanceMap;
        foreach ($map as $k => $v) {
            $old = $this->get($k, false);

            if ((string) $old !== (string) $v || $old === null) {
                $a[$k] = $v;
                $this->_data[$k] = $v;
            }
        }

        return $a;
    }
    /**
     * count
     * this class implements countable interface
     *
     * @return integer          the number of columns in this record
     */
    public function count()
    {
        return count($this->_data);
    }
    /**
     * alias for count()
     *
     * @return integer          the number of columns in this record
     */
    public function columnCount()
    {
        return $this->count();
    }
    /**
     * toArray
     * returns the record as an array
     *
     * @return array
     */
    public function toArray()
    {
        $a = array();

        foreach ($this as $column => $value) {
            $a[$column] = $value;
        }
        if ($this->_table->getIdentifierType() ==  Doctrine::IDENTIFIER_AUTOINC) {
            $i      = $this->_table->getIdentifier();
            $a[$i]  = $this->getIncremented();
        }
        return array_merge($a, $this->_values);
    }
    /**
     * exists
     * returns true if this record is persistent, otherwise false
     *
     * @return boolean
     */
    public function exists()
    {
        return ($this->_state !== Doctrine_Record::STATE_TCLEAN &&
                $this->_state !== Doctrine_Record::STATE_TDIRTY);
    }
    /**
     * isModified
     * returns true if this record was modified, otherwise false
     *
     * @return boolean
     */
    public function isModified()
    {
        return ($this->_state === Doctrine_Record::STATE_DIRTY ||
                $this->_state === Doctrine_Record::STATE_TDIRTY);
    }
    /**
     * method for checking existence of properties and Doctrine_Record references
     * @param mixed $name               name of the property or reference
     * @return boolean
     */
    public function hasRelation($name)
    {
        if (isset($this->_data[$name]) || isset($this->_id[$name])) {
            return true;
        }
        return $this->_table->hasRelation($name);
    }
    /**
     * getIterator
     * @return Doctrine_Record_Iterator     a Doctrine_Record_Iterator that iterates through the data
     */
    public function getIterator()
    {
        return new Doctrine_Record_Iterator($this);
    }
    /**
     * deletes this data access object and all the related composites
     * this operation is isolated by a transaction
     *
     * this event can be listened by the onPreDelete and onDelete listeners
     *
     * @return boolean      true on success, false on failure
     */
    public function delete(Doctrine_Connection $conn = null)
    {
        if ($conn == null) {
            $conn = $this->_table->getConnection();
        }
        return $conn->unitOfWork->delete($this);
    }
    /**
     * copy
     * returns a copy of this object
     *
     * @return Doctrine_Record
     */
    public function copy()
    {
        $ret = $this->_table->create($this->_data);
        $modified = array();
        foreach ($this->_data as $key => $val) {
            if ( ! ($val instanceof Doctrine_Null)) {
                $ret->_modified[] = $key;
            }
        }
        return $ret;
    }
    /**
     * copyDeep
     * returns a copy of this object and all its related objects
     *
     * @return Doctrine_Record
     */
    public function copyDeep(){
        $copy = $this->copy();

        foreach ($this->_references as $key => $value) {
            if ($value instanceof Doctrine_Collection) {
                foreach ($value as $record) {
                    $copy->{$key}[] = $record->copyDeep();
                }
            } else {
                $copy->set($key, $value->copyDeep());
            }
        }
        return $copy;
    }
    
    /**
     * assignIdentifier
     *
     * @param integer $id
     * @return void
     */
    public function assignIdentifier($id = false)
    {
        if ($id === false) {
            $this->_id       = array();
            $this->_data     = $this->_filter->cleanData($this->_data);
            $this->_state    = Doctrine_Record::STATE_TCLEAN;
            $this->_modified = array();
        } elseif ($id === true) {
            $this->prepareIdentifiers(false);
            $this->_state    = Doctrine_Record::STATE_CLEAN;
            $this->_modified = array();
        } else {
            $name            = $this->_table->getIdentifier();

            $this->_id[$name] = $id;
            $this->_state     = Doctrine_Record::STATE_CLEAN;
            $this->_modified  = array();
        }
    }
    /**
     * returns the primary keys of this object
     *
     * @return array
     */
    final public function obtainIdentifier()
    {
        return $this->_id;
    }
    /**
     * returns the value of autoincremented primary key of this object (if any)
     *
     * @return integer
     */
    final public function getIncremented()
    {
        $id = current($this->_id);
        if ($id === false) {
            return null;
        }

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
    public function getLast()
    {
        return $this;
    }
    /**
     * hasRefence
     * @param string $name
     * @return boolean
     */
    public function hasReference($name)
    {
        return isset($this->_references[$name]);
    }
    /**
     * obtainReference
     *
     * @param string $name
     * @throws Doctrine_Record_Exception        if trying to get an unknown related component
     */
    public function obtainReference($name)
    {
        if (isset($this->_references[$name])) {
            return $this->_references[$name];
        }
        throw new Doctrine_Record_Exception("Unknown reference $name");
    }
    /**
     * getReferences
     * @return array    all references
     */
    public function getReferences()
    {
        return $this->_references;
    }
    /**
     * setRelated
     *
     * @param string $alias
     * @param Doctrine_Access $coll
     */
    final public function setRelated($alias, Doctrine_Access $coll)
    {
        $this->_references[$alias] = $coll;
    }
    /**
     * loadReference
     * loads a related component
     *
     * @throws Doctrine_Table_Exception             if trying to load an unknown related component
     * @param string $name
     * @return void
     */
    public function loadReference($name)
    {
        $rel = $this->_table->getRelation($name);
        $this->_references[$name] = $rel->fetchRelatedFor($this);
    }
    /**
     * ownsOne
     * binds One-to-One composite relation
     *
     * @param string $componentName     the name of the related component
     * @param string $options           relation options
     * @see Doctrine_Relation::_$definition
     * @return Doctrine_Record          this object
     */
    public function ownsOne()
    {
        $this->_table->bind(func_get_args(), Doctrine_Relation::ONE_COMPOSITE);
        
        return $this;
    }
    /**
     * ownsMany
     * binds One-to-Many / Many-to-Many composite relation
     *
     * @param string $componentName     the name of the related component
     * @param string $options           relation options
     * @see Doctrine_Relation::_$definition
     * @return Doctrine_Record          this object
     */
    public function ownsMany()
    {
        $this->_table->bind(func_get_args(), Doctrine_Relation::MANY_COMPOSITE);
        return $this;
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
        $this->_table->bind(func_get_args(), Doctrine_Relation::ONE_AGGREGATE);

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
        $this->_table->bind(func_get_args(), Doctrine_Relation::MANY_AGGREGATE);

        return $this;
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
    public function hasColumn($name, $type, $length = 2147483647, $options = "")
    {
        $this->_table->setColumn($name, $type, $length, $options);
    }
    public function hasColumns(array $definitions)
    {
        foreach ($definitions as $name => $options) {
            $this->hasColumn($name, $options['type'], $options['length'], $options);
        }
    }
    /**
     * merge
     * merges this record with an array of values
     *
     * @param array $values
     * @return void
     */
    public function merge(array $values)
    {
        foreach ($this->_table->getColumnNames() as $value) {
            try {
                if (isset($values[$value])) {
                    $this->set($value, $values[$value]);
                }
            } catch(Exception $e) {
                // silence all exceptions
            }
        }
    }
    public function setAttribute($attr, $value)
    {
        $this->_table->setAttribute($attr, $value);
    }
    public function setTableName($tableName)
    {
        $this->_table->setOption('tableName', $tableName);
    }
    public function setInheritanceMap($map)
    {
        $this->_table->setOption('inheritanceMap', $map);
    }
    public function setEnumValues($column, $values)
    {
        $this->_table->setEnumValues($column, $values);
    }
    /**
     * attribute
     * sets or retrieves an option
     *
     * @see Doctrine::ATTR_* constants   availible attributes
     * @param mixed $attr
     * @param mixed $value
     * @return mixed
     */
    public function attribute($attr, $value)
    {
        if ($value == null) {
            if (is_array($attr)) {
                foreach ($attr as $k => $v) {
                    $this->_table->setAttribute($k, $v);
                }
            } else {
                return $this->_table->getAttribute($attr);
            }
        } else {
            $this->_table->setAttribute($attr, $value);
        }    
    }
    /**
     * option
     * sets or retrieves an option
     *
     * @see Doctrine_Table::$options    availible options
     * @param mixed $name               the name of the option
     * @param mixed $value              options value
     * @return mixed
     */
    public function option($name, $value = null)
    {
        if ($value == null) {
            if (is_array($name)) {
                foreach ($name as $k => $v) {
                    $this->_table->setOption($k, $v);
                }
            } else {
                return $this->_table->getOption($name);
            }
        } else {
            $this->_table->setOption($name, $value);
        }
    }
    /**
     * index
     * defines or retrieves an index
     * if the second parameter is set this method defines an index
     * if not this method retrieves index named $name
     *
     * @param string $name              the name of the index
     * @param array $definition         the definition array
     * @return mixed
     */
    public function index($name, array $definition = array())
    {
        if ( ! $definition) {
            return $this->_table->getIndex($name);
        } else {
            return $this->_table->addIndex($name, $definition);
        }
    }
    /**
     * addListener
     *
     * @param Doctrine_EventListener_Interface|Doctrine_Overloadable $listener
     * @return Doctrine_Record
     */
    public function addListener($listener, $name = null)
    {
        $this->_table->addListener($listener, $name = null);
        return $this;
    }
    /**
     * getListener
     *
     * @return Doctrine_EventListener_Interface|Doctrine_Overloadable
     */
    public function getListener()
    {
        return $this->_table->getListener();
    }
    /**
     * setListener
     *
     * @param Doctrine_EventListener_Interface|Doctrine_Overloadable $listener
     * @return Doctrine_Record
     */
    public function setListener($listener)
    {
        $this->_table->setListener($listener);
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
    public function call($callback, $column)
    {
        $args = func_get_args();
        array_shift($args);

        if (isset($args[0])) {
            $column = $args[0];
            $args[0] = $this->get($column);

            $newvalue = call_user_func_array($callback, $args);

            $this->_data[$column] = $newvalue;
        }
        return $this;
    }
    /**
     * getter for node assciated with this record
     *
     * @return mixed if tree returns Doctrine_Node otherwise returns false
     */    
    public function getNode() 
    {
        if ( ! $this->_table->isTree()) {
            return false;
        }

        if ( ! isset($this->_node)) {
            $this->_node = Doctrine_Node::factory($this,
                                              $this->getTable()->getOption('treeImpl'),
                                              $this->getTable()->getOption('treeOptions')
                                              );
        }
        
        return $this->_node;
    }
    public function revert($version)
    {
        $data = $this->_table->getAuditLog()->getVersion($this, $version);
        
        $this->_data = $data[0];
    }
    /**
     * loadTemplate
     *
     * @param string $template
     */
    public function loadTemplate($template)
    {
        return $this;
    }
    /**
     * used to delete node from tree - MUST BE USE TO DELETE RECORD IF TABLE ACTS AS TREE
     *
     */    
    public function deleteNode() {
        $this->getNode()->delete();
    }
    public function toString()
    {
        return Doctrine::dump(get_object_vars($this));
    }
    /**
     * returns a string representation of this object
     */
    public function __toString()
    {
        return (string) $this->_oid;
    }
}
