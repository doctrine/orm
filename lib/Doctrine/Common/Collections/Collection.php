<?php

namespace Doctrine\Common\Collections;

/**
 * The missing (SPL) Collection/Array interface.
 * 
 * A Collection resembles the nature of a regular PHP array. That is,
 * it is essentially an ordered map that can syntactically also be used
 * like a list.
 * 
 * A Collection has an internal iterator just like a PHP array. In addition
 * a Collection can be iterated with external iterators, which is preferrable.
 * To use an external iterator simply use the foreach language construct to
 * iterator over the collection (which canns getIterator() internally) or
 * explicitly retrieve an iterator though getIterator() which can then be
 * used to iterate over the collection.
 * 
 * You can not rely on the internal iterator of the collection being at a certain
 * position unless you explicitly positioned it before. Prefer iteration with
 * external iterators.
 * 
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
interface Collection extends \Countable, \IteratorAggregate, \ArrayAccess
{
    /**
     * Adds an element to the collection.
     *
     * @param mixed $element The element to add.
     * @return boolean Always TRUE.
     */
    function add($element);
    
    /**
     * Clears the collection.
     */
    function clear();
    
    /**
     * Checks whether an element is contained in the collection.
     * This is an O(n) operation.
     *
     * @param mixed $element The element to check for.
     * @return boolean TRUE if the collection contains the element, FALSE otherwise.
     */
    function contains($element);
    
    /**
     * Checks whether the collection is empty.
     *
     * @return boolean TRUE if the collection is empty, FALSE otherwise.
     */
    function isEmpty();
    
    /**
     * Removes the element with the specified key/index from the collection.
     * 
     * @param string|integer $key The key/index of the element to remove.
     * @return mixed The removed element or NULL, if the collection did not contain the element.
     */
    function remove($key);
    
    /**
     * Removes an element from the collection.
     *
     * @param mixed $element The element to remove.
     * @return mixed The removed element or NULL, if the collection did not contain the element.
     */
    function removeElement($element);
    
    /**
     * Checks whether the collection contains an element with the specified key/index.
     * 
     * @param string|integer $key The key/index to check for.
     * @return boolean TRUE if the collection contains an element with the specified key/index,
     *          FALSE otherwise.
     */
    function containsKey($key);
    
    /**
     * Gets an element with a specified key / at a specified index.
     * 
     * @param string|integer $key The key/index of the element to retrieve.
     * @return mixed
     */
    function get($key);
    
    /**
     * Gets all keys/indices of the collection.
     *
     * @return array The keys/indices of the collection, in the order of the corresponding
     *          elements in the collection.
     */
    function getKeys();
    
    /**
     * Gets all values of the collection. 
     * 
     * @return array The values of all elements in the collection, in the order they
     *          appear in the collection.
     */
    function getValues();
    
    /**
     * Sets an element in the collection at the specified key/index.
     * 
     * @param string|integer $key The key/index of the element to set.
     * @param mixed $value The element to set.
     */
    function set($key, $value);
    
    /**
     * Gets a plain PHP array representation of the collection.
     * 
     * @return array
     */
    function toArray();
    
    /**
     * Sets the internal iterator to the first element in the collection and
     * returns this element.
     *
     * @return mixed
     */
    function first();
    
    /**
     * Sets the internal iterator to the last element in the collection and
     * returns this element.
     *
     * @return mixed
     */
    function last();
    
    /**
     * Gets the key/index of the element at the current iterator position.
     */
    function key();
    
    /**
     * Gets the element of the collection at the current iterator position.
     */
    function current();
    
    /**
     * Moves the internal iterator position to the next element.
     */
    function next();
}