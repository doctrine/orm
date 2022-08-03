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

namespace Doctrine\ORM\Persisters\Collection;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\PersistentCollection;

/**
 * Collection persister interface
 * Define the behavior that should be implemented by all collection persisters.
 */
interface CollectionPersister
{
    /**
     * Deletes the persistent state represented by the given collection.
     *
     * @return void
     */
    public function delete(PersistentCollection $collection);

    /**
     * Updates the given collection, synchronizing its state with the database
     * by inserting, updating and deleting individual elements.
     *
     * @return void
     */
    public function update(PersistentCollection $collection);

    /**
     * Counts the size of this persistent collection.
     *
     * @return int
     */
    public function count(PersistentCollection $collection);

    /**
     * Slices elements.
     *
     * @param int $offset
     * @param int $length
     *
     * @return mixed[]
     */
    public function slice(PersistentCollection $collection, $offset, $length = null);

    /**
     * Checks for existence of an element.
     *
     * @param object $element
     *
     * @return bool
     */
    public function contains(PersistentCollection $collection, $element);

    /**
     * Checks for existence of a key.
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function containsKey(PersistentCollection $collection, $key);

    /**
     * Gets an element by key.
     *
     * @param mixed $index
     *
     * @return mixed
     */
    public function get(PersistentCollection $collection, $index);

    /**
     * Loads association entities matching the given Criteria object.
     *
     * @return mixed[]
     */
    public function loadCriteria(PersistentCollection $collection, Criteria $criteria);
}
