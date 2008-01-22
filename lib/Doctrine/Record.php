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
Doctrine::autoload('Doctrine_Record_Abstract');
/**
 * Doctrine_Record
 * All record classes should inherit this super class
 *
 * @package     Doctrine
 * @subpackage  Record
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
abstract class Doctrine_Record extends Doctrine_Record_Abstract implements Countable, IteratorAggregate, Serializable
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
     * a Doctrine_Record is in transient dirty state when it is created
     * and some of its fields are modified but it is NOT yet persisted into database
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
     * LOCKED STATE
     * a Doctrine_Record is temporarily locked during deletes and saves
     *
     * This state is used internally to ensure that circular deletes
     * and saves will not cause infinite loops
     */
    const STATE_LOCKED     = 6;

    /**
     *
     */
    protected $_domainClassName;

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
     * @var array $_modified                an array containing field names that have been modified
     * @todo Better name? $_modifiedFields?
     */
    protected $_modified     = array();

    /**
     * @var Doctrine_Validator_ErrorStack   error stack object
     */
    protected $_errorStack;

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
    public function __construct($mapper = null, $isNewEntry = false, array $data = array())
    {
        //echo get_class($this) . "<br />";
        if (isset($mapper) && $mapper instanceof Doctrine_Table) {
            //echo "one<br />";
            $this->_table = $mapper;
            //$this->_mapper = Doctrine_Manager::getInstance()->getMapper(get_class($this));
            $exists = ! $isNewEntry;
            return;
        } else if (isset($mapper) && $mapper instanceof Doctrine_Mapper_Abstract) {
            //echo "two<br />";
            $class = get_class($this);
            $this->_mapper = Doctrine_Manager::getInstance()->getMapper($class);
            $this->_table = $this->_mapper->getTable();
            $exists = ! $isNewEntry;
        } else {
            //echo "three<br />";
            $this->_mapper = Doctrine_Manager::getInstance()->getMapper(get_class($this));
            $this->_table = $this->_mapper->getTable();
            $exists = false;
        }

        $this->_domainClassName = get_class($this);
        $this->_oid = self::$_index;
        
        self::$_index++;

        // get the data array
        $this->_data = $data;

        // get the column count
        $count = count($this->_data);
        
        $this->_values = $this->cleanData($this->_data);

        $this->prepareIdentifiers($exists);
        
        if ( ! $exists) {
            if ($count > count($this->_values)) {
                $this->_state = Doctrine_Record::STATE_TDIRTY;
            } else {
                $this->_state = Doctrine_Record::STATE_TCLEAN;
            }

            // set the default values for this record
            $this->assignDefaultValues();
        } else {
            $this->_state = Doctrine_Record::STATE_CLEAN;
            // @TODO table->getColumnCount is not correct in CTI
            if ($count < $this->_table->getColumnCount()) {
                $this->_state  = Doctrine_Record::STATE_PROXY;
            }
        }

        $this->_errorStack = new Doctrine_Validator_ErrorStack(get_class($this));
        $repository = $this->_mapper->getRepository();
        $repository->add($this);

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
     * Empty template method to provide concrete Record classes with the possibility
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
     * @return boolean  whether or not this record is valid
     */
    public function isValid()
    {
        if ( ! $this->_mapper->getAttribute(Doctrine::ATTR_VALIDATE)) {
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
        if ($stack !== null) {
            if ( ! ($stack instanceof Doctrine_Validator_ErrorStack)) {
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
                continue;
            }

            if ($value === self::$_null || $overwrite) {
                $this->_data[$column] = $default;
                $this->_modified[]    = $column;
                $this->_state = Doctrine_Record::STATE_TDIRTY;
            }
        }
    }

    /**
     * cleanData
     * leaves the $data array only with values whose key is a field inside this
     * record and returns the values that were removed from $data.  Also converts
     * any values of 'null' to objects of type Doctrine_Null.
     *
     * @param array $data       data array to be cleaned
     * @return array $tmp       values cleaned from data
     */
    public function cleanData(&$data)
    {
        $tmp = $data;
        $data = array();

        $fieldNames = $this->_mapper->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            if (isset($tmp[$fieldName])) {
                $data[$fieldName] = $tmp[$fieldName];
            } else if (array_key_exists($fieldName, $tmp)) {
                $data[$fieldName] = self::$_null;
            } else if (!isset($this->_data[$fieldName])) {
                $data[$fieldName] = self::$_null;
            }
            unset($tmp[$fieldName]);
        }

        return $tmp;
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
        $this->_values = array_merge($this->_values, $this->cleanData($data));
        $this->_data   = array_merge($this->_data, $data);
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
            case Doctrine::IDENTIFIER_NATURAL:
                $name = $this->_table->getIdentifier();
                if (is_array($name)) {
                    $name = $name[0];
                }
                if ($exists) {
                    if (isset($this->_data[$name]) && $this->_data[$name] !== self::$_null) {
                        $this->_id[$name] = $this->_data[$name];
                    }
                }
                break;
            case Doctrine::IDENTIFIER_COMPOSITE:
                $names = $this->_table->getIdentifier();

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
     * Serializes the entity.
     * This method is automatically called when the entity is serialized.
     *
     * Part of the implementation of the Serializable interface.
     *
     * @return array
     */
    public function serialize()
    {
        $event = new Doctrine_Event($this, Doctrine_Event::RECORD_SERIALIZE);
        $this->preSerialize($event);

        $vars = get_object_vars($this);

        unset($vars['_references']);
        unset($vars['_mapper']);
        unset($vars['_errorStack']);
        unset($vars['_filter']);
        unset($vars['_node']);

        $name = $this->_table->getIdentifier();
        $this->_data = array_merge($this->_data, $this->_id);

        foreach ($this->_data as $k => $v) {
            if ($v instanceof Doctrine_Record && $this->_table->getTypeOf($k) != 'object') {
                unset($vars['_data'][$k]);
            } else if ($v === self::$_null) {
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
     * Reconstructs the entity from it's serialized form.
     * This method is automatically called everytime the entity is unserialized.
     *
     * @param string $serialized                Doctrine_Record as serialized string
     * @throws Doctrine_Record_Exception        if the cleanData operation fails somehow
     * @return void
     */
    public function unserialize($serialized)
    {
        $event = new Doctrine_Event($this, Doctrine_Event::RECORD_UNSERIALIZE);

        $this->preUnserialize($event);

        $manager    = Doctrine_Manager::getInstance();
        $connection = $manager->getConnectionForComponent(get_class($this));

        $this->_oid = self::$_index;
        self::$_index++;

        $this->_mapper = $connection->getMapper(get_class($this));  

        $array = unserialize($serialized);

        foreach($array as $k => $v) {
            $this->$k = $v;
        }
        
        $this->_table = $this->_mapper->getTable();

        foreach ($this->_data as $k => $v) {
            switch ($this->_table->getTypeOf($k)) {
                case 'array':
                case 'object':
                    $this->_data[$k] = unserialize($this->_data[$k]);
                    break;
                case 'gzip':
                   $this->_data[$k] = gzuncompress($this->_data[$k]);
                    break;
                case 'enum':
                    $this->_data[$k] = $this->_table->enumValue($k, $this->_data[$k]);
                    break;

            }
        }

        $this->_mapper->getRepository()->add($this);
        $this->cleanData($this->_data);
        $this->prepareIdentifiers($this->exists());
        
        $this->postUnserialize($event);
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
        } else if (is_string($state)) {
            $upper = strtoupper($state);

            $const = 'Doctrine_Record::STATE_' . $upper;
            if (defined($const)) {
                $this->_state = constant($const);
            } else {
                $err = true;
            }
        }

        if ($this->_state === Doctrine_Record::STATE_TCLEAN ||
                $this->_state === Doctrine_Record::STATE_CLEAN) {
            $this->_modified = array();
        }

        if ($err) {
            throw new Doctrine_Record_Exception("Unknown record state '$state'.");
        }
    }

    /**
     * refresh
     * refresh internal data from the database
     *
     * @param bool $deep                        If true, fetch also current relations. Caution: this deletes
     *                                          any aggregated values you may have queried beforee
     *
     * @throws Doctrine_Record_Exception        When the refresh operation fails (when the database row
     *                                          this record represents does not exist anymore)
     * @return boolean
     */
    public function refresh($deep = false)
    {
        $id = $this->identifier();
        if ( ! is_array($id)) {
            $id = array($id);
        }
        if (empty($id)) {
            return false;
        }
        $id = array_values($id);

        if ($deep) {
            
            $query = $this->_mapper->createQuery();
            foreach (array_keys($this->_references) as $name) {
                $query->leftJoin(get_class($this) . '.' . $name);
            }
            $query->where(implode(' = ? AND ', $this->_table->getIdentifierColumnNames()) . ' = ?');
            $record = $query->fetchOne($id);
        } else {
            // Use FETCH_ARRAY to avoid clearing object relations
            $record = $this->_mapper->find($id, Doctrine::HYDRATE_ARRAY);
            if ($record) {
                $this->hydrate($record);
            }
        }

        if ($record === false) {
            throw new Doctrine_Record_Exception('Failed to refresh. Record does not exist.');
        }

        $this->_modified = array();

        $this->prepareIdentifiers();

        $this->_state = Doctrine_Record::STATE_CLEAN;

        return $this;
    }

    /**
     * refresh
     * refres data of related objects from the database
     *
     * @param string $name              name of a related component.
     *                                  if set, this method only refreshes the specified related component
     *
     * @return Doctrine_Record          this object
     */
    public function refreshRelated($name = null)
    {
        if (is_null($name)) {
            foreach ($this->_table->getRelations() as $rel) {
                $this->_references[$rel->getAlias()] = $rel->fetchRelatedFor($this);
            }
        } else {
            $rel = $this->_table->getRelation($name);
            $this->_references[$name] = $rel->fetchRelatedFor($this);
        }
    }

    /**
     * clearRelated
     * unsets all the relationships this object has
     *
     * (references to related objects still remain on Table objects)
     */
    public function clearRelated()
    {
        $this->_references = array();
    }

    /**
     * getTable
     * returns the table object for this record
     *
     * @return Doctrine_Table        a Doctrine_Table object
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
    
    public function getValues()
    {
        return $this->_values;
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
    public function rawGet($fieldName)
    {
        if ( ! isset($this->_data[$fieldName])) {
            throw new Doctrine_Record_Exception('Unknown property '. $fieldName);
        }
        if ($this->_data[$fieldName] === self::$_null) {
            return null;
        }

        return $this->_data[$fieldName];
    }

    /**
     * load
     * loads all the uninitialized properties from the database
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
    public function get($fieldName, $load = true)
    {
        $value = self::$_null;

        if (isset($this->_data[$fieldName])) {
            // check if the value is the Doctrine_Null object located in self::$_null)
            if ($this->_data[$fieldName] === self::$_null && $load) {
                $this->load();
            }

            if ($this->_data[$fieldName] === self::$_null) {
                $value = null;
            } else {
                $value = $this->_data[$fieldName];
            }
            return $value;
        }

        if (isset($this->_values[$fieldName])) {
            return $this->_values[$fieldName];
        }

        try {
            if ( ! isset($this->_references[$fieldName]) && $load) {
                $rel = $this->_table->getRelation($fieldName);
                $this->_references[$fieldName] = $rel->fetchRelatedFor($this);
            }
            return $this->_references[$fieldName];
        } catch (Doctrine_Table_Exception $e) {
            //echo $e->getTraceAsString();
            //echo "<br /><br />";
            foreach ($this->_table->getFilters() as $filter) {
                if (($value = $filter->filterGet($this, $fieldName, $value)) !== null) {
                    return $value;
                }
            }
        }
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
    public function set($fieldName, $value, $load = true)
    {
        if (isset($this->_data[$fieldName])) {            
            if ($value instanceof Doctrine_Record) {
                $type = $this->_table->getTypeOf($fieldName);

                $id = $value->getIncremented();

                if ($id !== null && $type !== 'object') {
                    $value = $id;
                }
            }

            if ($load) {
                $old = $this->get($fieldName, $load);
            } else {
                $old = $this->_data[$fieldName];
            }

            if ($old !== $value) {
                if ($value === null) {
                    $value = self::$_null;
                }

                $this->_data[$fieldName] = $value;
                $this->_modified[] = $fieldName;
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
                $this->_coreSetRelated($fieldName, $value);
            } catch (Doctrine_Table_Exception $e) {
                foreach ($this->_table->getFilters() as $filter) {
                    if (($value = $filter->filterSet($this, $fieldName, $value)) !== null) {
                        return $value;
                    }
                }
            }
        }
    }

    /**
     * DESCRIBE WHAT THIS METHOD DOES, PLEASE!
     * @todo Refactor. What about composite keys?
     */
    private function _coreSetRelated($name, $value)
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
                    $relatedTable = $value->getTable();
                    $foreignFieldName = $rel->getForeignFieldName();
                    $localFieldName = $rel->getLocalFieldName();

                    // one-to-one relation found
                    if ( ! ($value instanceof Doctrine_Record)) {
                        throw new Doctrine_Record_Exception("Couldn't call Doctrine::set(), second argument should be an instance of Doctrine_Record or Doctrine_Null when setting one-to-one references.");
                    }
                    if ($rel instanceof Doctrine_Relation_LocalKey) {
                        if ( ! empty($foreignFieldName) && $foreignFieldName != $value->getTable()->getIdentifier()) {
                            $this->set($localFieldName, $value->rawGet($foreignFieldName), false);
                        } else {
                            $this->set($localFieldName, $value, false);
                        }
                    } else {
                        $value->set($foreignFieldName, $this, false);
                    }
                }
            }
        } else if ($rel instanceof Doctrine_Relation_Association) {
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
    public function contains($fieldName)
    {
        if (isset($this->_data[$fieldName])) {
            return true;
        }
        if (isset($this->_id[$fieldName])) {
            return true;
        }
        if (isset($this->_values[$fieldName])) {
            return true;
        }
        if (isset($this->_references[$fieldName]) &&
            $this->_references[$fieldName] !== self::$_null) {

            return true;
        }
        return false;
    }

    /**
     * @param string $name
     * @return void
     */
    public function __unset($fieldName)
    {
        if (isset($this->_data[$fieldName])) {
            $this->_data[$fieldName] = array();
        } else if (isset($this->_references[$fieldName])) {
            if ($this->_references[$fieldName] instanceof Doctrine_Record) {
                // todo: delete related record when saving $this
                $this->_references[$fieldName] = self::$_null;
            } else if ($this->_references[$fieldName] instanceof Doctrine_Collection) {
                $this->_references[$fieldName]->setData(array());
            }
        }
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
        $this->_mapper->save($this, $conn);
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
     * @throws Doctrine_Connection_Exception        if something fails at database level
     * @return integer                              number of rows affected
     */
    public function replace(Doctrine_Connection $conn = null)
    {
        if ($conn === null) {
            $conn = $this->_mapper->getConnection();
        }

        return $conn->replace($this->_table, $this->getPrepared(), $this->_id);
    }

    /**
     * returns an array of modified fields and associated values
     * @return array
     * @deprecated
     */
    public function getModified()
    {
        return $this->getModifiedFields();
    }
    
    /**
     * returns an array of modified fields and associated values.
     *
     * @return array
     */
    public function getModifiedFields()
    {
        $a = array();
        foreach ($this->_modified as $k => $v) {
            $a[$v] = $this->_data[$v];
        }
        return $a;
    }

    /**
     * REDUNDANT?
     */
    /*public function modifiedFields()
    {
        $a = array();

        foreach ($this->_modified as $k => $v) {
            $a[$v] = $this->_data[$v];
        }
        return $a;
    }*/

    /**
     * getPrepared
     *
     * returns an array of modified fields and values with data preparation
     * adds column aggregation inheritance and converts Records into primary key values
     *
     * @param array $array
     * @return array
     * @todo What about a little bit more expressive name? getPreparedData?
     */
    public function getPrepared(array $array = array())
    {
        $a = array();

        if (empty($array)) {
            $modifiedFields = $this->_modified;
        }

        foreach ($modifiedFields as $field) {
            $type = $this->_table->getTypeOf($field);

            if ($this->_data[$field] === self::$_null) {
                $a[$field] = null;
                continue;
            }

            switch ($type) {
                case 'array':
                case 'object':
                    $a[$field] = serialize($this->_data[$field]);
                    break;
                case 'gzip':
                    $a[$field] = gzcompress($this->_data[$field],5);
                    break;
                case 'boolean':
                    $a[$field] = $this->getTable()->getConnection()->convertBooleans($this->_data[$field]);
                break;
                case 'enum':
                    $a[$field] = $this->_table->enumIndex($field, $this->_data[$field]);
                    break;
                default:
                    if ($this->_data[$field] instanceof Doctrine_Record) {
                        $this->_data[$field] = $this->_data[$field]->getIncremented();
                    }
                    /** TODO:
                    if ($this->_data[$v] === null) {
                        throw new Doctrine_Record_Exception('Unexpected null value.');
                    }
                    */

                    $a[$field] = $this->_data[$field];
            }
        }
        //$map = $this->_table->getOption('inheritanceMap');
        $map = $this->_mapper->getDiscriminatorColumn();
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
     * Implementation of the Countable interface.
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
     * @param boolean $deep - Return also the relations
     * @return array
     */
    public function toArray($deep = true, $prefixKey = false)
    {
        $a = array();

        foreach ($this as $column => $value) {
            if ($value === self::$_null || is_object($value)) {
                $value = null;
            }
            $a[$column] = $value;
        }

        if ($this->_table->getIdentifierType() ==  Doctrine::IDENTIFIER_AUTOINC) {
            $i = $this->_table->getIdentifier();
            $a[$i] = $this->getIncremented();
        }

        if ($deep) {
            foreach ($this->_references as $key => $relation) {
                if ( ! $relation instanceof Doctrine_Null) {
                    $a[$key] = $relation->toArray($deep, $prefixKey);
                }
            }
        }

        // [FIX] Prevent mapped Doctrine_Records from being displayed fully
        foreach ($this->_values as $key => $value) {
            if ($value instanceof Doctrine_Record) {
                $a[$key] = $value->toArray($deep, $prefixKey);
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }

    /**
     * merge
     *
     * merges this record with an array of values
     * or with another existing instance of this object
     *
     * @param  mixed $data Data to merge. Either another instance of this model or an array
     * @param  bool  $deep Bool value for whether or not to merge the data deep
     * @return void
     */
    public function merge($data, $deep = true)
    {
        if ($data instanceof $this) {
            $array = $data->toArray($deep);
        } else if (is_array($data)) {
            $array = $data;
        } else {
            $array = array();
        }

        return $this->fromArray($array, $deep);
    }

    /**
     * fromArray
     *
     * @param   string $array
     * @param   bool  $deep Bool value for whether or not to merge the data deep
     * @return  void
     */
    public function fromArray($array, $deep = true)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if ($this->getTable()->hasRelation($key) && $deep) {
                    $this->$key->fromArray($value, $deep);
                } else if ($this->getTable()->hasField($key)) {
                    $this->set($key, $value);
                }
            }
        }
    }

    /**
     * synchronizeWithArray
     * synchronizes a Doctrine_Record and its relations with data from an array
     *
     * it expects an array representation of a Doctrine_Record similar to the return
     * value of the toArray() method. If the array contains relations it will create
     * those that don't exist, update the ones that do, and delete the ones missing
     * on the array but available on the Doctrine_Record
     *
     * @param array $array representation of a Doctrine_Record
     */
    public function synchronizeWithArray(array $array)
    {
        foreach ($array as $key => $value) {
            if ($this->getTable()->hasRelation($key)) {
                $this->get($key)->synchronizeWithArray($value);
            } else if ($this->getTable()->hasField($key)) {
                $this->set($key, $value);
            }
        }
        // eliminate relationships missing in the $array
        foreach ($this->_references as $name => $obj) {
            if ( ! isset($array[$name])) {
                unset($this->$name);
            }
        }
    }

    /**
     * exportTo
     *
     * @param string $type
     * @param string $deep
     * @return void
     */
    public function exportTo($type, $deep = true)
    {
        if ($type == 'array') {
            return $this->toArray($deep);
        } else {
            return Doctrine_Parser::dump($this->toArray($deep, true), $type);
        }
    }

    /**
     * importFrom
     *
     * @param string $type
     * @param string $data
     * @return void
     * @author Jonathan H. Wage
     */
    public function importFrom($type, $data)
    {
        if ($type == 'array') {
            return $this->fromArray($data);
        } else {
            return $this->fromArray(Doctrine_Parser::load($data, $type));
        }
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
    public function hasRelation($fieldName)
    {
        if (isset($this->_data[$fieldName]) || isset($this->_id[$fieldName])) {
            return true;
        }
        return $this->_table->hasRelation($fieldName);
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
        return $this->_mapper->delete($this, $conn);
    }

    /**
     * copy
     * returns a copy of this object
     *
     * @return Doctrine_Record
     */
    public function copy($deep = true)
    {
        $data = $this->_data;

        if ($this->_table->getIdentifierType() === Doctrine::IDENTIFIER_AUTOINC) {
            $id = $this->_table->getIdentifier();

            unset($data[$id]);
        }

        $ret = $this->_mapper->create($data);
        $modified = array();

        foreach ($data as $key => $val) {
            if ( ! ($val instanceof Doctrine_Null)) {
                $ret->_modified[] = $key;
            }
        }

        if ($deep) {
            foreach ($this->_references as $key => $value) {
                if ($value instanceof Doctrine_Collection) {
                    foreach ($value as $record) {
                        $rt->{$key}[] = $record->copy($deep);
                    }
                } else {
                    $rt->set($key, $value->copy($deep));
                }
            }
        }

        return $ret;
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
            $this->_data     = $this->cleanData($this->_data);
            $this->_state    = Doctrine_Record::STATE_TCLEAN;
            $this->_modified = array();
        } else if ($id === true) {
            $this->prepareIdentifiers(true);
            $this->_state    = Doctrine_Record::STATE_CLEAN;
            $this->_modified = array();
        } else {
            if (is_array($id)) {
                foreach ($id as $fieldName => $value) {
                    $this->_id[$fieldName] = $value;
                    $this->_data[$fieldName] = $value;
                }
            } else {
                $name = $this->_table->getIdentifier();
                $this->_id[$name] = $id;
                $this->_data[$name] = $id;
            }
            $this->_state = Doctrine_Record::STATE_CLEAN;
            $this->_modified = array();
        }
    }

    /**
     * returns the primary keys of this object
     *
     * @return array
     */
    public function identifier()
    {
        return $this->_id;
    }

    /**
     * returns the value of autoincremented primary key of this object (if any)
     *
     * @return integer
     * @todo Better name?
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
     * reference
     *
     * @param string $name
     */
    public function reference($name)
    {
        if (isset($this->_references[$name])) {
            return $this->_references[$name];
        }
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
            $fieldName = $args[0];
            $args[0] = $this->get($fieldName);

            $newvalue = call_user_func_array($callback, $args);

            $this->_data[$fieldName] = $newvalue;
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
                    $this->getTable()->getOption('treeOptions'));
        }

        return $this->_node;
    }
    
    /**
     * revert
     * reverts this record to given version, this method only works if versioning plugin
     * is enabled
     *
     * @throws Doctrine_Record_Exception    if given version does not exist
     * @param integer $version      an integer > 1
     * @return Doctrine_Record      this object
     */
    public function revert($version)
    {
        $data = $this->_table
                ->getTemplate('Doctrine_Template_Versionable')
                ->getAuditLog()
                ->getVersion($this, $version);

        if ( ! isset($data[0])) {
            throw new Doctrine_Record_Exception('Version ' . $version . ' does not exist!');
        }

        $this->_data = $data[0];

        return $this;
    }
    
    public function unshiftFilter(Doctrine_Record_Filter $filter)
    {
        return $this->_table->unshiftFilter($filter);
    }
    
    /**
     * unlink
     * removes links from this record to given records
     * if no ids are given, it removes all links
     *
     * @param string $alias     related component alias
     * @param array $ids        the identifiers of the related records
     * @return Doctrine_Record  this object
     */
    public function unlink($alias, $ids = array())
    {
        $ids = (array) $ids;

        $q = new Doctrine_Query();

        $rel = $this->getTable()->getRelation($alias);

        if ($rel instanceof Doctrine_Relation_Association) {
            $q->delete()
              ->from($rel->getAssociationTable()->getComponentName())
              ->where($rel->getLocal() . ' = ?', array_values($this->identifier()));

            if (count($ids) > 0) {
                $q->whereIn($rel->getForeign(), $ids);
            }

            $q->execute();

        } else if ($rel instanceof Doctrine_Relation_ForeignKey) {
            $q->update($rel->getTable()->getComponentName())
              ->set($rel->getForeign(), '?', array(null))
              ->addWhere($rel->getForeign() . ' = ?', array_values($this->identifier()));

            if (count($ids) > 0) {
                $q->whereIn($rel->getTable()->getIdentifier(), $ids);
            }

            $q->execute();
        }
        if (isset($this->_references[$alias])) {
            foreach ($this->_references[$alias] as $k => $record) {
                
                if (in_array(current($record->identifier()), $ids)) {
                    $this->_references[$alias]->remove($k);
                }
                
            }
            
            $this->_references[$alias]->takeSnapshot();
        }
        return $this;
    }


    /**
     * link
     * creates links from this record to given records
     *
     * @param string $alias     related component alias
     * @param array $ids        the identifiers of the related records
     * @return Doctrine_Record  this object
     */
    public function link($alias, array $ids)
    {
        if ( ! count($ids)) {
            return $this;
        }

        $identifier = array_values($this->identifier());
        $identifier = array_shift($identifier);

        $rel = $this->getTable()->getRelation($alias);

        if ($rel instanceof Doctrine_Relation_Association) {

            $modelClassName = $rel->getAssociationTable()->getComponentName();
            $localFieldName = $rel->getLocalFieldName();
            $localFieldDef  = $rel->getAssociationTable()->getColumnDefinition($localFieldName);
            if ($localFieldDef['type'] == 'integer') {
                $identifier = (integer) $identifier;
            }
            $foreignFieldName = $rel->getForeignFieldName();
            $foreignFieldDef  = $rel->getAssociationTable()->getColumnDefinition($foreignFieldName);
            if ($foreignFieldDef['type'] == 'integer') {
                for ($i = 0; $i < count($ids); $i++) {
                    $ids[$i] = (integer) $ids[$i];
                }
            }
            foreach ($ids as $id) {
                $record = new $modelClassName;
                $record[$localFieldName]   = $identifier;
                $record[$foreignFieldName] = $id;
                $record->save();
            }

        } else if ($rel instanceof Doctrine_Relation_ForeignKey) {

            $q = new Doctrine_Query();

            $q->update($rel->getTable()->getComponentName())
              ->set($rel->getForeign(), '?', array_values($this->identifier()));

            if (count($ids) > 0) {
                $q->whereIn($rel->getTable()->getIdentifier(), $ids);
            }

            $q->execute();

        } else if ($rel instanceof Doctrine_Relation_LocalKey) {

            $q = new Doctrine_Query();

            $q->update($this->getTable()->getComponentName())
                    ->set($rel->getLocalFieldName(), '?', $ids);

            if (count($ids) > 0) {
                $q->whereIn($rel->getTable()->getIdentifier(), array_values($this->identifier()));
            }

            $q->execute();

        }

        return $this;
    }


    /**
     * __call
     * this method is a magic method that is being used for method overloading
     *
     * the function of this method is to try to find given method from the templates
     * this record is using and if it finds given method it will execute it
     *
     * So, in sense, this method replicates the usage of mixins (as seen in some programming languages)
     *
     * @param string $method        name of the method
     * @param array $args           method arguments
     * @return mixed                the return value of the given method
     */
    public function __call($method, $args)
    {
        if (($template = $this->_mapper->getMethodOwner($method)) !== false) {
            $template->setInvoker($this);
            return call_user_func_array(array($template, $method), $args);
        }

        foreach ($this->_mapper->getTable()->getTemplates() as $template) {
            if (method_exists($template, $method)) {
                $template->setInvoker($this);
                $this->_mapper->setMethodOwner($method, $template);

                return call_user_func_array(array($template, $method), $args);
            }
        }

        throw new Doctrine_Record_Exception('Unknown method ' . $method);
    }

    /**
     * used to delete node from tree - MUST BE USE TO DELETE RECORD IF TABLE ACTS AS TREE
     *
     */
    public function deleteNode()
    {
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
    
    public function free()
    {
        $this->_mapper->getRepository()->evict($this->_oid);
        $this->_mapper->removeRecord($this);
    }
    
}
