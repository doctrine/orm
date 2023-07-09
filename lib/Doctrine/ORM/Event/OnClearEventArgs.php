<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\OnClearEventArgs as BaseOnClearEventArgs;

/**
 * Provides event arguments for the onClear event.
 *
 * @link        www.doctrine-project.org
 *
 * @extends BaseOnClearEventArgs<EntityManagerInterface>
 */
class OnClearEventArgs extends BaseOnClearEventArgs
{
    /** @param string|null $entityClass Optional entity class. */
    public function __construct(EntityManagerInterface $em, private $entityClass = null)
    {
        parent::__construct($em);
    }

    /**
     * Name of the entity class that is cleared, or empty if all are cleared.
     *
     * @return string|null
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Checks if event clears all entities.
     *
     * @return bool
     */
    public function clearsAllEntities()
    {
        return $this->entityClass === null;
    }
}
