<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

use Iterator;
use ReturnTypeWillChange;

/**
 * Represents a result structure that can be iterated over, hydrating row-by-row
 * during the iteration. An IterableResult is obtained by AbstractHydrator#iterate().
 *
 * @deprecated
 */
class IterableResult implements Iterator
{
    /** @var AbstractHydrator */
    private $hydrator;

    /** @var bool */
    private $rewinded = false;

    /** @var int */
    private $key = -1;

    /** @var mixed[]|null */
    private $current = null;

    /** @param AbstractHydrator $hydrator */
    public function __construct($hydrator)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * @return void
     *
     * @throws HydrationException
     */
    #[ReturnTypeWillChange]
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
    #[ReturnTypeWillChange]
    public function next()
    {
        $this->current = $this->hydrator->hydrateRow();
        $this->key++;

        return $this->current;
    }

    /** @return mixed */
    #[ReturnTypeWillChange]
    public function current()
    {
        return $this->current;
    }

    /** @return int */
    #[ReturnTypeWillChange]
    public function key()
    {
        return $this->key;
    }

    /** @return bool */
    #[ReturnTypeWillChange]
    public function valid()
    {
        return $this->current !== false;
    }
}
