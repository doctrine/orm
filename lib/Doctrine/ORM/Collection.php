<?php
/*
 *  $Id: Collection.php 4930 2008-09-12 10:40:23Z romanb $
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
 * A persistent collection.
 * 
 * A collection object is strongly typed in the sense that it can only contain
 * entities of a specific type or one of it's subtypes. A collection object is
 * basically a wrapper around an ordinary php array and just like a php array
 * it can have List or Map semantics.
 * 
 * A collection of entities represents only the associations (links) to those entities.
 * That means, if the collection is part of a many-many mapping and you remove
 * entities from the collection, only the links in the xref table are removed (on flush).
 * Similarly, if you remove entities from a collection that is part of a one-many
 * mapping this will only result in the nulling out of the foreign keys on flush
 * (or removal of the links in the xref table if the one-many is mapped through an
 * xref table). If you want entities in a one-many collection to be removed when
 * they're removed from the collection, use deleteOrphans => true on the one-many
 * mapping.
 *
 * @license   http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since     1.0
 * @version   $Revision: 4930 $
 * @author    Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author    Roman Borschel <roman@code-factory.org>
 * @todo Add more typical Collection methods.
 * @todo Rename to PersistentCollection
 */
class Doctrine_ORM_Collection implements Countable, IteratorAggregate, Serializable, ArrayAccess
{   
    /**
     * The base type of the collection.
     *
     * @var string
     */
    protected $_entityBaseType;
    
    /**
     * An array containing the entries of this collection.
     * This is the wrapped php array.
     *
     * @var array 
     */
    protected $_data = array();

    /**
     * A snapshot of the collection at the moment it was fetched from the database.
     * This is used to create a diff of the collection at commit time.
     *
     * @var array
     */
    protected $_snapshot = array();

    /**
     * This entity that owns this collection.
     * 
     * @var Doctrine\ORM\Entity
     */
    protected $_owner;

    /**
     * The association mapping the collection belongs to.
     * This is currently either a OneToManyMapping or a ManyToManyMapping.
     *
     * @var Doctrine\ORM\Mapping\AssociationMapping
     */
    protected $_association;

    /**
     * The name of the field that is used for collection key mapping.
     *
     * @var string
     */
    protected $_keyField;
    
    /**
     * The EntityManager that manages the persistence of the collection.
     *
     * @var Doctrine\ORM\EntityManager
     */
    protected $_em;
    
    /**
     * The name of the field on the target entities that points to the owner
     * of the collection. This is only set if the association is bidirectional.
     *
     * @var string
     */
    protected $_backRefFieldName;
    
    /**
     * Hydration flag.
     *
     * @var boolean
     * @see _setHydrationFlag()
     */
    protected $_hydrationFlag;

    /**
     * Creates a new persistent collection.
     */
    public function __construct($entityBaseType, $keyField = null)
    {
        $this->_entityBaseType = $entityBaseType;
        $this->_em = Doctrine_ORM_EntityManager::getActiveEntityManager();

        if ($keyField !== null) {
            if ( ! $this->_em->getClassMetadata($entityBaseType)->hasField($keyField)) {
                throw new Doctrine_Exception("Invalid field '$keyField' can't be uses as key.");
            }
            $this->_keyField = $keyField;
        }
    }

    /**
     * setData
     *
     * @param array $data
     * @todo Remove?
     */
    public function setData(array $data) 
    {
        $this->_data = $data;
    }

    /**
     * INTERNAL: Sets the key column for this collection
     *
     * @param string $column
     * @return Doctrine_Collection
     */
    public function setKeyField($fieldName)
    {
        $this->_keyField = $fieldName;
        return $this;
    }

    /**
     * INTERNAL: returns the name of the key column
     *
     * @return string
     */
    public function getKeyField()
    {
        return $this->_keyField;
    }

    /**
     * Unwraps the array contained in the Collection instance.
     *
     * @return array The wrapped array.
     */
    public function unwrap()
    {
        return $this->_data;
    }

    /**
     * returns the first record in the collection
     *
     * @return mixed
     */
    public function getFirst()
    {
        return reset($this->_data);
    }

    /**
     * returns the last record in the collection
     *
     * @return mixed
     */
    public function getLast()
    {
        return end($this->_data);
    }
    
