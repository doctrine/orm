<?php
/*
 *  $Id: Record.php 4342 2008-05-08 14:17:35Z romanb $
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

#namespace Doctrine::ORM;

/**
 * Base class for all Entities (objects with persistent state in a RDBMS that are
 * managed by Doctrine).
 * 
 * NOTE: Methods that are intended for internal use only but must be public
 * are marked INTERNAL: and begin with an underscore "_" to indicate that they
 * ideally would not be public and to minimize naming collisions.
 *
 * @package     Doctrine
 * @subpackage  Entity
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision: 4342 $
 * @todo Split up into "Entity" and "ActiveEntity" (extends Entity).
 */
abstract class Doctrine_Entity extends Doctrine_Access implements Serializable
{
    /**
     * MANAGED
     * An Entity is in managed state when it has a primary key/identifier and is
     * managed by an EntityManager (registered in the identity map).
     */
    const STATE_MANAGED = 1;

    /**
     * NEW
     * An Entity is new if it does not yet have an identifier/primary key
     * and is not (yet) managed by an EntityManager.
     */
    const STATE_NEW = 2;

    /**
     * LOCKED STATE
     * An Entity is temporarily locked during deletes and saves.
     *
     * This state is used internally to ensure that circular deletes
     * and saves will not cause infinite loops.
     * @todo Not sure this is a good idea. It is a problematic solution because
     * it hides the original state while the locked state is active.
     */
    const STATE_LOCKED = 6;
    
    /**
     * A detached Entity is an instance with a persistent identity that is not
     * (or no longer) associated with an EntityManager (and a UnitOfWork).
     * This means its no longer in the identity map.
     */
    const STATE_DETACHED = 3;
    
    /**
     * A removed Entity instance is an instance with a persistent identity,
     * associated with an EntityManager, that is scheduled for removal from the
     * database.
     */
    const STATE_DELETED = 4;
    
    /**
     * Index used for creating object identifiers (oid's).
     *
     * @var integer $index                  
     */
    private static $_index = 1;
    
    /**
     * Boolean flag that indicates whether automatic accessor overriding is enabled.
     *
     * @var boolean
     */
    private static $_useAutoAccessorOverride;
    
    /**
     * The accessor cache is used as a memory for the existance of custom accessors.
     *
     * @var array
     */
    private static $_accessorCache = array();
    
    /**
     * The mutator cache is used as a memory for the existance of custom mutators.
     *
     * @var array
     */
    private static $_mutatorCache = array();
    
    /**
     * The class descriptor.
     *
     * @var ClassMetadata
     */
    private $_class;
    
    /**
     * The name of the Entity.
     * 
     * @var string
     */
    private $_entityName;

    /**
     * The values that make up the ID/primary key of the entity.
     *
     * @var array                   
     */
    private $_id = array();

    /**
     * The entity data.
     *
     * @var array                  
     */
    private $_data = array();

    /**
     * The state of the object.
     *
     * @var integer
     */
    private $_state;

    /**
     * The names of fields that have been modified but not yet persisted.
     * Keys are field names, values oldValue => newValue tuples.
     *
     * @var array
     * @todo Rename to $_changeSet
     */
    private $_modified = array();

    /**
     * The references for all associations of the entity to other entities.
     *
     * @var array
     */
    private $_references = array();
    
    /**
     * The EntityManager that is responsible for the persistence of the entity.
     *
     * @var Doctrine_EntityManager
     */
    private $_em;

    /**
     * The object identifier of the object. Each object has a unique identifier
     * during script execution.
     * 
     * @var integer                  
     */
    private $_oid;

    /**
     * Constructor.
     * Creates a new Entity instance.
     */
    public function __construct()
    {
        $this->_entityName = get_class($this);
        $this->_em = Doctrine_EntityManagerFactory::getManager($this->_entityName);
        $this->_class = $this->_em->getClassMetadata($this->_entityName);
        $this->_oid = self::$_index++;
        $this->_data = $this->_em->_getTmpEntityData();
        if ($this->_data) {
            $this->_extractIdentifier();
            $this->_state = self::STATE_MANAGED;
        } else {
            $this->_state = self::STATE_NEW;
        }
        
        // @todo read from attribute the first time and move this initialization elsewhere.
        self::$_useAutoAccessorOverride = true; 
    }
    
    /**
     * Returns the object identifier.
     *
     * @return integer
     */
    final public function getOid()
    {
        return $this->_oid;
    }

    /**
     * setDefaultValues
     * sets the default values for records internal data
     *
     * @param boolean $overwrite                whether or not to overwrite the already set values
     * @return boolean
     * @todo Job of EntityManager.
     * @deprecated
     * Setting object-level default field values is much more natural to do in 
     * the constructor and database-level default values are set by the database.
     */
    /*public function assignDefaultValues($overwrite = false)
    {
        if ( ! $this->_class->hasDefaultValues()) {
            return false;
        }
        foreach ($this->_data as $column => $value) {
            $default = $this->_class->getDefaultValueOf($column);

            if ($default === null) {
                continue;
            }

            if ($value === Doctrine_Null::$INSTANCE || $overwrite) {
                $this->_data[$column] = $default;
                $this->_modified[]    = $column;
                $this->_state = Doctrine_Entity::STATE_TDIRTY;
            }
        }
    }*/

