<?php

declare(strict_types=1);

namespace Doctrine\ORM\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use function spl_object_id;

/**
 * This factory is used to create default repository objects for entities at runtime.
 */
final class DefaultRepositoryFactory implements RepositoryFactory
{
    /**
     * The list of EntityRepository instances.
     *
     * @var ObjectRepository[]
     */
    private $repositoryList = [];

    /**
     * {@inheritdoc}
     */
    public function getRepository(EntityManagerInterface $entityManager, $entityName)
    {
        $repositoryHash = $entityManager->getClassMetadata($entityName)->getClassName() . spl_object_id($entityManager);

        return $this->repositoryList[$repositoryHash]
                ?? $this->repositoryList[$repositoryHash] = $this->createRepository($entityManager, $entityName);
    }

    /**
     * Create a new repository instance for an entity class.
     *
     * @param EntityManagerInterface $entityManager The EntityManager instance.
     * @param string                 $entityName    The name of the entity.
     *
     * @return ObjectRepository
     */
    private function createRepository(EntityManagerInterface $entityManager, $entityName)
    {
        /** @var ClassMetadata $metadata */
        $metadata            = $entityManager->getClassMetadata($entityName);
        $repositoryClassName = $metadata->getCustomRepositoryClassName()
            ?: $entityManager->getConfiguration()->getDefaultRepositoryClassName();

        return new $repositoryClassName($entityManager, $metadata);
    }
}
