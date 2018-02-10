<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs as BaseLifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Lifecycle Events are triggered by the UnitOfWork during lifecycle transitions
 * of entities.
 *
 * @link   www.doctrine-project.org
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
