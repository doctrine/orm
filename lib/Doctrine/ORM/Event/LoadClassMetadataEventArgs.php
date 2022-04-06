<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs as BaseLoadClassMetadataEventArgs;

/**
 * Class that holds event arguments for a loadMetadata event.
 *
 * @extends BaseLoadClassMetadataEventArgs<ClassMetadata<object>, EntityManagerInterface>
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