    /**
     * Hydrates this object from given array
     *
     * @param array $data
     * @return boolean
     */
    final public function hydrate(array $data)
    {
        $this->_data = array_merge($this->_data, $data);
        $this->_extractIdentifier();
    }

    /**
     * Copies the identifier names and values from _data into _id.
     */
    private function _extractIdentifier()
    {
        if ( ! $this->_class->isIdentifierComposite()) {
            // Single field identifier
            $name = $this->_class->getIdentifier();
            $name = $name[0];
            if (isset($this->_data[$name]) && $this->_data[$name] !== Doctrine_Null::$INSTANCE) {
                $this->_id[$name] = $this->_data[$name];
            }
        } else {
            // Composite identifier
            $names = $this->_class->getIdentifier();
            foreach ($names as $name) {
                if ($this->_data[$name] === Doctrine_Null::$INSTANCE) {
                    $this->_id[$name] = null;
                } else {
                    $this->_id[$name] = $this->_data[$name];
                }
            }
        }
    }
    
    /**
     * INTERNAL:
     */
    final public function _setIdentifier(array $identifier)
    {
        $this->_id = $identifier;
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
        //$this->_em->getEventManager()->dispatchEvent(Event::preSerialize);
        //$this->_class->dispatchLifecycleEvent(Event::preSerialize, $this);

        $vars = get_object_vars($this);

        unset($vars['_references']);
        unset($vars['_mapper']);
        unset($vars['_errorStack']);
        unset($vars['_filter']);
        unset($vars['_node']);
        unset($vars['_em']);

        //$name = (array)$this->_table->getIdentifier();
        $this->_data = array_merge($this->_data, $this->_id);

        foreach ($this->_data as $k => $v) {
            if ($v instanceof Doctrine_Entity && $this->_class->getTypeOfField($k) != 'object') {
                unset($vars['_data'][$k]);
            } else if ($v === Doctrine_Null::$INSTANCE) {
                unset($vars['_data'][$k]);
            } else {
                switch ($this->_class->getTypeOfField($k)) {
                    case 'array':
                    case 'object':
                        $vars['_data'][$k] = serialize($vars['_data'][$k]);
                        break;
                    case 'gzip':
                        $vars['_data'][$k] = gzcompress($vars['_data'][$k]);
                        break;
                    case 'enum':
                        $vars['_data'][$k] = $this->_class->enumIndex($k, $vars['_data'][$k]);
                        break;
                }
            }
        }
        
        $str = serialize($vars);

        //$this->postSerialize($event);

        return $str;
    }

    /**
     * Reconstructs the entity from it's serialized form.
     * This method is automatically called everytime the entity is unserialized.
     *
     * @param string $serialized                Doctrine_Entity as serialized string
     * @throws Doctrine_Record_Exception        if the cleanData operation fails somehow
     * @return void
     */
    public function unserialize($serialized)
    {
        //$event = new Doctrine_Event($this, Doctrine_Event::RECORD_UNSERIALIZE);
        //$this->preUnserialize($event);

        $this->_entityName = get_class($this);
        $manager = Doctrine_EntityManagerFactory::getManager($this->_entityName);
        $connection = $manager->getConnection();

        $this->_oid = self::$_index;
        self::$_index++;

        $this->_em = $manager;  

        $array = unserialize($serialized);

        foreach($array as $k => $v) {
            $this->$k = $v;
        }
        
        $this->_class = $this->_em->getClassMetadata($this->_entityName);

        foreach ($this->_data as $k => $v) {
            switch ($this->_class->getTypeOfField($k)) {
                case 'array':
                case 'object':
                    $this->_data[$k] = unserialize($this->_data[$k]);
                    break;
                case 'gzip':
                   $this->_data[$k] = gzuncompress($this->_data[$k]);
                    break;
                case 'enum':
                    $this->_data[$k] = $this->_class->enumValue($k, $this->_data[$k]);
                    break;

            }
        }

        $this->_extractIdentifier(!$this->isNew());
        
        //$this->postUnserialize($event);
    }

    /**
     * INTERNAL:
     * Gets or sets the state of this Entity.
     *
     * @param integer|string $state                 if set, this method tries to set the record state to $state
     * @see Doctrine_Entity::STATE_* constants
     *
     * @throws Doctrine_Record_State_Exception      if trying to set an unknown state
     * @return null|integer
     */
    final public function _state($state = null)
    {
        if ($state == null) {
            return $this->_state;
        }
        
        /* TODO: Do we really need this check? This is only for internal use after all. */
        switch ($state) {
            case self::STATE_MANAGED:
            case self::STATE_DELETED:
            case self::STATE_DETACHED:
            case self::STATE_NEW:
            case self::STATE_LOCKED:
                $this->_state = $state;
                break;
            default:
                throw Doctrine_Entity_Exception::invalidState($state);
        }
    }

