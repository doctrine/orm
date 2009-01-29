<?php

#namespace Doctrine::ORM;

/**
 * The ActiveEntity class adds an ActiveRecord-like interface to Entity classes
 * that allows the Entities to directly interact with the persistence layer.
 * This is mostly just a convenient wrapper for people who want it that forwards
 * most method calls to the EntityManager.
 *
 * @since 2.0
 * @todo Any takers for this one? Needs a rewrite.
 */
class Doctrine_ORM_ActiveEntity
{
    /**
     * The class descriptor.
     *
     * @var Doctrine::ORM::ClassMetadata
     */
    private $_class;

    /**
     * The changes that happened to fields of a managed entity.
     * Keys are field names, values oldValue => newValue tuples.
     *
     * @var array
     */
    private $_dataChangeSet = array();

    /**
     * The EntityManager that is responsible for the persistent state of the entity.
     * Only managed entities have an associated EntityManager.
     *
     * @var Doctrine\ORM\EntityManager
     */
    private $_em;

    /**
     * Initializes a new instance of a class derived from ActiveEntity.
     */
    public function __construct() {
        parent::__construct();
        $this->_oid = self::$_index++;
        $this->_em = Doctrine_ORM_EntityManager::getActiveEntityManager();
        if (is_null($this->_em)) {
            throw new Doctrine_Exception("No EntityManager found. ActiveEntity instances "
                    . "can only be instantiated within the context of an active EntityManager.");
        }
        $this->_class = $this->_em->getClassMetadata($this->_entityName);
    }

    /**
     * Saves the current state of the entity into the database.
     *
     * @param Doctrine_Connection $conn                 optional connection parameter
     * @return void
     * @todo ActiveEntity method.
     */
    public function save()
    {
        $this->_em->save($this);
    }
    
