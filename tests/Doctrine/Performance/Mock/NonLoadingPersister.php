<?php

namespace Doctrine\Performance\Mock;

use Doctrine\ORM\Mapping\AssociationMetadata;
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
        ?AssociationMetadata $assoc = null,
        array $hints = [],
        $lockMode = null,
        $limit = null,
        array $orderBy = null
    ) {
        return $entity;
    }
}
