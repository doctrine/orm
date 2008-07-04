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

#namespace Doctrine::ORM;

/**
 * A persistent collection of entities.
 * A collection object is strongly typed in the sense that it can only contain
 * entities of a specific type or one it's subtypes.
 *
 * @package     Doctrine
 * @subpackage  Collection
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @todo Rename to EntityCollection
 */
class Doctrine_Collection extends Doctrine_Access implements Countable, IteratorAggregate, Serializable
{
    protected $_entityBaseType;
    
    /**
     * An array containing the records of this collection.
     *
     * @var array                     
     */
    protected $data = array();
    
    /**
     * The mapper object used to map the records of this collection to the database.
     *
     * @var Doctrine_Mapper
     */
    protected $_mapper;

    /**
     * A snapshot of the fetched data.
     *
     * @var array                
     */
    protected $_snapshot = array();

    /**
     * This record this collection is attached to, if any.
     * 
     * @var Doctrine_Entity
     */
    protected $_owner;

    /**
     * The reference field of the collection.
     *
     * @var string $referenceField         
     */
    protected $referenceField;

    /**
     * The relation this collection is related to, if any.
     *
     * @var Doctrine_Relation               
     */
    protected $relation;

    /**
     * The name of the column that is used for collection key mapping.
     *
     * @var string
     */
    protected $_keyField;

    /**
     * Helper variable. Used for fast null value testing.
     *
     * @var Doctrine_Null
     */
    //protected static $null;
    
    /**
     * The EntityManager.
     *
     * @var EntityManager
     */
    protected $_em;

    /**
     * Constructor.
     *
     * @param Doctrine_Mapper|string $mapper   The mapper used by the collection.
     * @param string $keyColumn                The field name that will be used as the key
     *                                         in the collection.
     */
    public function __construct($entityBaseType, $keyField = null)
    {
        $this->_entityBaseType = $entityBaseType;
        $this->_em = Doctrine_EntityManagerFactory::getManager($entityBaseType);
        $this->_mapper = $this->_em->getEntityPersister($entityBaseType);

        if ($keyField === null) {
            $keyField = $this->_mapper->getClassMetadata()->getBoundQueryPart('indexBy');
        }

        if ($keyField === null) {
        	//$keyField = $mapper->getClassMetadata()->getAttribute(Doctrine::ATTR_COLL_KEY);
        }

        if ($keyField !== null) {
            if ( ! $this->_mapper->getClassMetadata()->hasField($keyField)) {
                throw new Doctrine_Collection_Exception("Invalid field '$keyField' can't be uses as key.");
            }
            $this->_keyField = $keyField;
        }
    }

    /**
     * setData
     *
     * @param array $data
     * @return Doctrine_Collection
     */
    public function setData(array $data) 
    {
        $this->data = $data;
    }

    /**
     * Serializes the collection.
     * This method is automatically called when this Doctrine_Collection is serialized.
     *
     * Part of the implementation of the Serializable interface.
     *
     * @return array
     */
    public function serialize()
    {
        $vars = get_object_vars($this);

        unset($vars['reference']);
        unset($vars['reference_field']);
        unset($vars['relation']);
        unset($vars['expandable']);
        unset($vars['expanded']);
        unset($vars['generator']);

        $vars['_mapper'] = $vars['_mapper']->getComponentName();

        return serialize($vars);
    }

    /**
     * Reconstitutes the collection object from it's serialized form.
     * This method is automatically called everytime a Doctrine_Collection object is unserialized.
     *
     * Part of the implementation of the Serializable interface.
     *
     * @param string $serialized The serialized data
     *
     * @return void
     */
    public function unserialize($serialized)
    {
        $manager = Doctrine_EntityManagerFactory::getManager();
        $connection = $manager->getConnection();
        
        $array = unserialize($serialized);

        foreach ($array as $name => $values) {
            $this->$name = $values;
        }

        $this->_mapper = $manager->getEntityPersister($this->_entityBaseType);

        $keyColumn = isset($array['keyField']) ? $array['keyField'] : null;
        if ($keyColumn === null) {
            $keyColumn = $this->_mapper->getClassMetadata()->getBoundQueryPart('indexBy');
        }

        if ($keyColumn !== null) {
            $this->_keyField = $keyColumn;
        }
    }

