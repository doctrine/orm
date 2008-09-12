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
 * managed by Doctrine). Kind of a Layer Supertype.
 * 
 * NOTE: Methods that are intended for internal use only but must be public
 * are marked INTERNAL: and begin with an underscore "_" to indicate that they
 * ideally would not be public and to minimize naming collisions.
 * 
 * The "final" modifiers on most methods prevent accidental overrides.
 * It is not desirable that subclasses can override these methods.
 * The persistence layer should stay in the background as much as possible.
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision: 4342 $
 */
abstract class Doctrine_ORM_Entity implements ArrayAccess, Serializable
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
     * associated with an EntityManager, whose persistent state has been
     * deleted (or is scheduled for deletion).
     */
    const STATE_DELETED = 4;
    
    /**
     * Index used for creating object identifiers (oid's).
     *
     * @var integer
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
     * @var Doctrine::ORM::ClassMetadata
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
     * Name => Value map of join columns.
     *
     * @var array
     * @todo Not yet clear if needed.
     */
    //private $_joinColumns = array();

    /**
     * The changes that happened to fields of the entity.
     * Keys are field names, values oldValue => newValue tuples.
     *
     * @var array
     */
    private $_dataChangeSet = array();
    
    /**
     * The changes that happened to references of the entity to other entities.
     * Keys are field names, values oldReference => newReference tuples.
     * 
     * With one-one associations, a reference change means the reference has been
     * swapped out / replaced by another one.
     * 
     * With one-many, many-many associations, a reference change means the complete
     * collection has been sweapped out / replaced by another one.
     *
     * @var array
     */
    private $_referenceChangeSet = array();

    /**
     * The references for all associations of the entity to other entities.
     * Keys are field names, values object references.
     *
     * @var array
     */
    private $_references = array();

    /**
     * The EntityManager that is responsible for the persistent state of the entity.
     *
     * @var Doctrine::ORM::EntityManager
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
        $this->_em = Doctrine_ORM_EntityManager::getActiveEntityManager();
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
     * Copies the identifier names and values from _data into _id.
     */
    private function _extractIdentifier()
    {
        if ( ! $this->_class->isIdentifierComposite()) {
            // Single field identifier
            $name = $this->_class->getIdentifier();
            $name = $name[0];
            if (isset($this->_data[$name]) && $this->_data[$name] !== Doctrine_ORM_Internal_Null::$INSTANCE) {
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
     * Serializes the entity.
     * This method is automatically called when the entity is serialized.
     *
     * Part of the implementation of the Serializable interface.
     *
     * @return string
     */
    public function serialize()
    {
        //$this->_em->getEventManager()->dispatchEvent(Event::preSerialize);
        //$this->_class->dispatchLifecycleEvent(Event::preSerialize, $this);

        $vars = get_object_vars($this);

        unset($vars['_references']);
        unset($vars['_em']);

        //$name = (array)$this->_table->getIdentifier();
        $this->_data = array_merge($this->_data, $this->_id);

        foreach ($this->_data as $k => $v) {
            if ($v instanceof Doctrine_ORM_Entity && $this->_class->getTypeOfField($k) != 'object') {
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
        $this->_state = $state;
    }

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
     * Gets the value of a field (regular field or reference).
     *
     * @param $name  Name of the field.
     * @return mixed  Value of the field.
     * @throws Doctrine::ORM::Exceptions::EntityException  If trying to get an unknown field.
     */
    final protected function _get($fieldName)
    {
        $nullObj = Doctrine_ORM_Internal_Null::$INSTANCE;
        if (isset($this->_data[$fieldName])) {
            return $this->_data[$fieldName] !== $nullObj ?
                    $this->_data[$fieldName] : null;
        } else if (isset($this->_references[$fieldName])) {
            return $this->_references[$fieldName] !== $nullObj ?
                    $this->_references[$fieldName] : null;
        } else {
            if ($this->_class->hasField($fieldName)) {
                return null;
            } else if ($this->_class->hasAssociation($fieldName)) {
                $rel = $this->_class->getAssociationMapping($fieldName);
                if ($rel->isLazilyFetched()) {
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
     * Sets the value of a field (regular field or reference).
     *
     * @param $fieldName The name of the field.
     * @param $value The value of the field.
     * @return void
     * @throws Doctrine::ORM::Exceptions::EntityException
     */
    final protected function _set($fieldName, $value)
    {
        if ($this->_class->hasField($fieldName)) {
            $old = isset($this->_data[$fieldName]) ? $this->_data[$fieldName] : null;
            // NOTE: Common case: $old != $value. Special case: null == 0 (TRUE), which
            // is addressed by xor.
            if ($old != $value || (is_null($old) xor is_null($value))) {
                $this->_data[$fieldName] = $value;
                $this->_dataChangeSet[$fieldName] = array($old => $value);
                if ($this->isNew() && $this->_class->isIdentifier($fieldName)) {
                    $this->_id[$fieldName] = $value;
                }
                $this->_registerDirty();
            }
        } else if ($this->_class->hasAssociation($fieldName)) {
            $old = isset($this->_references[$fieldName]) ? $this->_references[$fieldName] : null;
            if ($old !== $value) {
                $this->_internalSetReference($fieldName, $value);
                $this->_referenceChangeSet[$fieldName] = array($old => $value);
                $this->_registerDirty();
                //TODO: Allow arrays in $value. Wrap them in a collection transparently.
                if ($old instanceof Doctrine_Collection) {
                    $this->_em->getUnitOfWork()->scheduleCollectionDeletion($old);
                }
                if ($value instanceof Doctrine_Collection) {
                    $this->_em->getUnitOfWork()->scheduleCollectionRecreation($value);
                }
            }
        } else {
            throw Doctrine_ORM_Exceptions_EntityException::invalidField($fieldName);
        }
    }
    
    /**
     * Registers the entity as dirty with the UnitOfWork.
     */
    private function _registerDirty()
    {
        if ($this->_state == self::STATE_MANAGED &&
                ! $this->_em->getUnitOfWork()->isRegisteredDirty($this)) {
            $this->_em->getUnitOfWork()->registerDirty($this);
        }
    }

    /**
     * INTERNAL:
     * Gets the value of a field.
     * 
     * NOTE: Use of this method in userland code is strongly discouraged.
     * This method does NOT check whether the field exists.
     * _get() in extending classes should be preferred.
     *
     * @param string $fieldName
     * @return mixed
     */
    final public function _internalGetField($fieldName)
    {
        if ($this->_data[$fieldName] === Doctrine_ORM_Internal_Null::$INSTANCE) {
            return null;
        }
        return $this->_data[$fieldName];
    }

    /**
     * INTERNAL:
     * Sets the value of a field.
     * 
     * NOTE: Use of this method in userland code is strongly discouraged.
     * This method does NOT check whether the field exists.
     * _set() in extending classes should be preferred.
     *
     * @param string $fieldName
     * @param mixed $value
     */
    final public function _internalSetField($fieldName, $value)
    {
        $this->_data[$fieldName] = $value;
    }

    /**
     * INTERNAL:
     * Gets a reference to another Entity.
     * 
     * NOTE: Use of this method in userland code is strongly discouraged.
     * This method does NOT check whether the reference exists.
     *
     * @param string $fieldName
     */
    final public function _internalGetReference($fieldName)
    {
        if ($this->_references[$fieldName] === Doctrine_ORM_Internal_Null::$INSTANCE) {
            return null;
        }
        return $this->_references[$fieldName];
    }

    /**
     * INTERNAL:
     * Sets a reference to another entity or a collection of entities.
     * 
     * NOTE: Use of this method in userland code is strongly discouraged.
     *
     * @param string $fieldName
     * @param mixed $value
     * @param boolean $completeBidirectional Whether to complete bidirectional associations
     *                                       (creating the back-reference). Should only
     *                                       be used by hydration.
     */
    final public function _internalSetReference($name, $value, $completeBidirectional = false)
    {
        if (is_null($value) || $value === Doctrine_ORM_Internal_Null::$INSTANCE) {
            $this->_references[$name] = $value;
            return; // early exit!
        }

        $rel = $this->_class->getAssociationMapping($name);

        if ($rel->isOneToOne() && ! $value instanceof Doctrine_ORM_Entity) {
            throw Doctrine_Entity_Exception::invalidValueForOneToOneReference();
        } else if (($rel->isOneToMany() || $rel->isManyToMany()) && ! $value instanceof Doctrine_ORM_Collection) {
            throw Doctrine_Entity_Exception::invalidValueForOneToManyReference();
        }

        $this->_references[$name] = $value;
        
        if ($completeBidirectional && $rel->isOneToOne()) {
            if ($rel->isOwningSide()) {
                // If there is an inverse mapping on the target class its bidirectional
                $targetClass = $this->_em->getClassMetadata($rel->getTargetEntityName());
                if ($targetClass->hasInverseAssociationMapping($name)) {
                    $value->_internalSetReference(
                            $targetClass->getInverseAssociationMapping($name)->getSourceFieldName(),
                            $this
                            );
                }
            } else {
                // for sure bidirectional, as there is no inverse side in unidirectional
                $value->_internalSetReference($rel->getMappedByFieldName(), $this);
            }
        }
    }

    /**
     * Generic getter for all (persistent) fields of the entity.
     * 
     * Invoked by Doctrine::ORM::Access#__get().
     *
     * @param string $fieldName  Name of the field.
     * @return mixed
     * @override
     */
    final public function get($fieldName)
    {
        if ($getter = $this->_getCustomAccessor($fieldName)) {
            return $this->$getter();
        }
        return $this->_get($fieldName);
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
     * Generic setter for (persistent) fields of the entity.
     * 
     * Invoked by Doctrine::ORM::Access#__set().
     *
     * @param string $name  The name of the field to set.
     * @param mixed $value  The value of the field.
     * @override
     */
    final public function set($fieldName, $value)
    {
        if ($setter = $this->_getCustomMutator($fieldName)) {
            return $this->$setter($value);
        }
        $this->_set($fieldName, $value);        
    }

    /**
     * Checks whether a field is set (not null).
     * 
     * NOTE: Invoked by Doctrine::ORM::Access#__isset().
     *
     * @param string $name
     * @return boolean
     */
    private function _contains($fieldName)
    {
        if (isset($this->_data[$fieldName])) {
            if ($this->_data[$fieldName] === Doctrine_ORM_Internal_Null::$INSTANCE) {
                return false;
            }
            return true;
        }
        if (isset($this->_id[$fieldName])) {
            return true;
        }
        if (isset($this->_references[$fieldName]) &&
                $this->_references[$fieldName] !== Doctrine_ORM_Internal_Null::$INSTANCE) {
            return true;
        }
        return false;
    }

    /**
     * Clears the value of a field.
     * 
     * @param string $name
     * @return void
     */
    private function _unset($fieldName)
    {
        if (isset($this->_data[$fieldName])) {
            $this->_data[$fieldName] = null;
        } else if (isset($this->_references[$fieldName])) {
            $assoc = $this->_class->getAssociationMapping($fieldName);
            if ($assoc->isOneToOne() && $assoc->shouldDeleteOrphans()) {
                $this->_em->delete($this->_references[$fieldName]);
            } else if ($assoc->isOneToMany() && $assoc->shouldDeleteOrphans()) {
                foreach ($this->_references[$fieldName] as $entity) {
                    $this->_em->delete($entity);
                }
            }
            $this->_references[$fieldName] = null;
        }
    }

    /**
     * INTERNAL:
     * Gets the changeset of the entities data.
     *
     * @return array
     */
    final public function _getDataChangeSet()
    {
        return $this->_dataChangeSet;
    }

    /**
     * INTERNAL:
     * Gets the changeset of the entities references to other entities.
     *
     * @return array
     */
    final public function _getReferenceChangeSet()
    {
        return $this->_referenceChangeSet;
    }

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
        return count($this->_fieldChangeSet) > 0;
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
        $this->_dataChangeSet = array();
        $this->_referenceChangeSet = array();
    }
    
    /**
     * @todo Not yet clear if needed.
     */
    /*final public function _setJoinColumn($columnName, $value)
    {
        $this->_joinColumns[$columnName] = $value;
    }*/
    
    /**
     * @todo Not yet clear if needed.
     */
    /*final public function _getJoinColumn($columnName)
    {
        return $this->_joinColumns[$columnName];
    }*/

    /**
     * INTERNAL:
     * Returns the primary keys of the entity (field => value pairs).
     *
     * @return array
     */
    final public function _identifier()
    {
        return $this->_id;
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
     * the entity.
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
     * Helps freeing the memory occupied by the entity.
     * Cuts all references the entity has to other entities and removes the entity
     * from the instance pool.
     * Note: The entity is no longer useable after free() has been called. Any operations
     * done with the entity afterwards can lead to unpredictable results.
     * 
     * @param boolean $deep Whether to cascade the free() call to (loaded) associated entities.
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

    /**
     * Check if an offsetExists.
     * 
     * Part of the ArrayAccess implementation.
     *
     * @param mixed $offset
     * @return boolean          whether or not this object contains $offset
     */
    public function offsetExists($offset)
    {
        return $this->_contains($offset);
    }

    /**
     * offsetGet    an alias of get()
     * 
     * Part of the ArrayAccess implementation.
     *
     * @see get,  __get
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Part of the ArrayAccess implementation.
     * 
     * sets $offset to $value
     * @see set,  __set
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    /**
     * Part of the ArrayAccess implementation.
     * 
     * unset a given offset
     * @see set, offsetSet, __set
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        return $this->_unset($offset);
    }

    /**
     * __set
     *
     * @see set, offsetSet
     * @param $name
     * @param $value
     * @since 1.0
     * @return void
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * __get
     *
     * @see get,  offsetGet
     * @param mixed $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * __isset()
     *
     * @param string $name
     * @since 1.0
     * @return boolean          whether or not this object contains $name
     */
    public function __isset($name)
    {
        return $this->_contains($name);
    }

    /**
     * __unset()
     *
     * @param string $name
     * @since 1.0
     * @return void
     */
    public function __unset($name)
    {
        return $this->_unset($name);
    }
    
    /**
     * returns a string representation of this object
     */
    public function __toString()
    {
        return (string)$this->_oid;
    }
}