    /**
     * refresh internal data from the database
     *
     * @param bool $deep                        If true, fetch also current relations. Caution: this deletes
     *                                          any aggregated values you may have queried beforee
     *
     * @throws Doctrine_Record_Exception        When the refresh operation fails (when the database row
     *                                          this record represents does not exist anymore)
     * @return boolean
     * @todo Implementation to EntityManager.
     * @todo Move to ActiveEntity (extends Entity).
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
            $query = $this->_em->createQuery()->from($this->_entityName);
            foreach (array_keys($this->_references) as $name) {
                $query->leftJoin(get_class($this) . '.' . $name);
            }
            $query->where(implode(' = ? AND ', $this->_class->getIdentifierColumnNames()) . ' = ?');
            $this->clearRelated();
            $record = $query->fetchOne($id);
        } else {
            // Use FETCH_ARRAY to avoid clearing object relations
            $record = $this->getRepository()->find($this->identifier(), Doctrine::HYDRATE_ARRAY);
            if ($record) {
                $this->hydrate($record);
            }
        }

        if ($record === false) {
            throw new Doctrine_Record_Exception('Failed to refresh. Record does not exist.');
        }

        $this->_modified = array();

        $this->_extractIdentifier();

        $this->_state = Doctrine_Entity::STATE_CLEAN;

        return $this;
    }

    /**
     * refresh
     * refres data of related objects from the database
     *
     * @param string $name              name of a related component.
     *                                  if set, this method only refreshes the specified related component
     *
     * @return Doctrine_Entity          this object
     * @todo Implementation to EntityManager.
     * @todo ActiveEntity method.
     */
    /*public function refreshRelated($name = null)
    {
        if (is_null($name)) {
            foreach ($this->_class->getRelations() as $rel) {
                $this->_references[$rel->getAlias()] = $rel->fetchRelatedFor($this);
            }
        } else {
            $rel = $this->_class->getRelation($name);
            $this->_references[$name] = $rel->fetchRelatedFor($this);
        }
    }*/

    /**
     * clearRelated
     * unsets all the relationships this object has
     *
     * (references to related objects still remain on Table objects)
     */
    /*public function clearRelated()
    {
        $this->_references = array();
    }*/

    /**
     * Gets the current field values.
     *
     * @return array  The fields and their values.                     
     */
    final public function getData()
    {
        return $this->_data;
    }
    
    /**
     * INTERNAL:
     * For internal hydration purposes only.
     */
    /*final public function _setData(array $data)
    {
        $this->_data = $data;
        $this->_extractIdentifier();
    }*/

    /**
     * INTERNAL: (Usage from within extending classes is intended)
     * 
     * Gets the value of a field (regular field or reference).
     * If the field is not yet loaded this method does NOT load it.
     * 
     * NOTE: Use of this method from outside the scope of an extending class
     * is strongly discouraged.
     *
     * @param $name                         name of the property
     * @throws Doctrine_Entity_Exception    if trying to get an unknown field
     * @return mixed
     * @todo Rename to _get()
     */
    final public function _rawGet($fieldName)
    {
        if (isset($this->_data[$fieldName])) {
            return $this->_rawGetField($fieldName);
        } else if (isset($this->_references[$fieldName])) {
            return $this->_rawGetReference($fieldName);
        } else {
            throw Doctrine_Entity_Exception::unknownField($fieldName);
        }
    }
    
    /**
     * INTERNAL: (Usage from within extending classes is intended)
     * 
     * Sets the value of a field (regular field or reference).
     * If the field is not yet loaded this method does NOT load it.
     * 
     * NOTE: Use of this method from outside the scope of an extending class
     * is strongly discouraged.
     *
     * @param $name                         name of the field
     * @throws Doctrine_Entity_Exception    if trying to get an unknown field
     * @return mixed
     * @todo Rename to _set
     */
    final public function _rawSet($fieldName, $value)
    {
        if (isset($this->_data[$fieldName])) {
            return $this->_rawSetField($fieldName, $value);
        } else if (isset($this->_references[$fieldName])) {
            return $this->_rawSetReference($fieldName, $value);
        } else {
            throw Doctrine_Entity_Exception::unknownField($fieldName);
        }
    }
    
    /**
     * INTERNAL:
     * Gets the value of a field.
     * 
     * NOTE: Use of this method from outside the scope of an extending class
     * is strongly discouraged. This method does NOT check whether the field
     * exists. _rawGet() in extending classes should be preferred.
     *
     * @param string $fieldName
     * @return mixed
     * @todo Rename to _unsafeGetField()
     */
    final public function _rawGetField($fieldName)
    {
        if ($this->_data[$fieldName] === Doctrine_Null::$INSTANCE) {
            return null;
        }
        return $this->_data[$fieldName];
    }
    
    /**
     * INTERNAL:
     * Sets the value of a field.
     * 
     * NOTE: Use of this method from outside the scope of an extending class
     * is strongly discouraged. This method does NOT check whether the field
     * exists. _rawSet() in extending classes should be preferred.
     *
     * @param string $fieldName
     * @param mixed $value
     * @todo Rename to _unsafeSetField()
     */
    final public function _rawSetField($fieldName, $value)
    {
        $this->_data[$fieldName] = $value;
    }
    
