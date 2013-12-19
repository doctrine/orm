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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM;

use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Persisters\BasicEntityPersister;

/**
 * A lazy collection that allow a fast count when using criteria object
 *
 * @since   2.5
 * @author  Michaël Gallego <mic.gallego@gmail.com>
 */
class LazyCriteriaCollection implements Collection
{
    /**
     * @var BasicEntityPersister
     */
    protected $entityPersister;

    /**
     * @var Criteria
     */
    protected $criteria;

    /**
     * @var ArrayCollection
     */
    protected $collection;

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * Allow to cache the count
     *
     * @var int
     */
    protected $count;

    /**
     * @param BasicEntityPersister $entityPersister
     * @param Criteria             $criteria
     */
    public function __construct(BasicEntityPersister $entityPersister, Criteria $criteria)
    {
        $this->entityPersister = $entityPersister;
        $this->criteria        = $criteria;
    }

    /**
     * Do an efficient count on the collection
     *
     * @return int
     */
    public function count()
    {
        if (null !== $this->count) {
            return $this->count;
        }

        $this->count = $this->entityPersister->count($this->criteria);

        return $this->count;
    }

    /**
     * {@inheritDoc}
     */
    function add($element)
    {
        $this->initialize();
        return $this->collection->add($element);
    }

    /**
     * {@inheritDoc}
     */
    function clear()
    {
        $this->initialize();
        $this->collection->clear();
    }

    /**
     * {@inheritDoc}
     */
    function contains($element)
    {
        $this->initialize();
        return $this->collection->contains($element);
    }

    /**
     * {@inheritDoc}
     */
    function isEmpty()
    {
        $this->initialize();
        return $this->collection->isEmpty();
    }

    /**
     * {@inheritDoc}
     */
    function remove($key)
    {
        $this->initialize();
        return $this->collection->remove($key);
    }

    /**
     * {@inheritDoc}
     */
    function removeElement($element)
    {
        $this->initialize();
        return $this->collection->removeElement($element);
    }

    /**
     * {@inheritDoc}
     */
    function containsKey($key)
    {
        $this->initialize();
        return $this->collection->containsKey($key);
    }

    /**
     * {@inheritDoc}
     */
    function get($key)
    {
        $this->initialize();
        return $this->collection->get($key);
    }

    /**
     * {@inheritDoc}
     */
    function getKeys()
    {
        $this->initialize();
        return $this->collection->getKeys();
    }

    /**
     * {@inheritDoc}
     */
    function getValues()
    {
        $this->initialize();
        return $this->collection->getValues();
    }

    /**
     * {@inheritDoc}
     */
    function set($key, $value)
    {
        $this->initialize();
        $this->collection->set($key, $value);
    }

    /**
     * {@inheritDoc}
     */
    function toArray()
    {
        $this->initialize();
        return $this->collection->toArray();
    }

    /**
     * {@inheritDoc}
     */
    function first()
    {
        $this->initialize();
        return $this->collection->first();
    }

    /**
     * {@inheritDoc}
     */
    function last()
    {
        $this->initialize();
        return $this->collection->last();
    }

    /**
     * {@inheritDoc}
     */
    function key()
    {
        $this->initialize();
        return $this->collection->key();
    }

    /**
     * {@inheritDoc}
     */
    function current()
    {
        $this->initialize();
        return $this->collection->current();
    }

    /**
     * {@inheritDoc}
     */
    function next()
    {
        $this->initialize();
        return $this->collection->next();
    }

    /**
     * {@inheritDoc}
     */
    function exists(Closure $p)
    {
        $this->initialize();
        return $this->collection->exists($p);
    }

    /**
     * {@inheritDoc}
     */
    function filter(Closure $p)
    {
        $this->initialize();
        return $this->collection->filter($p);
    }

    /**
     * {@inheritDoc}
     */
    function forAll(Closure $p)
    {
        $this->initialize();
        return $this->collection->forAll($p);
    }

    /**
     * {@inheritDoc}
     */
    function map(Closure $func)
    {
        $this->initialize();
        return $this->collection->map($func);
    }

    /**
     * {@inheritDoc}
     */
    function partition(Closure $p)
    {
        $this->initialize();
        return $this->collection->partition($p);
    }

    /**
     * {@inheritDoc}
     */
    function indexOf($element)
    {
        $this->initialize();
        return $this->collection->indexOf($element);
    }

    /**
     * {@inheritDoc}
     */
    function slice($offset, $length = null)
    {
        $this->initialize();
        return $this->collection->slice($offset, $length);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        $this->initialize();
        return $this->collection->getIterator();
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        $this->initialize();
        return $this->collection->offsetExists($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        $this->initialize();
        return $this->collection->offsetGet($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->initialize();
        return $this->collection->offsetSet($offset, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        $this->initialize();
        return $this->collection->offsetUnset($offset);
    }

    /**
     * Initialize the collection
     *
     * @return void
     */
    protected function initialize()
    {
        if ($this->initialized) {
            return;
        }

        $elements = $this->entityPersister->loadCriteria($this->criteria);

        $this->collection  = new ArrayCollection($elements);
        $this->initialized = true;
    }
}
