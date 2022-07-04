<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Deprecations\Deprecation;
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
    /** @var string|null */
    private $entityClass;

    /**
     * @param string|null $entityClass Optional entity class.
     */
    public function __construct(EntityManagerInterface $em, $entityClass = null)
    {
        parent::__construct($em);

        $this->entityClass = $entityClass;
    }

    /**
     * Retrieves associated EntityManager.
     *
     * @deprecated 2.13. Use {@see getObjectManager} instead.
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/9875',
            'Method %s() is deprecated and will be removed in Doctrine ORM 3.0. Use getObjectManager() instead.',
            __METHOD__
        );

        return $this->getObjectManager();
    }

    /**
     * Name of the entity class that is cleared, or empty if all are cleared.
     *
     * @deprecated Clearing the entity manager partially is deprecated. This method will be removed in 3.0.
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
     * @deprecated Clearing the entity manager partially is deprecated. This method will be removed in 3.0.
     *
     * @return bool
     */
    public function clearsAllEntities()
    {
        return $this->entityClass === null;
    }
}
