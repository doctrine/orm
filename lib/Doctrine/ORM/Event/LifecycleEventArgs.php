<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs as BaseLifecycleEventArgs;

/**
 * Lifecycle Events are triggered by the UnitOfWork during lifecycle transitions
 * of entities.
 *
 * @extends BaseLifecycleEventArgs<EntityManagerInterface>
 */
class LifecycleEventArgs extends BaseLifecycleEventArgs
{
    /**
     * Retrieves associated Entity.
     *
     * @return object
     */
    public function getEntity()
    {
        return $this->getObject();
    }

    /**
     * Retrieves associated EntityManager.
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->getObjectManager();
    }
}
