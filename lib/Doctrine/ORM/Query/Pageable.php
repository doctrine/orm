<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

interface Pageable
{
    /**
     * Sets the position of the first result to retrieve (the "offset").
     *
     * @param integer $firstResult The first result to return.
     *
     * @return static This query object.
     */
    public function setFirstResult($firstResult);

    /**
     * Gets the position of the first result the query object was set to retrieve (the "offset").
     * Returns NULL if {@link setFirstResult} was not applied to this query.
     *
     * @return integer The position of the first result.
     */
    public function getFirstResult();

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param integer $maxResults
     *
     * @return static This query object.
     */
    public function setMaxResults($maxResults);

    /**
     * Gets the maximum number of results the query object was set to retrieve (the "limit").
     * Returns NULL if {@link setMaxResults} was not applied to this query.
     *
     * @return integer Maximum number of results.
     */
    public function getMaxResults();
}
