<?php

declare(strict_types=1);

namespace Doctrine\ORM\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Interface for entity repository factory.
 */
interface RepositoryFactory
{
    /**
     * Gets the repository for an entity class.
     *
     * @param EntityManagerInterface $entityManager The EntityManager instance.
     * @param class-string<T>        $entityName    The name of the entity.
     *
     * @return EntityRepository<T>
     *
     * @template T of object
     */
    public function getRepository(EntityManagerInterface $entityManager, string $entityName): EntityRepository;
}