    /**
     * Gets a reference to another Entity.
     * 
     * NOTE: Use of this method from outside the scope of an extending class
     * is strongly discouraged. This method does NOT check whether the reference
     * exists.
     *
     * @param unknown_type $fieldName
     * @todo Rename to _unsafeGetReference().
     */
    final public function _rawGetReference($fieldName)
    {
        if ($this->_references[$fieldName] === Doctrine_Null::$INSTANCE) {
            return null;
        }
        return $this->_references[$fieldName];
    }
    
    /**
     * INTERNAL:
     * Sets a reference to another Entity.
     * 
     * NOTE: Use of this method from outside the scope of an extending class
     * is strongly discouraged.
     *
     * @param string $fieldName
     * @param mixed $value
     * @todo Refactor. What about composite keys?
     * @todo Rename to _unsafeSetReference()
     */
    final public function _rawSetReference($name, $value)
    {
        if ($value === Doctrine_Null::$INSTANCE) {
            $this->_references[$name] = $value;
            return;
        }
        
        $rel = $this->_class->getRelation($name);

        // one-to-many or one-to-one relation
        if ($rel instanceof Doctrine_Relation_ForeignKey ||
                $rel instanceof Doctrine_Relation_LocalKey) {
            if ( ! $rel->isOneToOne()) {
                // one-to-many relation found
                if ( ! $value instanceof Doctrine_Collection) {
                    throw Doctrine_Entity_Exception::invalidValueForOneToManyReference();
                }
                if (isset($this->_references[$name])) {
                    $this->_references[$name]->setData($value->getData());
                    return;
                }
            } else {
                $relatedTable = $value->getTable();
                $foreignFieldName = $rel->getForeignFieldName();
                $localFieldName = $rel->getLocalFieldName();

                // one-to-one relation found
                if ( ! ($value instanceof Doctrine_Entity)) {
                    throw Doctrine_Entity_Exception::invalidValueForOneToOneReference();
                }
                if ($rel instanceof Doctrine_Relation_LocalKey) {
                    $idFieldNames = $value->getTable()->getIdentifier();
                    if ( ! empty($foreignFieldName) && $foreignFieldName != $idFieldNames[0]) {
                        $this->set($localFieldName, $value->_rawGet($foreignFieldName));
                    } else {
                        $this->set($localFieldName, $value);
                    }
                } else {
                    $value->set($foreignFieldName, $this);
                }
            }
        } else if ($rel instanceof Doctrine_Relation_Association) {
            if ( ! ($value instanceof Doctrine_Collection)) {
                throw Doctrine_Entity_Exception::invalidValueForManyToManyReference();
            }
        }

        $this->_references[$name] = $value;
    }

    /**
     * loads all the uninitialized properties from the database.
     *
     * @return boolean
     * @todo ActiveRecord method.
     */
    /*public function load()
    {
        // only load the data from database if the Doctrine_Entity is in proxy state
        if ($this->_state == Doctrine_Entity::STATE_PROXY) {
            $this->refresh();
            $this->_state = Doctrine_Entity::STATE_CLEAN;
            return true;
        }
        return false;
    }*/

    /**
     * Generic getter.
     *
     * @param mixed $name                       name of the property or related component
     * @param boolean $load                     whether or not to invoke the loading procedure
     * @throws Doctrine_Record_Exception        if trying to get a value of unknown property / related component
     * @return mixed
     */
    final public function get($fieldName)
    {
        if ($getter = $this->_getCustomAccessor($fieldName)) {
            return $this->$getter();
        }
        
        // Use built-in accessor functionality        
        $nullObj = Doctrine_Null::$INSTANCE;
        if (isset($this->_data[$fieldName])) {
            return $this->_data[$fieldName] !== $nullObj ?
                    $this->_data[$fieldName] : null;
        } else if (isset($this->_references[$fieldName])) {
            return $this->_references[$fieldName] !== $nullObj ?
                    $this->_references[$fieldName] : null;
        } else {
            $class = $this->_class;
            if ($class->hasField($fieldName)) {
                return null;
            } else if ($class->hasRelation($fieldName)) {
                $rel = $class->getRelation($fieldName);
                if ($rel->isLazilyLoaded()) {
                    $this->_references[$fieldName] = $rel->lazyLoadFor($this);
                    return $this->_references[$fieldName] !== $nullObj ?
                            $this->_references[$fieldName] : null;
                } else {
                    return null;
                }
            } else {
                throw Doctrine_Entity_Exception::invalidField($fieldName);
            }
        }
    }
    
