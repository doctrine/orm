<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\Common\Collections;

use \Countable;
use \IteratorAggregate;
use \ArrayAccess;
use \ArrayIterator;

/**
 * A Collection is a wrapper around a php array and just like a php array a
 * collection instance can be a list, a set or a map, depending on how it is used.
 *
 * @author robo
 */
class Collection implements Countable, IteratorAggregate, ArrayAccess
{
    /**
     * An array containing the entries of this collection.
     * This is the wrapped php array.
     *
     * @var array
     */
    protected $_data = array();

    /**
     *
     * @param <type> $elements
     */
    public function __construct(array $elements = array())
    {
        $this->_data = $elements;
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
     * returns the first entry in the collection
     *
     * @return mixed
     */
    public function first()
    {
        return reset($this->_data);
    }

    /**
     * returns the last record in the collection
     *
     * @return mixed
     */
    public function last()
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
     * Removes an entry with a specific key from the collection.
     *
     * @param mixed $key
     * @return mixed
     */
    public function remove($key)
    {
        $removed = $this->_data[$key];
        unset($this->_data[$key]);
        return $removed;
    }

    /**
     * Removes the specified element from the collection, if it is found.
     *
     * @param mixed $element
     * @return boolean
     */
    public function removeElement($element)
    {
        $key = array_search($element, $this->_data, true);
        if ($key !== false) {
            unset($this->_data[$key]);
            return true;
        }
        return false;
    }

    /**
     * @see containsKey()
     */
    public function __isset($key)
    {
        return $this->containsKey($key);
    }

    /**
     * @see remove()
     */
    public function __unset($key)
    {
        return $this->remove($key);
    }

    /* ArrayAccess implementation */

    /**
     * @see containsKey()
     */
    public function offsetExists($offset)
    {
        return $this->containsKey($offset);
    }

    /**
     * @see get()
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @see add()
     * @see set()
     */
    public function offsetSet($offset, $value)
    {
        if ( ! isset($offset)) {
            return $this->add($value);
        }
        return $this->set($offset, $value);
    }

    /**
     * @see remove()
     */
    public function offsetUnset($offset)
    {
        return $this->remove($offset);
    }

    /* END ArrayAccess implementation */

    /**
     * Checks whether the collection contains a specific key/index.
     *
     * @param mixed $key The key to check for.
     * @return boolean TRUE if the given key/index exists, FALSE otherwise.
     */
    public function containsKey($key)
    {
        return isset($this->_data[$key]);
    }

    /**
     * Checks whether the given element is contained in the collection.
     * Only element values are compared, not keys. The comparison of two elements
     * is strict, that means not only the value but also the type must match.
     * For objects this means reference equality.
     *
     * @param mixed $element
     * @return boolean TRUE if the given element is contained in the collection,
     *          FALSE otherwise.
     */
    public function contains($element)
    {
        return in_array($element, $this->_data, true);
    }

    /**
     * Tests for the existance of an element that satisfies the given predicate.
     *
     * @param function $func
     * @return boolean TRUE if the predicate is TRUE for at least one element, FALSE otherwise.
     */
    public function exists(Closure $func) {
        foreach ($this->_data as $key => $element)
            if ($func($key, $element))
                return true;
        return false;
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $otherColl
     * @todo Impl
     */
    public function containsAll($otherColl)
    {
        throw new DoctrineException("Not yet implemented.");
    }

    /**
     * Searches for a given element and, if found, returns the corresponding key/index
     * of that element. The comparison of two elements is strict, that means not
     * only the value but also the type must match.
     * For objects this means reference equality.
     *
     * @param mixed $element The element to search for.
     * @return mixed The key/index of the element or FALSE if the element was not found.
     */
    public function search($element)
    {
        return array_search($element, $this->_data, true);
    }

    /**
     * Gets the element with the given key/index.
     *
     * @param mixed $key The key.
     * @return mixed The element or NULL, if no element exists for the given key.
     */
    public function get($key)
    {
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
        return null;
    }

    /**
     * Gets all keys/indexes.
     *
     * @return array
     */
    public function getKeys()
    {
        return array_keys($this->_data);
    }

    /**
     * Gets all elements.
     *
     * @return array
     */
    public function getElements()
    {
        return array_values($this->_data);
    }

    /**
     * Returns the number of elements in the collection.
     *
     * Implementation of the Countable interface.
     *
     * @return integer  The number of elements in the collection.
     */
    public function count()
    {
        return count($this->_data);
    }

    /**
     * Adds/sets an element in the collection at the index / with the specified key.
     *
     * When the collection is a Map this is like put(key,value)/add(key,value).
     * When the collection is a List this is like add(position,value).
     *
     * @param integer $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->_data[$key] = $value;
    }

    /**
     * Adds an element to the collection.
     *
     * @param mixed $value
     * @param string $key
     * @return boolean Always returns TRUE.
     */
    public function add($value)
    {
        $this->_data[] = $value;
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
     * Note: This is preferrable over count() == 0.
     *
     * @return boolean TRUE if the collection is empty, FALSE otherwise.
     */
    public function isEmpty()
    {
        // Note: Little "trick". Empty arrays evaluate to FALSE. No need to count().
        return ! (bool)$this->_data;
    }

    /**
     * Gets an iterator that enables foreach() iteration over the elements in
     * the collection.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        $data = $this->_data;
        return new ArrayIterator($data);
    }

    /**
     * Applies the given function to each element in the collection and returns
     * a new collection with the modified values.
     *
     * @param function $func
     */
    public function map(Closure $func)
    {
        return new Collection(array_map($func, $this->_data));
    }

    /**
     * Applies the given function to each element in the collection and returns
     * a new collection with the new values.
     *
     * @param function $func
     */
    public function filter(Closure $func)
    {
        return new Collection(array_filter($this->_data, $func));
    }

    /**
     * returns a string representation of this object
     */
    public function __toString()
    {
        return __CLASS__ . '@' . spl_object_hash($this);
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

