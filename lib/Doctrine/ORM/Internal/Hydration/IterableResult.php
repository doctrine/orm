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
    private $_hydrator;

    /** @var bool */
    private $_rewinded = false;

    /** @var int */
    private $_key = -1;

    /** @var mixed[]|null */
    private $_current = null;

    /**
     * @param AbstractHydrator $hydrator
     */
    public function __construct($hydrator)
    {
        $this->_hydrator = $hydrator;
    }

    /**
     * @return void
     *
     * @throws HydrationException
     */
    #[ReturnTypeWillChange]
    public function rewind()
    {
        if ($this->_rewinded === true) {
            throw new HydrationException('Can only iterate a Result once.');
        }

        $this->_current  = $this->next();
        $this->_rewinded = true;
    }

    /**
     * Gets the next set of results.
     *
     * @return mixed[]|false
     */
    #[ReturnTypeWillChange]
    public function next()
    {
        $this->_current = $this->_hydrator->hydrateRow();
        $this->_key++;

        return $this->_current;
    }

    /**
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function current()
    {
        return $this->_current;
    }

    /**
     * @return int
     */
    #[ReturnTypeWillChange]
    public function key()
    {
        return $this->_key;
    }

    /**
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function valid()
    {
        return $this->_current !== false;
    }
}