    /**
     * Gets the custom mutator method for a field, if it exists.
     *
     * @param string $fieldName  The field name.
     * @return mixed  The name of the custom mutator or FALSE, if the field does
     *                not have a custom mutator.
     */
    private function _getCustomMutator($fieldName)
    {
        if ( ! isset(self::$_mutatorCache[$this->_entityName][$fieldName])) {
            if (self::$_useAutoAccessorOverride) {
                $setterMethod = 'set' . Doctrine::classify($fieldName);
                if (method_exists($this, $setterMethod)) {
                    self::$_mutatorCache[$this->_entityName][$fieldName] = $setterMethod;
                } else {
                    self::$_mutatorCache[$this->_entityName][$fieldName] = false;
                }
            }
            
            if ($setter = $this->_class->getCustomMutator($fieldName)) {
                self::$_mutatorCache[$this->_entityName][$fieldName] = $setter;
            } else if ( ! isset(self::$_mutatorCache[$this->_entityName][$fieldName])) {
                self::$_mutatorCache[$this->_entityName][$fieldName] = false;
            }
        }
        
        return self::$_mutatorCache[$this->_entityName][$fieldName];
    }
    
    /**
     * Gets the custom accessor method of a field, if it exists.
     *
     * @param string $fieldName  The field name.
     * @return mixed  The name of the custom accessor method, or FALSE if the 
     *                field does not have a custom accessor.
     */
    private function _getCustomAccessor($fieldName)
    {
        if ( ! isset(self::$_accessorCache[$this->_entityName][$fieldName])) {
            if (self::$_useAutoAccessorOverride) {
                $getterMethod = 'get' . Doctrine::classify($fieldName);
                if (method_exists($this, $getterMethod)) {
                    self::$_accessorCache[$this->_entityName][$fieldName] = $getterMethod;
                } else {
                    self::$_accessorCache[$this->_entityName][$fieldName] = false;
                }
            }
            if ($getter = $this->_class->getCustomAccessor($fieldName)) {
                self::$_accessorCache[$this->_entityName][$fieldName] = $getter;
            } else if ( ! isset(self::$_accessorCache[$this->_entityName][$fieldName])) {
                self::$_accessorCache[$this->_entityName][$fieldName] = false;
            }
        }
        
        return self::$_accessorCache[$this->_entityName][$fieldName];
    }
    
    /**
     * Gets the entity class name.
     *
     * @return string
     */
    final public function getClassName()
    {
        return $this->_entityName;
    }

    /**
     * Generic setter.
     *
     * @param mixed $name                   name of the property or reference
     * @param mixed $value                  value of the property or reference
     */
    final public function set($fieldName, $value)
    {
        if ($setter = $this->_getCustomMutator($fieldName)) {
            return $this->$setter($value);
        }
        
        if ($this->_class->hasField($fieldName)) {
            /*if ($value instanceof Doctrine_Entity) {
                $type = $class->getTypeOf($fieldName);
                // FIXME: composite key support
                $ids = $value->identifier();
                $id = count($ids) > 0 ? array_pop($ids) : null;
                if ($id !== null && $type !== 'object') {
                    $value = $id;
                }
            }*/

            $old = isset($this->_data[$fieldName]) ? $this->_data[$fieldName] : null;
            //FIXME: null == 0 => true
            if ($old != $value) {
                $this->_data[$fieldName] = $value;
                $this->_modified[$fieldName] = array($old => $value);
                if ($this->isNew() && $this->_class->isIdentifier($fieldName)) {
                    $this->_id[$fieldName] = $value;
                }
            }
        } else if ($this->_class->hasRelation($fieldName)) {
            $this->_rawSetReference($fieldName, $value);
        } else {
            throw Doctrine_Entity_Exception::invalidField($fieldName);
        }
    }

    /**
     * Checks whether a field is set (not null).
     * 
     * NOTE: Invoked by Doctrine::ORM::Access#__isset().
     *
     * @param string $name
     * @return boolean
     */
    final public function contains($fieldName)
    {
        if (isset($this->_data[$fieldName])) {
            if ($this->_data[$fieldName] === Doctrine_Null::$INSTANCE) {
                return false;
            }
            return true;
        }
        if (isset($this->_id[$fieldName])) {
            return true;
        }
        if (isset($this->_references[$fieldName]) &&
                $this->_references[$fieldName] !== Doctrine_Null::$INSTANCE) {
            return true;
        }
        return false;
    }

    /**
     * Clears the value of a field.
     * 
     * NOTE: Invoked by Doctrine::ORM::Access#__unset().
     * 
     * @param string $name
     * @return void
     */
    final public function remove($fieldName)
    {
        if (isset($this->_data[$fieldName])) {
            $this->_data[$fieldName] = array();
        } else if (isset($this->_references[$fieldName])) {
            if ($this->_references[$fieldName] instanceof Doctrine_Entity) {
                // todo: delete related record when saving $this
                $this->_references[$fieldName] = Doctrine_Null::$INSTANCE;
            } else if ($this->_references[$fieldName] instanceof Doctrine_Collection) {
                $this->_references[$fieldName]->setData(array());
            }
        }
    }

    /**
     * Saves the current state of the entity into the database.
     * This method also saves associated entities.
     *
     * @param Doctrine_Connection $conn                 optional connection parameter
     * @return void
     * @todo ActiveEntity method.
     */
    public function save()
    {
        // TODO: Forward to EntityManager. There: registerNew() OR registerDirty() on UnitOfWork.
        $this->_em->save($this);
    }