    /**
     * returns the last record in the collection
     *
     * @return mixed
     */
    public function end()
    {
        return end($this->_data);
    }
    
    /**
     * returns the current key
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->_data);
    }
    
    /**
     * INTERNAL:
     * Sets the collection owner. Used (only?) during hydration.
     *
     * @return void
     */
    public function _setOwner($entity, Doctrine_ORM_Mapping_AssociationMapping $relation)
    {
        $this->_owner = $entity;
        $this->_association = $relation;
        if ($relation->isInverseSide()) {
            // for sure bidirectional
            $this->_backRefFieldName = $relation->getMappedByFieldName();
        } else {
            $targetClass = $this->_em->getClassMetadata($relation->getTargetEntityName());
            if ($targetClass->hasInverseAssociationMapping($relation->getSourceFieldName())) {
                // bidirectional
                $this->_backRefFieldName = $targetClass->getInverseAssociationMapping(
                        $relation->getSourceFieldName())->getSourceFieldName();
            }
        }
    }

    /**
     * INTERNAL:
     * Gets the collection owner.
     *
     * @return Doctrine\ORM\Entity
     */
    public function _getOwner()
    {
        return $this->_owner;
    }

    /**
     * Removes an entity from the collection.
     *
     * @param mixed $key
     * @return boolean
     */
    public function remove($key)
    {
        $removed = $this->_data[$key];
        unset($this->_data[$key]);
        //TODO: Register collection as dirty with the UoW if necessary
        //$this->_em->getUnitOfWork()->scheduleCollectionUpdate($this);
        //TODO: delete entity if shouldDeleteOrphans
        /*if ($this->_association->isOneToMany() && $this->_association->shouldDeleteOrphans()) {
            $this->_em->delete($removed);
        }*/
        
        return $removed;
    }
    
    /**
     * __isset()
     *
     * @param string $name
     * @return boolean          whether or not this object contains $name
     */
    public function __isset($key)
    {
        return $this->containsKey($key);
    }
    
    /**
     * __unset()
     *
     * @param string $name
     * @since 1.0
     * @return mixed
     */
    public function __unset($key)
    {
        return $this->remove($key);
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
        return $this->containsKey($offset);
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
        if ( ! isset($offset)) {
            return $this->add($value);
        }
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
        return $this->remove($offset);
    }

