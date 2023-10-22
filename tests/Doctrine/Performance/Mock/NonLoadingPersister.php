<?php

declare(strict_types=1);

namespace Doctrine\Performance\Mock;

use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;

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
        array $hints = [],
        $lockMode = null,
        $limit = null,
        ?array $orderBy = null
    ) {
        return $entity;
    }
}