    /**
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
     * @todo ActiveEntity method.
     */
    public function replace()
    {
        return $this->_em->replace(
                $this->_class,
                $this->getPrepared(),
                $this->_id);
    }
    
    /**
     * Gets the names and values of all fields that have been modified since
     * the entity was last synch'd with the database.
     *
     * @return array
     */
    final public function getModifiedFields()
    {
        $a = array();
        foreach ($this->_modified as $k => $v) {
            $a[$v] = $this->_data[$v];
        }
        return $a;
    }

    /**
     * Returns an array of modified fields and values with data preparation
     * adds column aggregation inheritance and converts Records into primary key values
     *
     * @param array $array
     * @return array
     * @todo What about a little bit more expressive name? getPreparedData?
     * @todo Does not look like the best place here ...
     */
    final public function getPrepared(array $array = array())
    {
        $dataSet = array();

        if (empty($array)) {
            $modifiedFields = $this->_modified;
        }

        foreach ($modifiedFields as $field) {
            $type = $this->_class->getTypeOfField($field);

            if ($this->_data[$field] === Doctrine_Null::$INSTANCE) {
                $dataSet[$field] = null;
                continue;
            }

            switch ($type) {
                case 'array':
                case 'object':
                    $dataSet[$field] = serialize($this->_data[$field]);
                    break;
                case 'gzip':
                    $dataSet[$field] = gzcompress($this->_data[$field],5);
                    break;
                case 'boolean':
                    $dataSet[$field] = $this->_em->getConnection()
                            ->convertBooleans($this->_data[$field]);
                break;
                case 'enum':
                    $dataSet[$field] = $this->_class->enumIndex($field, $this->_data[$field]);
                    break;
                default:
                    /*if ($this->_data[$field] instanceof Doctrine_Entity) {
                        // FIXME: composite key support
                        $ids = $this->_data[$field]->identifier();
                        $id = count($ids) > 0 ? array_pop($ids) : null;
                        $this->_data[$field] = $id;
                    }*/
                    /** TODO:
                    if ($this->_data[$v] === null) {
                        throw new Doctrine_Record_Exception('Unexpected null value.');
                    }
                    */

                    $dataSet[$field] = $this->_data[$field];
            }
        }
        
        // @todo cleanup
        // populates the discriminator field in Single & Class Table Inheritance
        if ($this->_class->getInheritanceType() == Doctrine::INHERITANCE_TYPE_JOINED ||
                $this->_class->getInheritanceType() == Doctrine::INHERITANCE_TYPE_SINGLE_TABLE) {
            $discCol = $this->_class->getInheritanceOption('discriminatorColumn');
            $discMap = $this->_class->getInheritanceOption('discriminatorMap');
            $old = $this->get($discCol, false);
            $discValue = array_search($this->_entityName, $discMap);
            if ((string) $old !== (string) $discValue || $old === null) {
                $dataSet[$discCol] = $discValue;
                $this->_data[$discCol] = $discValue;
            }
        }

        return $dataSet;
    }

