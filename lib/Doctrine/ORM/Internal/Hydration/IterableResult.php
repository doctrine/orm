<?php
/**
 * Represents a result structure that can be iterated over, hydrating row-by-row
 * during the iteration. An IterableResult is obtained by AbstractHydrator#iterate().
 *
 * @author robo
 * @since 2.0
 */
class Doctrine_ORM_Internal_Hydration_IterableResult
{
    private $_hydrator;

    public function __construct($hydrator)
    {
        $this->_hydrator = $hydrator;
    }

    /**
     * Gets the next set of results.
     *
     * @return array
     */
    public function next()
    {
        return $this->_hydrator->hydrateRow();
    }
}

