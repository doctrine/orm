<?php

namespace Doctrine\Performance\Mock;

use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Query;

/**
 * A persister that doesn't actually load given objects
 */
class NonLoadingPersister extends BasicEntityPersister
{
    public function __construct()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function load(
        array $criteria,
        $entity = null,
        $assoc = null,
        array $hints = array(),
        $lockMode = 0,
        $limit = null,
        array $orderBy = null
    ) {
        return $entity;
    }
}