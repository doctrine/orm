<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs as BaseLifecycleEventArgs;

/**
 * Lifecycle Events are triggered by the UnitOfWork during lifecycle transitions
 * of entities.
 *
 * @link   www.doctrine-project.org
 * @since  2.0
 * @author Roman Borschel <roman@code-factory.de>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
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
     * @return \Doctrine\ORM\EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->getObjectManager();
    }
}
