<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs as BaseLoadClassMetadataEventArgs;

/**
 * Class that holds event arguments for a loadMetadata event.
 *
 * @method __construct(ClassMetadata $classMetadata, EntityManagerInterface $objectManager)
 * @method ClassMetadata getClassMetadata()
 */
class LoadClassMetadataEventArgs extends BaseLoadClassMetadataEventArgs
{
    /**
     * Retrieve associated EntityManager.
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->getObjectManager();
    }
}
