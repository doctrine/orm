<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Class that holds event arguments for a loadMetadata event.
 */
class LoadClassMetadataEventArgs extends EventArgs
{
    /** @var ClassMetadata */
    private $classMetadata;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(ClassMetadata $classMetadata, EntityManagerInterface $entityManager)
    {
        $this->classMetadata = $classMetadata;
        $this->entityManager = $entityManager;
    }

    /**
     * Retrieves the associated ClassMetadata.
     */
    public function getClassMetadata() : ClassMetadata
    {
        return $this->classMetadata;
    }

    /**
     * Retrieve associated EntityManager.
     */
    public function getEntityManager() : EntityManagerInterface
    {
        return $this->entityManager;
    }
}
