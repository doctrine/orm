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
        object|null $entity = null,
        array|null $assoc = null,
        array $hints = [],
        int|null $lockMode = null,
        int|null $limit = null,
        array|null $orderBy = null,
    ): object|null {
        return $entity;
    }
}