    /**
     * Checks whether the collection contains an entity.
     *
     * @param mixed $key                    the key of the element
     * @return boolean
     */
    public function containsKey($key)
    {
        return isset($this->_data[$key]);
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $entity
     * @return unknown
     */
    public function contains($entity)
    {
        return in_array($entity, $this->_data, true);
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $otherColl
     * @todo Impl
     */
    public function containsAll($otherColl)
    {
        //...
    }
    
    /**
     *
     */
    public function search(Doctrine_ORM_Entity $record)
    {
        return array_search($record, $this->_data, true);
    }

    /**
     * returns a record for given key
     *
     * Collection also maps referential information to newly created records
     *
     * @param mixed $key                    the key of the element
     * @return Doctrine_Entity              return a specified record
     */
    public function get($key)
    {
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
        return null;
    }

    /**
     * Gets all keys.
     * (Map method)
     * 
     * @return array
     */
    public function getKeys()
    {
        return array_keys($this->_data);
    }
    
    /**
     * Gets all values.
     * (Map method)
     *
     * @return array
     */
    public function getValues()
    {
        return array_values($this->_data);
    }

    /**
     * Returns the number of records in this collection.
     *
     * Implementation of the Countable interface.
     *
     * @return integer  The number of records in the collection.
     */
    public function count()
    {
        return count($this->_data);
    }

    /**
     * When the collection is a Map this is like put(key,value)/add(key,value).
     * When the collection is a List this is like add(position,value).
     * 
     * @param integer $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        if ( ! $value instanceof Doctrine_ORM_Entity) {
            throw new Doctrine_Collection_Exception('Value variable in set is not an instance of Doctrine_Entity');
        }
        $this->_data[$key] = $value;
        //TODO: Register collection as dirty with the UoW if necessary
        $this->_changed();
    }

    /**
     * Adds an entry to the collection.
     * 
     * @param mixed $value
     * @param string $key 
     * @return boolean
     */
    public function add($value, $key = null)
    {
        if ( ! $value instanceof $this->_entityBaseType) {
            throw new Doctrine_Record_Exception('Value variable in collection is not an instance of Doctrine_Entity.');
        }
        
        // TODO: Really prohibit duplicates?
        if (in_array($value, $this->_data, true)) {
            return false;
        }

        if (isset($key)) {
            if (isset($this->_data[$key])) {
                return false;
            }
            $this->_data[$key] = $value;
        } else {
            $this->_data[] = $value;
        }
        
        if ($this->_hydrationFlag) {
            if ($this->_backRefFieldName) {
                // set back reference to owner
                $this->_em->getClassMetadata($this->_entityBaseType)->getReflectionProperty(
                        $this->_backRefFieldName)->setValue($value, $this->_owner);
            }
        } else {
            //TODO: Register collection as dirty with the UoW if necessary
            $this->_changed();
        }
        
        return true;
    }
    
    /**
     * Adds all entities of the other collection to this collection.
     *
     * @param unknown_type $otherCollection
     * @todo Impl
     */
    public function addAll($otherCollection)
    {
        //...
        //TODO: Register collection as dirty with the UoW if necessary
        //$this->_changed();
    }

    /**
     * INTERNAL:
     * loadRelated
     *
     * @param mixed $name
     * @return boolean
     * @todo New implementation & maybe move elsewhere.
     */
    /*public function loadRelated($name = null)
    {
        $list = array();
        $query = new Doctrine_Query($this->_mapper->getConnection());

        if ( ! isset($name)) {
            foreach ($this->_data as $record) {
                // FIXME: composite key support
                $ids = $record->identifier();
                $value = count($ids) > 0 ? array_pop($ids) : null;
                if ($value !== null) {
                    $list[] = $value;
                }
            }
            $query->from($this->_mapper->getComponentName()
                    . '(' . implode(", ",$this->_mapper->getTable()->getPrimaryKeys()) . ')');
            $query->where($this->_mapper->getComponentName()
                    . '.id IN (' . substr(str_repeat("?, ", count($list)),0,-2) . ')');

            return $query;
        }

        $rel = $this->_mapper->getTable()->getRelation($name);

        if ($rel instanceof Doctrine_Relation_LocalKey || $rel instanceof Doctrine_Relation_ForeignKey) {
            foreach ($this->_data as $record) {
                $list[] = $record[$rel->getLocal()];
            }
        } else {
            foreach ($this->_data as $record) {
                $ids = $record->identifier();
                $value = count($ids) > 0 ? array_pop($ids) : null;
                if ($value !== null) {
                    $list[] = $value;
                }
            }
        }

        $dql = $rel->getRelationDql(count($list), 'collection');
        $coll = $query->query($dql, $list);

        $this->populateRelated($name, $coll);
    }*/

    /**
     * INTERNAL:
     * populateRelated
     *
     * @param string $name
     * @param Doctrine_Collection $coll
     * @return void
     * @todo New implementation & maybe move elsewhere.
     */
    /*protected function populateRelated($name, Doctrine_Collection $coll)
    {
        $rel     = $this->_mapper->getTable()->getRelation($name);
        $table   = $rel->getTable();
        $foreign = $rel->getForeign();
        $local   = $rel->getLocal();

        if ($rel instanceof Doctrine_Relation_LocalKey) {
            foreach ($this->_data as $key => $record) {
                foreach ($coll as $k => $related) {
                    if ($related[$foreign] == $record[$local]) {
                        $this->_data[$key]->_setRelated($name, $related);
                    }
                }
            }
        } else if ($rel instanceof Doctrine_Relation_ForeignKey) {
            foreach ($this->_data as $key => $record) {
                if ( ! $record->exists()) {
                    continue;
                }
                $sub = new Doctrine_Collection($rel->getForeignComponentName());

                foreach ($coll as $k => $related) {
                    if ($related[$foreign] == $record[$local]) {
                        $sub->add($related);
                        $coll->remove($k);
                    }
                }

                $this->_data[$key]->_setRelated($name, $sub);
            }
        } else if ($rel instanceof Doctrine_Relation_Association) {
            // @TODO composite key support
            $identifier = (array)$this->_mapper->getClassMetadata()->getIdentifier();
            $asf        = $rel->getAssociationFactory();
            $name       = $table->getComponentName();

            foreach ($this->_data as $key => $record) {
                if ( ! $record->exists()) {
                    continue;
                }
                $sub = new Doctrine_Collection($rel->getForeignComponentName());
                foreach ($coll as $k => $related) {
                    $idField = $identifier[0];
                    if ($related->get($local) == $record[$idField]) {
                        $sub->add($related->get($name));
                    }
                }
                $this->_data[$key]->_setRelated($name, $sub);

            }
        }
    }*/
    
    /**
     * INTERNAL:
     * Sets a flag that indicates whether the collection is currently being hydrated.
     * This has the following consequences:
     * 1) During hydration, bidirectional associations are completed automatically
     *    by setting the back reference.
     * 2) During hydration no change notifications are reported to the UnitOfWork.
     *    I.e. that means add() etc. do not cause the collection to be scheduled
     *    for an update.
     *
     * @param boolean $bool
     */
    public function _setHydrationFlag($bool)
    {
        $this->_hydrationFlag = $bool;
    }

    /**
     * INTERNAL: Takes a snapshot from this collection.
     *
     * Snapshots are used for diff processing, for example
     * when a fetched collection has three elements, then two of those
     * are being removed the diff would contain one element.
     *
     * Collection::save() attaches the diff with the help of last snapshot.
     * 
     * @return void
     */
    public function _takeSnapshot()
    {
        $this->_snapshot = $this->_data;
    }

    /**
     * INTERNAL: Returns the data of the last snapshot.
     *
     * @return array    returns the data in last snapshot
     */
    public function _getSnapshot()
    {
        return $this->_snapshot;
    }

    /**
     * INTERNAL: Processes the difference of the last snapshot and the current data.
     *
     * an example:
     * Snapshot with the objects 1, 2 and 4
     * Current data with objects 2, 3 and 5
     *
     * The process would remove objects 1 and 4
     *
     * @return Doctrine_Collection
     * @todo Move elsewhere
     */
    public function processDiff() 
    {
        foreach (array_udiff($this->_snapshot, $this->_data, array($this, "_compareRecords")) as $record) {
            $record->delete();
        }
        return $this;
    }

    /**
     * Creates an array representation of the collection.
     *
     * @param boolean $deep
     * @return array
     */
    public function toArray($deep = false, $prefixKey = false)
    {
        $data = array();
        foreach ($this as $key => $record) {
            $key = $prefixKey ? get_class($record) . '_' .$key:$key;
            $data[$key] = $record->toArray($deep, $prefixKey);
        }
        
        return $data;
    }
    
    /**
     * Checks whether the collection is empty.
     *
     * @return boolean TRUE if the collection is empty, FALSE otherwise.
     */
    public function isEmpty()
    {
        // Note: Little "trick". Empty arrays evaluate to FALSE. No need to count().
        return ! (bool)$this->_data;
    }

    /**
     * Populate a Doctrine_Collection from an array of data.
     *
     * @param string $array 
     * @return void
     */
    public function fromArray($array, $deep = true)
    {
        $data = array();
        foreach ($array as $rowKey => $row) {
            $this[$rowKey]->fromArray($row, $deep);
        }
    }

    /**
     * Synchronizes a Doctrine_Collection with data from an array.
     *
     * it expects an array representation of a Doctrine_Collection similar to the return
     * value of the toArray() method. It will create Dectrine_Records that don't exist
     * on the collection, update the ones that do and remove the ones missing in the $array
     *
     * @param array $array representation of a Doctrine_Collection
     */
    public function synchronizeFromArray(array $array)
    {
        foreach ($this as $key => $record) {
            if (isset($array[$key])) {
                $record->synchronizeFromArray($array[$key]);
                unset($array[$key]);
            } else {
                // remove records that don't exist in the array
                $this->remove($key);
            }
        }

        // create new records for each new row in the array
        foreach ($array as $rowKey => $row) {
            $this[$rowKey]->fromArray($row);
        }
    }

    /**
     * INTERNAL: getDeleteDiff
     *
     * @return array
     */
    public function getDeleteDiff()
    {
        return array_udiff($this->_snapshot, $this->_data, array($this, "_compareRecords"));
    }

    /**
     * INTERNAL getInsertDiff
     *
     * @return array
     */
    public function getInsertDiff()
    {
        return array_udiff($this->_data, $this->_snapshot, array($this, "_compareRecords"));
    }

    /**
     * Compares two records. To be used on _snapshot diffs using array_udiff.
     * 
     * @return integer
     */
    protected function _compareRecords($a, $b)
    {
        if ($a->getOid() == $b->getOid()) {
            return 0;
        }
        return ($a->getOid() > $b->getOid()) ? 1 : -1;
    }

    /**
     *
     * @param <type> $deep
     */
    public function free($deep = false)
    {
        foreach ($this->getData() as $key => $record) {
            if ( ! ($record instanceof Doctrine_Null)) {
                $record->free($deep);
            }
        }

        $this->_data = array();

        if ($this->_owner) {
            $this->_owner->free($deep);
            $this->_owner = null;
        }
    }


    /**
     * getIterator
     * 
     * @return object ArrayIterator
     */
    public function getIterator()
    {
        $data = $this->_data;
        return new ArrayIterator($data);
    }

    /**
     * returns a string representation of this object
     */
    public function __toString()
    {
        return Doctrine_Lib::getCollectionAsString($this);
    }
    
    /**
     * INTERNAL: Gets the association mapping of the collection.
     * 
     * @return Doctrine::ORM::Mapping::AssociationMapping
     */
    public function getMapping()
    {
        return $this->relation;
    }
    
    /**
     * @todo Experiment. Waiting for 5.3 closures.
     * Example usage:
     * 
     * $map = $coll->mapElements(function($key, $entity) {
     *     return array($entity->id, $entity->name);
     * });
     * 
     * or:
     * 
     * $map = $coll->mapElements(function($key, $entity) {
     *     return array($entity->name, strtoupper($entity->name));
     * });
     * 
     */
    public function mapElements($lambda) {
        $result = array();
        foreach ($this->_data as $key => $entity) {
            list($key, $value) = each($lambda($key, $entity));
            $result[$key] = $value;
        }
        return $result;
    }
    
    /**
     * Clears the collection.
     *
     * @return void
     */
    public function clear()
    {
        //TODO: Register collection as dirty with the UoW if necessary
        //TODO: If oneToMany() && shouldDeleteOrphan() delete entities
        /*if ($this->_association->isOneToMany() && $this->_association->shouldDeleteOrphans()) {
            foreach ($this->_data as $entity) {
                $this->_em->delete($entity);
            }
        }*/
        $this->_data = array();
    }
    
    private function _changed()
    {
        /*if ( ! $this->_em->getUnitOfWork()->isCollectionScheduledForUpdate($this)) {
            $this->_em->getUnitOfWork()->scheduleCollectionUpdate($this);
        }*/  
    }
    
    /* Serializable implementation */
    
    /**
     * Serializes the collection.
     * This method is automatically called when the Collection is serialized.
     *
     * Part of the implementation of the Serializable interface.
     *
     * @return array
     */
    public function serialize()
    {
        $vars = get_object_vars($this);

        unset($vars['reference']);
        unset($vars['relation']);
        unset($vars['expandable']);
        unset($vars['expanded']);
        unset($vars['generator']);

        return serialize($vars);
    }

    /**
     * Reconstitutes the collection object from it's serialized form.
     * This method is automatically called everytime the Collection object is unserialized.
     *
     * Part of the implementation of the Serializable interface.
     *
     * @param string $serialized The serialized data
     *
     * @return void
     */
    public function unserialize($serialized)
    {
        $manager = Doctrine_ORM_EntityManager::getActiveEntityManager();
        $connection = $manager->getConnection();
        
        $array = unserialize($serialized);

        foreach ($array as $name => $values) {
            $this->$name = $values;
        }

        $keyColumn = isset($array['keyField']) ? $array['keyField'] : null;

        if ($keyColumn !== null) {
            $this->_keyField = $keyColumn;
        }
    }
}
