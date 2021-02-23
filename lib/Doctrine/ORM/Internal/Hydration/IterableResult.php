<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

use Iterator;

/**
 * Represents a result structure that can be iterated over, hydrating row-by-row
 * during the iteration. An IterableResult is obtained by AbstractHydrator#iterate().
 */
class IterableResult implements Iterator
{
    /** @var AbstractHydrator */
    private $hydrator;

    /** @var bool */
    private $rewinded = false;

    /** @var int */
    private $key = -1;

    /** @var object|null */
    private $current;

    /**
     * @param AbstractHydrator $hydrator
     */
    public function __construct($hydrator)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * @throws HydrationException
     */
    public function rewind()
    {
        if ($this->rewinded === true) {
            throw new HydrationException('Can only iterate a Result once.');
        }

        $this->current  = $this->next();
        $this->rewinded = true;
    }

    /**
     * Gets the next set of results.
     *
     * @return mixed[]|false
     */
    public function next()
    {
        $this->current = $this->hydrator->hydrateRow();
        $this->key++;

        return $this->current;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->current!==false;
    }
}
