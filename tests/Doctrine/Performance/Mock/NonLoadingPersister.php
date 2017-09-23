<?php

declare(strict_types=1);

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
        array $hints = array(),
        $lockMode = null,
        $limit = null,
        array $orderBy = null
    ) {
        return $entity;
    }

    public function getIdentifier($entity) : array
    {
        // empty on purpose

        return [];
    }

    public function setIdentifier($entity, array $id) : void
    {
        // empty on purpose
    }

    public function loadById(array $identifier, $entity = null)
    {
        // empty on purpose

        return $entity;
    }
}