    /**
     * setKeyField
     * sets the key column for this collection
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
     * getKeyField
     * returns the name of the key column
     *
     * @return string
     */
    public function getKeyField()
    {
        return $this->_keyField;
    }

    /**
     * getData
     * returns all the records as an array
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * getFirst
     * returns the first record in the collection
     *
     * @return mixed
     */
    public function getFirst()
    {
        return reset($this->data);
    }

    /**
     * getLast
     * returns the last record in the collection
     *
     * @return mixed
     */
    public function getLast()
    {
        return end($this->data);
    }
    
    /**
     * returns the last record in the collection
     *
     * @return mixed
     */
    public function end()
    {
        return end($this->data);
    }
    
    /**
     * returns the current key
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->data);
    }
    
    /**
     * INTERNAL:
     * sets a reference pointer
     *
     * @return void
     */
    public function setReference(Doctrine_Entity $entity, Doctrine_Relation $relation)
    {
        $this->_owner = $entity;
        $this->relation  = $relation;

        if ($relation instanceof Doctrine_Relation_ForeignKey || 
                $relation instanceof Doctrine_Relation_LocalKey) {
            $this->referenceField = $relation->getForeignFieldName();
            $value = $entity->get($relation->getLocalFieldName());
            foreach ($this->data as $entity) {
                if ($value !== null) {
                    $entity->set($this->referenceField, $value, false);
                } else {
                    $entity->set($this->referenceField, $this->_owner, false);
                }
            }
        } else if ($relation instanceof Doctrine_Relation_Association) {

        }
    }

