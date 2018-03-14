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
    /** @var object */
    private $entity;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(object $entity, EntityManagerInterface $entityManager)
    {
        $this->entity        = $entity;
        $this->entityManager = $entityManager;
    }

    public function getObject() : object
    {
        return $this->entity;
    }

    /**
     * Retrieves associated Entity.
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public function getObjectManager()
    {
        return $this->entityManager;
    }

    /**
     * Retrieves associated EntityManager.
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }
}
