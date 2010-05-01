<?php
/*
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

use Closure, Countable, IteratorAggregate, ArrayAccess;

/**
 * The missing (SPL) Collection/Array/OrderedMap interface.
 * 
 * A Collection resembles the nature of a regular PHP array. That is,
 * it is essentially an <b>ordered map</b> that can also be used
 * like a list.
 * 
 * A Collection has an internal iterator just like a PHP array. In addition,
 * a Collection can be iterated with external iterators, which is preferrable.
 * To use an external iterator simply use the foreach language construct to
 * iterate over the collection (which calls {@link getIterator()} internally) or
 * explicitly retrieve an iterator though {@link getIterator()} which can then be
 * used to iterate over the collection.
 * You can not rely on the internal iterator of the collection being at a certain
 * position unless you explicitly positioned it before. Prefer iteration with
 * external iterators.
 * 
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
interface Collection extends Countable, IteratorAggregate, ArrayAccess
{
    /**
     * Adds an element at the end of the collection.
     *
     * @param mixed $element The element to add.
     * @return boolean Always TRUE.
     */
    function add($element);
    
    /**
     * Clears the collection, removing all elements.
     */
    function clear();

    /**
     * Checks whether an element is contained in the collection.
     * This is an O(n) operation, where n is the size of the collection.
     *
     * @param mixed $element The element to search for.
     * @return boolean TRUE if the collection contains the element, FALSE otherwise.
     */
    function contains($element);

    /**
     * Checks whether the collection is empty (contains no elements).
     *
     * @return boolean TRUE if the collection is empty, FALSE otherwise.
     */
    function isEmpty();

    /**
     * Removes the element at the specified index from the collection.
     * 
     * @param string|integer $key The kex/index of the element to remove.
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
     * Gets the element at the specified key/index.
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
     * Gets a native PHP array representation of the collection.
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
     *
     */
    function key();

    /**
     * Gets the element of the collection at the current iterator position.
     *
     */
    function current();

    /**
     * Moves the internal iterator position to the next element.
     *
     */
    function next();

    /**
     * Tests for the existence of an element that satisfies the given predicate.
     *
     * @param Closure $p The predicate.
     * @return boolean TRUE if the predicate is TRUE for at least one element, FALSE otherwise.
     */
    function exists(Closure $p);

    /**
     * Returns all the elements of this collection that satisfy the predicate p.
     * The order of the elements is preserved.
     *
     * @param Closure $p The predicate used for filtering.
     * @return Collection A collection with the results of the filter operation.
     */
    function filter(Closure $p);

    /**
     * Applies the given predicate p to all elements of this collection,
     * returning true, if the predicate yields true for all elements.
     *
     * @param Closure $p The predicate.
     * @return boolean TRUE, if the predicate yields TRUE for all elements, FALSE otherwise.
     */
    function forAll(Closure $p);

    /**
     * Applies the given function to each element in the collection and returns
     * a new collection with the elements returned by the function.
     *
     * @param Closure $func
     * @return Collection
     */
    function map(Closure $func);

    /**
     * Partitions this collection in two collections according to a predicate.
     * Keys are preserved in the resulting collections.
     *
     * @param Closure $p The predicate on which to partition.
     * @return array An array with two elements. The first element contains the collection
     *               of elements where the predicate returned TRUE, the second element
     *               contains the collection of elements where the predicate returned FALSE.
     */
    function partition(Closure $p);

    /**
     * Gets the index/key of a given element. The comparison of two elements is strict,
     * that means not only the value but also the type must match.
     * For objects this means reference equality.
     *
     * @param mixed $element The element to search for.
     * @return mixed The key/index of the element or FALSE if the element was not found.
     */
    function indexOf($element);
}