    /**
     * Creates an array representation of the object's data.
     *
     * @param boolean $deep - Return also the relations
     * @return array
     * @todo ActiveEntity method.
     * @todo Move implementation to EntityManager.
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
            if ($value instanceof Doctrine_ORM_Entity) {
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
     * @todo Move implementation to EntityManager.
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
     * @todo Move implementation to EntityManager.
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
     * @todo Move implementation to EntityManager.
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
     * Deletes the persistent state of the entity.
     *
     * @return boolean  TRUE on success, FALSE on failure.
     */
    public function delete()
    {
        return $this->_em->remove($this);
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
     * Removes links from this record to given records
     * if no ids are given, it removes all links
     *
     * @param string $alias     related component alias
     * @param array $ids        the identifiers of the related records
     * @return Doctrine_Entity  this object
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
     * Refresh internal data from the database
     *
     * @param bool $deep                        If true, fetch also current relations. Caution: this deletes
     *                                          any aggregated values you may have queried beforee
     *
     * @throws Doctrine_Record_Exception        When the refresh operation fails (when the database row
     *                                          this record represents does not exist anymore)
     * @return boolean
     * @todo Implementation to EntityManager.
     * @todo Move to ActiveEntity (extends Entity). Implementation to EntityManager.
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

        $this->_state = Doctrine_ORM_Entity::STATE_CLEAN;

        return $this;
    }
    
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
            if ($this->_state == self::STATE_MANAGED) {
                $this->_em->detach($this);
            }
            if ($deep) {
                foreach ($this->_data as $name => $value) {
                    if ($value instanceof Doctrine_ORM_Entity || $value instanceof Doctrine_ORM_Collection) {
                        $value->free($deep);
                    }
                }
            }
            $this->_data = array();
        }
    }

    /**
     * Returns a string representation of this object.
     */
    public function __toString()
    {
        return (string)$this->_oid;
    }

    /**
     * Checks whether the entity is new.
     *
     * @return boolean  TRUE if the entity is new, FALSE otherwise.
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
        return count($this->_dataChangeSet) > 0;
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
     * this entity.
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
     * Checks whether a field is set (not null).
     *
     * @param string $name
     * @return boolean
     * @override
     */
    final protected function _contains($fieldName)
    {
        if (isset($this->_data[$fieldName])) {
            if ($this->_data[$fieldName] === Doctrine_ORM_Internal_Null::$INSTANCE) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Clears the value of a field.
     *
     * @param string $name
     * @return void
     * @override
     */
    final protected function _unset($fieldName)
    {
        if (isset($this->_data[$fieldName])) {
            if ($this->_state == self::STATE_MANAGED && $this->_class->hasAssociation($fieldName)) {
                $assoc = $this->_class->getAssociationMapping($fieldName);
                if ($assoc->isOneToOne() && $assoc->shouldDeleteOrphans()) {
                    $this->_em->delete($this->_references[$fieldName]);
                } else if ($assoc->isOneToMany() && $assoc->shouldDeleteOrphans()) {
                    foreach ($this->_references[$fieldName] as $entity) {
                        $this->_em->delete($entity);
                    }
                }
            }
            $this->_data[$fieldName] = null;
        }
    }


    /**
     * Registers the entity as dirty with the UnitOfWork.
     * Note: The Entity is only registered dirty if it is MANAGED and not yet
     * registered as dirty.
     */
    private function _registerDirty()
    {
        if ($this->_state == self::STATE_MANAGED &&
                ! $this->_em->getUnitOfWork()->isRegisteredDirty($this)) {
            $this->_em->getUnitOfWork()->registerDirty($this);
        }
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
     * Gets the data of the Entity.
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
     * @override
     */
    final protected function _get($fieldName)
    {
        $nullObj = Doctrine_ORM_Internal_Null::$INSTANCE;
        if (isset($this->_data[$fieldName])) {
            return $this->_data[$fieldName] !== $nullObj ?
                    $this->_data[$fieldName] : null;
        } else {
            if ($this->_state == self::STATE_MANAGED && $this->_class->hasAssociation($fieldName)) {
                $rel = $this->_class->getAssociationMapping($fieldName);
                if ($rel->isLazilyFetched()) {
                    $this->_data[$fieldName] = $rel->lazyLoadFor($this);
                    return $this->_data[$fieldName] !== $nullObj ?
                            $this->_data[$fieldName] : null;
                } else {
                    return null;
                }
            } else {
                return null;
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
     * @override
     */
    final protected function _set($fieldName, $value)
    {
        $old = isset($this->_data[$fieldName]) ? $this->_data[$fieldName] : null;
        if ( ! is_object($value)) {
            // NOTE: Common case: $old != $value. Special case: null == 0 (TRUE), which
            // is addressed by xor.
            if ($old != $value || (is_null($old) xor is_null($value))) {
                $this->_data[$fieldName] = $value;
                $this->_dataChangeSet[$fieldName] = array($old => $value);
                $this->_registerDirty();
            }
        } else {
            if ($old !== $value) {
                $this->_internalSetReference($fieldName, $value);
                $this->_dataChangeSet[$fieldName] = array($old => $value);
                $this->_registerDirty();
                if ($this->_state == self::STATE_MANAGED) {
                    //TODO: Allow arrays in $value. Wrap them in a Collection transparently.
                    if ($old instanceof Doctrine_ORM_Collection) {
                        $this->_em->getUnitOfWork()->scheduleCollectionDeletion($old);
                    }
                    if ($value instanceof Doctrine_ORM_Collection) {
                        $this->_em->getUnitOfWork()->scheduleCollectionRecreation($value);
                    }
                }
            }
        }
    }

    /* Serializable implementation */

    /**
     * Serializes the entity.
     * This method is automatically called when the entity is serialized.
     *
     * Part of the implementation of the Serializable interface.
     *
     * @return string
     * @todo Reimplement
     */
    public function serialize()
    {
        return "";
    }

    /**
     * Reconstructs the entity from it's serialized form.
     * This method is automatically called everytime the entity is unserialized.
     *
     * @param string $serialized                Doctrine_Entity as serialized string
     * @throws Doctrine_Record_Exception        if the cleanData operation fails somehow
     * @return void
     * @todo Reimplement.
     */
    public function unserialize($serialized)
    {
        ;
    }

    /* END of Serializable implementation */
}

?>