<?php

declare(strict_types=1);

namespace Doctrine\ORM\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Interface for entity repository factory.
 */
interface RepositoryFactory
{
    /**
     * Gets the repository for an entity class.
     *
     * @param EntityManagerInterface $entityManager The EntityManager instance.
     * @param string                 $entityName    The name of the entity.
     *
     * @return ObjectRepository
     */
    public function getRepository(EntityManagerInterface $entityManager, $entityName);
}
