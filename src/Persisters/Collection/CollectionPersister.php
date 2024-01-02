<?php

declare(strict_types=1);

namespace Doctrine\ORM\Persisters\Collection;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\PersistentCollection;

/**
 * Define the behavior that should be implemented by all collection persisters.
 */
interface CollectionPersister
{
    /**
     * Deletes the persistent state represented by the given collection.
     */
    public function delete(PersistentCollection $collection): void;

    /**
     * Updates the given collection, synchronizing its state with the database
     * by inserting, updating and deleting individual elements.
     */
    public function update(PersistentCollection $collection): void;

    /**
     * Counts the size of this persistent collection.
     */
    public function count(PersistentCollection $collection): int;

    /**
     * Slices elements.
     *
     * @return mixed[]
     */
    public function slice(PersistentCollection $collection, int $offset, int|null $length = null): array;

    /**
     * Checks for existence of an element.
     */
    public function contains(PersistentCollection $collection, object $element): bool;

    /**
     * Checks for existence of a key.
     */
    public function containsKey(PersistentCollection $collection, mixed $key): bool;

    /**
     * Gets an element by key.
     */
    public function get(PersistentCollection $collection, mixed $index): mixed;

    /**
     * Loads association entities matching the given Criteria object.
     *
     * @return mixed[]
     */
    public function loadCriteria(PersistentCollection $collection, Criteria $criteria): array;
}
