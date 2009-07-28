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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Common\Collections;

use \Closure;
use \Countable;
use \IteratorAggregate;
use \ArrayAccess;
use \ArrayIterator;

/**
 * A Collection is a thin wrapper around a php array. Like a php array it is essentially
 * an ordered map.
 *
 * @author Roman S. Borschel <roman@code-factory.org>
 * @since 2.0
 */
class Collection implements Countable, IteratorAggregate, ArrayAccess
{
    /**
     * An array containing the entries of this collection.
     * This is the wrapped php array.
     *
     * @var array
     */
    protected $_elements;

    /**
     * Initializes a new Collection.
     *
     * @param array $elements
     */
    public function __construct(array $elements = array())
    {
        $this->_elements = $elements;
    }

    /**
     * Unwraps the array contained in the Collection instance.
     *
     * @return array The wrapped array.
     */
    public function unwrap()
    {
        return $this->_elements;
    }

    /**
     * Gets the first element in the collection.
     *
     * @return mixed
     */
    public function first()
    {
        return reset($this->_elements);
    }

    /**
     * Gets the last element in the collection.
     *
     * @return mixed
     */
    public function last()
    {
        return end($this->_elements);
    }

    /**
     * Gets the current key.
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->_elements);
    }

    /**
     * Removes an element with a specific key/index from the collection.
     *
     * @param mixed $key
     * @return mixed
     */
    public function remove($key)
    {
        if (isset($this->_elements[$key])) {
            $removed = $this->_elements[$key];
            unset($this->_elements[$key]);
            return $removed;
        }
        return null;
    }

    /**
     * Removes the specified element from the collection, if it is found.
     *
     * @param mixed $element
     * @return boolean
     */
    public function removeElement($element)
    {
        $key = array_search($element, $this->_elements, true);
        if ($key !== false) {
            $removed = $this->_elements[$key];
            unset($this->_elements[$key]);
            return $removed;
        }
        return null;
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
        return isset($this->_elements[$key]);
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
        return in_array($element, $this->_elements, true);
    }

    /**
     * Tests for the existance of an element that satisfies the given predicate.
     *
     * @param Closure $p The predicate.
     * @return boolean TRUE if the predicate is TRUE for at least one element, FALSE otherwise.
     */
    public function exists(Closure $p)
    {
        foreach ($this->_elements as $key => $element)
            if ($p($key, $element)) return true;
        return false;
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
        return array_search($element, $this->_elements, true);
    }

    /**
     * Gets the element with the given key/index.
     *
     * @param mixed $key The key.
     * @return mixed The element or NULL, if no element exists for the given key.
     */
    public function get($key)
    {
        if (isset($this->_elements[$key])) {
            return $this->_elements[$key];
        }
        return null;
    }

    /**
     * Gets all keys/indexes of the collection elements.
     *
     * @return array
     */
    public function getKeys()
    {
        return array_keys($this->_elements);
    }

    /**
     * Gets all elements.
     *
     * @return array
     */
    public function getElements()
    {
        return array_values($this->_elements);
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
        return count($this->_elements);
    }

    /**
     * Adds/sets an element in the collection at the index / with the specified key.
     *
     * When the collection is a Map this is like put(key,value)/add(key,value).
     * When the collection is a List this is like add(position,value).
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->_elements[$key] = $value;
    }

    /**
     * Adds an element to the collection.
     *
     * @param mixed $value
     * @return boolean Always TRUE.
     */
    public function add($value)
    {
        $this->_elements[] = $value;
        return true;
    }

    /**
     * Checks whether the collection is empty.
     * 
     * Note: This is preferrable over count() == 0.
     *
     * @return boolean TRUE if the collection is empty, FALSE otherwise.
     */
    public function isEmpty()
    {
        // Note: Little "trick". Empty arrays evaluate to FALSE. No need to count().
        return ! (bool) $this->_elements;
    }

    /**
     * Gets an iterator for iterating over the elements in the collection.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_elements);
    }

    /**
     * Applies the given function to each element in the collection and returns
     * a new collection with the elements returned by the function.
     *
     * @param Closure $func
     */
    public function map(Closure $func)
    {
        return new Collection(array_map($func, $this->_elements));
    }

    /**
     * Returns all the elements of this collection that satisfy the predicate p.
     * The order of the elements is preserved.
     *
     * @param Closure $p The predicate used for filtering.
     * @return Collection A collection with the results of the filter operation.
     */
    public function filter(Closure $p)
    {
        return new Collection(array_filter($this->_elements, $p));
    }

    /**
     * Applies the given predicate p to all elements of this collection,
     * returning true, if the predicate yields true for all elements.
     *
     * @param Closure $p The predicate.
     * @return boolean TRUE, if the predicate yields TRUE for all elements, FALSE otherwise.
     */
    public function forAll(Closure $p)
    {
        foreach ($this->_elements as $key => $element) {
            if ( ! $p($key, $element)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Partitions this collection in two collections according to a predicate.
     * Keys are preserved in the resulting collections.
     *
     * @param Closure $p The predicate on which to partition.
     * @return array An array with two elements. The first element contains the collection
     *               of elements where the predicate returned TRUE, the second element
     *               contains the collection of elements where the predicate returned FALSE.
     */
    public function partition(Closure $p)
    {
        $coll1 = $coll2 = array();
        foreach ($this->_elements as $key => $element) {
            if ($p($key, $element)) {
                $coll1[$key] = $element;
            } else {
                $coll2[$key] = $element;
            }
        }
        return array(new Collection($coll1), new Collection($coll2));
    }

    /**
     * Returns a string representation of this object.
     */
    public function __toString()
    {
        return __CLASS__ . '@' . spl_object_hash($this);
    }

    /**
     * Clears the collection.
     */
    public function clear()
    {
        $this->_elements = array();
    }
}