    /**
     * Creates an array representation of the object's data.
     *
     * @param boolean $deep - Return also the relations
     * @return array
     * @todo ActiveEntity method.
     */
    public function toArray($deep = true, $prefixKey = false)
    {
        $a = array();

        foreach ($this as $column => $value) {
            if ($value === Doctrine_Null::$INSTANCE || is_object($value)) {
                $value = null;
            }
            $a[$column] = $value;
        }

        if ($this->_class->getIdentifierType() == Doctrine::IDENTIFIER_AUTOINC) {
            $idFieldNames = $this->_class->getIdentifier();
            $idFieldName = $idFieldNames[0];
            
            $ids = $this->identifier();
            $id = count($ids) > 0 ? array_pop($ids) : null;
            
            $a[$idFieldName] = $id;
        }

        if ($deep) {
            foreach ($this->_references as $key => $relation) {
                if ( ! $relation instanceof Doctrine_Null) {
                    $a[$key] = $relation->toArray($deep, $prefixKey);
                }
            }
        }

        // [FIX] Prevent mapped Doctrine_Entitys from being displayed fully
        foreach ($this->_values as $key => $value) {
            if ($value instanceof Doctrine_Entity) {
                $a[$key] = $value->toArray($deep, $prefixKey);
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }

    /**
     * Merges this Entity with an array of values
     * or with another existing instance of.
     *
     * @param  mixed $data Data to merge. Either another instance of this model or an array
     * @param  bool  $deep Bool value for whether or not to merge the data deep
     * @return void
     * @todo ActiveEntity method.
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
     * @todo ActiveEntity method.
     */
    public function fromArray($array, $deep = true)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if ($deep && $this->getTable()->hasRelation($key)) {
                    $this->$key->fromArray($value, $deep);
                } else if ($this->getTable()->hasField($key)) {
                    $this->set($key, $value);
                }
            }
        }
    }

    /**
     * Synchronizes a Doctrine_Entity and its relations with data from an array
     *
     * It expects an array representation of a Doctrine_Entity similar to the return
     * value of the toArray() method. If the array contains relations it will create
     * those that don't exist, update the ones that do, and delete the ones missing
     * on the array but available on the Doctrine_Entity
     *
     * @param array $array representation of a Doctrine_Entity
     * @todo ActiveEntity method.
     */
    public function synchronizeFromArray(array $array)
    {
        foreach ($array as $key => $value) {
            if ($this->getTable()->hasRelation($key)) {
                $this->get($key)->synchronizeFromArray($value);
            } else if ($this->getTable()->hasColumn($key)) {
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
     * @todo ActiveEntity method.
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
     * @todo ActiveEntity method.
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
     * Checks whether the entity already has a persistent state.
     *
     * @return boolean  TRUE if the object is managed and has persistent state, FALSE otherwise.
     * @deprecated
     */
    /*public function exists()
    {
        return ($this->_state !== Doctrine_Entity::STATE_TCLEAN &&
                $this->_state !== Doctrine_Entity::STATE_TDIRTY);
    }*/
    
    /**
     * Checks whether the entity already has a persistent state.
     *
     * @return boolean  TRUE if the object is new, FALSE otherwise.
     */
    final public function isNew()
    {
        return $this->_state == self::STATE_NEW;
    }

    /**
     * Checks whether the entity has been modified since it was last synchronized
     * with the database.
     *
     * @return boolean  TRUE if the object has been modified, FALSE otherwise.
     */
    final public function isModified()
    {
        return count($this->_modified) > 0;
    }

    /**
     * method for checking existence of properties and Doctrine_Entity references
     *
     * @param mixed $name               name of the property or reference
     * @return boolean
     * @todo Method name does not reflect the purpose.
     */
    /*public function hasRelation($fieldName)
    {
        if (isset($this->_data[$fieldName]) || isset($this->_id[$fieldName])) {
            return true;
        }
        return $this->_class->hasRelation($fieldName);
    }*/

    /**
     * getIterator
     * @return Doctrine_Record_Iterator     a Doctrine_Record_Iterator that iterates through the data
     * @todo Really needed/useful?
     */
    /*public function getIterator()
    {
        return new Doctrine_Record_Iterator($this);
    }*/

    /**
     * Deletes the entity.
     *
     * Triggered events: onPreDelete, onDelete.
     *
     * @return boolean      true on success, false on failure
     * @todo ActiveRecord method.
     */
    public function delete(Doctrine_Connection $conn = null)
    {
        // TODO: Forward to EntityManager. There: registerRemoved() on UnitOfWork
        return $this->_em->remove($this, $conn);
    }

    /**
     * Creates a copy of the entity.
     *
     * @return Doctrine_Entity
     * @todo ActiveEntity method. Implementation to EntityManager.
     */
    public function copy($deep = true)
    {
        $data = $this->_data;

        if ($this->_class->getIdentifierType() === Doctrine::IDENTIFIER_AUTOINC) {
            $idFieldNames = (array)$this->_class->getIdentifier();
            $id = $idFieldNames[0];
            unset($data[$id]);
        }

        $ret = $this->_em->createEntity($this->_entityName, $data);
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
                        $ret->{$key}[] = $record->copy($deep);
                    }
                } else {
                    $ret->set($key, $value->copy($deep));
                }
            }
        }

        return $ret;
    }

    /**
     * INTERNAL:
     * Assigns an identifier to the entity. This is only intended for use by
     * the EntityPersisters or the UnitOfWork.
     *
     * @param mixed $id
     */
    final public function _assignIdentifier($id)
    {
        if (is_array($id)) {
            foreach ($id as $fieldName => $value) {
                $this->_id[$fieldName] = $value;
                $this->_data[$fieldName] = $value;
            }
        } else {
            $name = $this->_class->getSingleIdentifierFieldName();
            $this->_id[$name] = $id;
            $this->_data[$name] = $id;
        }
        $this->_modified = array();
    }

    /**
     * INTERNAL:
     * Returns the primary keys of the entity (key => value pairs).
     *
     * @return array
     */
    final public function _identifier()
    {
        return $this->_id;
    }

    /**
     * hasRefence
     * @param string $name
     * @return boolean
     * @todo Better name? hasAssociation() ?
     */
    final public function hasReference($name)
    {
        return isset($this->_references[$name]);
    }

    /**
     * obtainReference
     *
     * @param string $name
     * @throws Doctrine_Record_Exception        if trying to get an unknown related component
     */
    final public function obtainReference($name)
    {
        if (isset($this->_references[$name])) {
            return $this->_references[$name];
        }
        throw new Doctrine_Record_Exception("Unknown reference $name.");
    }

    /**
     * INTERNAL:
     * 
     * getReferences
     * @return array    all references
     */
    final public function _getReferences()
    {
        return $this->_references;
    }

    /**
     * INTERNAL:
     * setRelated
     *
     * @param string $alias
     * @param Doctrine_Access $coll
     */
    final public function _setRelated($alias, Doctrine_Access $coll)
    {
        $this->_references[$alias] = $coll;
    }

    /**
     * getter for node assciated with this record
     *
     * @return mixed if tree returns Doctrine_Node otherwise returns false
     * @todo Should go to the NestedSet Behavior plugin.
     */
    /*public function getNode()
    {
        if ( ! $this->_class->isTree()) {
            return false;
        }

        if ( ! isset($this->_node)) {
            $this->_node = Doctrine_Node::factory($this,
                    $this->getTable()->getOption('treeImpl'),
                    $this->getTable()->getOption('treeOptions'));
        }

        return $this->_node;
    }*/
    
    /**
     * revert
     * reverts this record to given version, this method only works if versioning plugin
     * is enabled
     *
     * @throws Doctrine_Record_Exception    if given version does not exist
     * @param integer $version      an integer > 1
     * @return Doctrine_Entity      this object
     * @todo Should go to the Versionable plugin.
     */
    public function revert($version)
    {
        $data = $this->_class
                ->getBehavior('Doctrine_Template_Versionable')
                ->getAuditLog()
                ->getVersion($this, $version);

        if ( ! isset($data[0])) {
            throw new Doctrine_Record_Exception('Version ' . $version . ' does not exist!');
        }

        $this->_data = $data[0];

        return $this;
    }
    
    /**
     * Removes links from this record to given records
     * if no ids are given, it removes all links
     *
     * @param string $alias     related component alias
     * @param array $ids        the identifiers of the related records
     * @return Doctrine_Entity  this object
     * @todo ActiveEntity method.
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
                $relTableIdFieldNames = (array)$rel->getTable()->getIdentifier();
                $q->whereIn($relTableIdFieldNames[0], $ids);
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
     * Creates links from this record to given records.
     *
     * @param string $alias     related component alias
     * @param array $ids        the identifiers of the related records
     * @return Doctrine_Entity  this object
     * @todo ActiveEntity method.
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
                $relTableIdFieldNames = (array)$rel->getTable()->getIdentifier();
                $q->whereIn($relTableIdFieldNames[0], $ids);
            }

            $q->execute();

        } else if ($rel instanceof Doctrine_Relation_LocalKey) {
            $q = new Doctrine_Query();
            $q->update($this->getTable()->getComponentName())
                    ->set($rel->getLocalFieldName(), '?', $ids);

            if (count($ids) > 0) {
                $relTableIdFieldNames = (array)$rel->getTable()->getIdentifier();
                $q->whereIn($relTableIdFieldNames[0], array_values($this->identifier()));
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
     * @todo In order to avoid name clashes and provide a more robust implementation
     *       we decided that all behaviors should be accessed through getBehavior($name)
     *       before they're used.
     */
    /*public function __call($method, $args)
    {
        if (($behavior = $this->_class->getBehaviorForMethod($method)) !== false) {
            $behavior->setInvoker($this);
            return call_user_func_array(array($behavior, $method), $args);
        }

        foreach ($this->_class->getBehaviors() as $behavior) {
            if (method_exists($behavior, $method)) {
                $behavior->setInvoker($this);
                $this->_class->addBehaviorMethod($method, $behavior);
                return call_user_func_array(array($behavior, $method), $args);
            }
        }

        throw new Doctrine_Record_Exception(sprintf('Unknown method %s::%s', get_class($this), $method));
    }*/

    /**
     * used to delete node from tree - MUST BE USE TO DELETE RECORD IF TABLE ACTS AS TREE
     *
     * @todo Should go to the NestedSet Behavior plugin.
     */
    /*public function deleteNode()
    {
        $this->getNode()->delete();
    }*/
    
    /**
     * Gets the ClassMetadata object that describes the entity class.
     * 
     * @return Doctrine::ORM::Mapping::ClassMetadata
     */
    final public function getClass()
    {
        return $this->_class;
    }
    
    /**
     * Gets the EntityManager that is responsible for the persistence of 
     * the Entity.
     *
     * @return Doctrine::ORM::EntityManager
     */
    final public function getEntityManager()
    {
        return $this->_em;
    }
    
    /**
     * Gets the EntityRepository of the Entity.
     *
     * @return Doctrine::ORM::EntityRepository
     */
    final public function getRepository()
    {
        return $this->_em->getRepository($this->_entityName);
    }
    
    /**
     * @todo Why toString() and __toString() ?
     */
    public function toString()
    {
        return Doctrine::dump(get_object_vars($this));
    }

    /**
     * returns a string representation of this object
     * @todo Why toString() and __toString() ?
     */
    public function __toString()
    {
        return (string) $this->_oid;
    }
    
    /**
     * Helps freeing the memory occupied by the entity.
     * Cuts all references the entity has to other entities and removes the entity
     * from the instance pool.
     * Note: The entity is no longer useable after free() has been called. Any operations
     * done with the entity afterwards can lead to unpredictable results.
     */
    public function free($deep = false)
    {
        if ($this->_state != self::STATE_LOCKED) {
            $this->_em->detach($this);
            $this->_data = array();
            $this->_id = array();

            if ($deep) {
                foreach ($this->_references as $name => $reference) {
                    if ( ! ($reference instanceof Doctrine_Null)) {
                        $reference->free($deep);
                    }
                }
            }

            $this->_references = array();
        }
    }

}
