<?php

declare(strict_types=1);

namespace Doctrine\Performance\Mock;

use Doctrine\DBAL\LockMode;
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
        ?object $entity = null,
        ?array $assoc = null,
        array $hints = [],
        LockMode|int|null $lockMode = null,
        ?int $limit = null,
        ?array $orderBy = null
    ): ?object {
        return $entity;
    }
}
