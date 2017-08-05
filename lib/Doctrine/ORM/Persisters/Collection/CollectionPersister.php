<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Collection;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\PersistentCollection;

/**
 * Collection persister interface
 * Define the behavior that should be implemented by all collection persisters.
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since 2.5
 */
interface CollectionPersister
{
    /**
     * Deletes the persistent state represented by the given collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $collection
     *
     * @return void
     */
    public function delete(PersistentCollection $collection);

    /**
     * Updates the given collection, synchronizing its state with the database
     * by inserting, updating and deleting individual elements.
     *
     * @param \Doctrine\ORM\PersistentCollection $collection
     *
     * @return void
     */
    public function update(PersistentCollection $collection);

    /**
     * Counts the size of this persistent collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $collection
     *
     * @return integer
     */
    public function count(PersistentCollection $collection);

    /**
     * Slices elements.
     *
     * @param \Doctrine\ORM\PersistentCollection $collection
     * @param integer                            $offset
     * @param integer                            $length
     *
     * @return  array
     */
    public function slice(PersistentCollection $collection, $offset, $length = null);

    /**
     * Checks for existence of an element.
     *
     * @param \Doctrine\ORM\PersistentCollection $collection
     * @param object                             $element
     *
     * @return boolean
     */
    public function contains(PersistentCollection $collection, $element);

    /**
     * Checks for existence of a key.
     *
     * @param \Doctrine\ORM\PersistentCollection $collection
     * @param mixed                              $key
     *
     * @return boolean
     */
    public function containsKey(PersistentCollection $collection, $key);

    /**
     * Removes an element.
     *
     * @param \Doctrine\ORM\PersistentCollection $collection
     * @param object                             $element
     *
     * @return mixed
     */
    public function removeElement(PersistentCollection $collection, $element);

    /**
     * Gets an element by key.
     *
     * @param \Doctrine\ORM\PersistentCollection $collection
     * @param mixed                              $index
     *
     * @return mixed
     */
    public function get(PersistentCollection $collection, $index);

    /**
     * Loads association entities matching the given Criteria object.
     *
     * @param \Doctrine\ORM\PersistentCollection    $collection
     * @param \Doctrine\Common\Collections\Criteria $criteria
     *
     * @return array
     */
    public function loadCriteria(PersistentCollection $collection, Criteria $criteria);
}
