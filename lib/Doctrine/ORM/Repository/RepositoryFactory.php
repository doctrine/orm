<?php

declare(strict_types=1);

namespace Doctrine\ORM\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepositoryInterface;

/**
 * Interface for entity repository factory.
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @since 2.4
 */
interface RepositoryFactory
{
    /**
     * Gets the repository for an entity class.
     *
     * @param EntityManagerInterface $entityManager The EntityManager instance.
     * @param string                 $entityName    The name of the entity.
     */
    public function getRepository(EntityManagerInterface $entityManager, string $entityName) : EntityRepositoryInterface;
}
