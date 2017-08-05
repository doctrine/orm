<?php

declare(strict_types=1);

namespace Doctrine\ORM\Repository;

use Doctrine\ORM\EntityManagerInterface;

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
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager The EntityManager instance.
     * @param string                               $entityName    The name of the entity.
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getRepository(EntityManagerInterface $entityManager, $entityName);
}