    /**
     * INTERNAL:
     * getReference
     *
     * @return mixed
     */
    public function getReference()
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
        $removed = $this->data[$key];
        unset($this->data[$key]);
        return $removed;
    }

    /**
     * Checks whether the collection contains an entity.
     *
     * @param mixed $key                    the key of the element
     * @return boolean
     * @todo Rename to containsKey().
     */
    public function contains($key)
    {
        return isset($this->data[$key]);
    }
    
    /**
     *
     */
    public function search(Doctrine_Entity $record)
    {
        return array_search($record, $this->data, true);
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
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }

    /**
     * Returns the primary keys of all records in the collection.
     *
     * @return array   An array containing all primary keys.
     * @todo Rename.
     */
    public function getPrimaryKeys()
    {
        $list = array();
        $idFieldNames = (array)$this->_mapper->getClassMetadata()->getIdentifier();

        foreach ($this->data as $record) {
            if (is_array($record)) {
                if (count($idFieldNames) > 1) {
                    $id = array();
                    foreach ($idFieldNames as $fieldName) {
                         if (isset($record[$fieldName])) {
                             $id[] = $record[$fieldName];
                         }
                    }
                    $list[] = $id;
                } else {
                    $idField = $idFieldNames[0];
                    if (isset($record[$idField])) {
                        $list[] = $record[$idField];
                    }
                }
            } else {
                // @todo does not take composite keys into account
                $ids = $record->identifier();
                $list[] = count($ids) > 0 ? array_pop($ids) : null;
            }
        }
        
        return $list;
    }

    /**
     * returns all keys
     * @return array
     */
    public function getKeys()
    {
        return array_keys($this->data);
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
        return count($this->data);
    }

    /**
     * set
     * @param integer $key
     * @param Doctrine_Entity $record
     * @return void
     * @internal Can't type-hint the second parameter to Doctrine_Entity because we need
     *           to adhere to the Doctrine_Access::set() signature.
     */
    public function set($key, $entity)
    {
        if ( ! $entity instanceof Doctrine_Entity) {
            throw new Doctrine_Collection_Exception('Value variable in set is not an instance of Doctrine_Entity');
        }

        if (isset($this->referenceField)) {
            $entity->set($this->referenceField, $this->_owner, false);
        }
        $this->data[$key] = $entity;
    }

    /**
     * adds a record to collection
     * @param Doctrine_Entity $record              record to be added
     * @param string $key                          optional key for the record
     * @return boolean
     */
    public function add($record, $key = null)
    {
        /** @TODO Use raw getters/setters */
        if ( ! $record instanceof Doctrine_Entity) {
            throw new Doctrine_Record_Exception('Value variable in set is not an instance of Doctrine_Entity.');
        }

        if (isset($this->referenceField)) {
            $value = $this->_owner->get($this->relation->getLocalFieldName());

            if ($value !== null) {
                $record->set($this->referenceField, $value, false);
            } else {
                $record->set($this->referenceField, $this->_owner, false);
            }
        }
        /*
         * for some weird reason in_array cannot be used here (php bug ?)
         *
         * if used it results in fatal error : [ nesting level too deep ]
         */
        foreach ($this->data as $val) {
            if ($val === $record) {
                return false;
            }
        }

        if (isset($key)) {
            if (isset($this->data[$key])) {
                return false;
            }
            $this->data[$key] = $record;
            return true;
        }

         // why is this not checked when the keyColumn is set? 
        if (isset($this->_keyField)) {
            $value = $record->get($this->_keyField);
            if ($value === null) {
                throw new Doctrine_Collection_Exception("Couldn't create collection index. Record field '".$this->_keyField."' was null.");
            }
            $this->data[$value] = $record;
        } else {
            $this->data[] = $record;
        }
        
        return true;
    }

    /**
     * INTERNAL:
     * loadRelated
     *
     * @param mixed $name
     * @return boolean
     */
    public function loadRelated($name = null)
    {
        $list = array();
        $query = new Doctrine_Query($this->_mapper->getConnection());

        if ( ! isset($name)) {
            foreach ($this->data as $record) {
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
            foreach ($this->data as $record) {
                $list[] = $record[$rel->getLocal()];
            }
        } else {
            foreach ($this->data as $record) {
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
    }

    /**
     * INTERNAL:
     * populateRelated
     *
     * @param string $name
     * @param Doctrine_Collection $coll
     * @return void
     */
    public function populateRelated($name, Doctrine_Collection $coll)
    {
        $rel     = $this->_mapper->getTable()->getRelation($name);
        $table   = $rel->getTable();
        $foreign = $rel->getForeign();
        $local   = $rel->getLocal();

        if ($rel instanceof Doctrine_Relation_LocalKey) {
            foreach ($this->data as $key => $record) {
                foreach ($coll as $k => $related) {
                    if ($related[$foreign] == $record[$local]) {
                        $this->data[$key]->_setRelated($name, $related);
                    }
                }
            }
        } else if ($rel instanceof Doctrine_Relation_ForeignKey) {
            foreach ($this->data as $key => $record) {
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

                $this->data[$key]->_setRelated($name, $sub);
            }
        } else if ($rel instanceof Doctrine_Relation_Association) {
            // @TODO composite key support
            $identifier = (array)$this->_mapper->getClassMetadata()->getIdentifier();
            $asf        = $rel->getAssociationFactory();
            $name       = $table->getComponentName();

            foreach ($this->data as $key => $record) {
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
                $this->data[$key]->_setRelated($name, $sub);

            }
        }
    }

    /**
     * getNormalIterator
     * returns normal iterator - an iterator that will not expand this collection
     *
     * @return Doctrine_Iterator_Normal
     */
    public function getNormalIterator()
    {
        return new Doctrine_Collection_Iterator_Normal($this);
    }

    /**
     * takeSnapshot
     * takes a snapshot from this collection
     *
     * snapshots are used for diff processing, for example
     * when a fetched collection has three elements, then two of those
     * are being removed the diff would contain one element
     *
     * Doctrine_Collection::save() attaches the diff with the help of last
     * snapshot.
     *
     * @return Doctrine_Collection
     */
    public function takeSnapshot()
    {
        $this->_snapshot = $this->data;
        return $this;
    }

    /**
     * getSnapshot
     * returns the data of the last snapshot
     *
     * @return array    returns the data in last snapshot
     */
    public function getSnapshot()
    {
        return $this->_snapshot;
    }

    /**
     * processDiff
     * processes the difference of the last snapshot and the current data
     *
     * an example:
     * Snapshot with the objects 1, 2 and 4
     * Current data with objects 2, 3 and 5
     *
     * The process would remove objects 1 and 4
     *
     * @return Doctrine_Collection
     */
    public function processDiff() 
    {
        foreach (array_udiff($this->_snapshot, $this->data, array($this, "_compareRecords")) as $record) {
            $record->delete();
        }
        return $this;
    }

    /**
     * toArray
     * Mimics the result of a $query->execute(array(), Doctrine::FETCH_ARRAY);
     *
     * @param boolean $deep
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
     * fromArray
     *
     * Populate a Doctrine_Collection from an array of data
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
     * synchronizeFromArray
     * synchronizes a Doctrine_Collection with data from an array
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
     * exportTo
     *
     * Export a Doctrine_Collection to one of the supported Doctrine_Parser formats
     *
     * @param string $type 
     * @param string $deep 
     * @return void
     * @todo Move elsewhere.
     */
    public function exportTo($type, $deep = false)
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
     * Import data to a Doctrine_Collection from one of the supported Doctrine_Parser formats
     *
     * @param string $type 
     * @param string $data 
     * @return void
     * @todo Move elsewhere.
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
     * getDeleteDiff
     *
     * @return void
     */
    public function getDeleteDiff()
    {
        return array_udiff($this->_snapshot, $this->data, array($this, "_compareRecords"));
    }

    /**
     * getInsertDiff
     *
     * @return void
     */
    public function getInsertDiff()
    {
        return array_udiff($this->data, $this->_snapshot, array($this, "_compareRecords"));
    }

    /**
     * _compareRecords
     * Compares two records. To be used on _snapshot diffs using array_udiff.
     */
    protected function _compareRecords($a, $b)
    {
        if ($a->getOid() == $b->getOid()) {
            return 0;
        }
        
        return ($a->getOid() > $b->getOid()) ? 1 : -1;
    }

    /**
     * save
     * Saves all records of this collection and processes the 
     * difference of the last snapshot and the current data.
     *
     * @param Doctrine_Connection $conn     optional connection parameter
     * @return Doctrine_Collection
     */
    public function save()
    {
        $conn = $this->_mapper->getConnection();
        
        try {
            $conn->beginInternalTransaction();
            
            $conn->transaction->addCollection($this);
            $this->processDiff();
            foreach ($this->getData() as $key => $record) {
                $record->save($conn);
            }
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $this;
    }

    /**
     * Deletes all records from the collection.
     *
     * @return void
     */
    public function delete($clearColl = false)
    {  
        $conn = $this->_mapper->getConnection();

        try {
            $conn->beginInternalTransaction();
            
            $conn->transaction->addCollection($this);
            foreach ($this as $key => $record) {
                $record->delete($conn);
            }

            $conn->commit();            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
        if ($clearColl) {
            $this->clear();            
        }
    }


    public function free($deep = false)
    {
        foreach ($this->getData() as $key => $record) {
            if ( ! ($record instanceof Doctrine_Null)) {
                $record->free($deep);
            }
        }

        $this->data = array();

        if ($this->_owner) {
            $this->_owner->free($deep);
            $this->_owner = null;
        }
    }


    /**
     * getIterator
     * @return object ArrayIterator
     */
    public function getIterator()
    {
        $data = $this->data;
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
     * returns the relation object
     * @return object Doctrine_Relation
     */
    public function getRelation()
    {
        return $this->relation;
    }
    
    public function clear()
    {
        $this->data = array();
    }
}
