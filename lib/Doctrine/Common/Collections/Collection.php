<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

#namespace Doctrine\Common\Collections;

#use \Countable;
#use \IteratorAggregate;
#use \Serializable;
#use \ArrayAccess;

/**
 * A Collection is a wrapper around a php array and just like a php array a
 * collection instance can be a list, a map or a hashmap, depending on how it
 * is used.
 *
 * @author robo
 */
class Doctrine_Common_Collection implements Countable, IteratorAggregate, Serializable, ArrayAccess {
    /**
     * An array containing the entries of this collection.
     * This is the wrapped php array.
     *
     * @var array
     */
    protected $_data = array();

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
     * Removes an entry from the collection.
     *
     * @param mixed $key
     * @return boolean
     */
    public function remove($key)
    {
        $removed = $this->_data[$key];
        unset($this->_data[$key]);
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
        //TODO: really only allow entities?
        if ( ! $value instanceof Doctrine_ORM_Entity) {
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
                $value->_internalSetReference($this->_backRefFieldName, $this->_owner);
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
     * Clears the collection.
     *
     * @return void
     */
    public function clear()
    {
        $this->_data = array();
    }
}
?>
