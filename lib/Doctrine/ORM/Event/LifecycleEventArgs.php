<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Deprecations\Deprecation;
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
    /** @param object $object */
    public function __construct($object, EntityManagerInterface $objectManager)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/9875',
            'The %s class is deprecated and will be removed in ORM 3.0. Use %s instead.',
            self::class,
            BaseLifecycleEventArgs::class
        );

        parent::__construct($object, $objectManager);
    }

    /**
     * Retrieves associated Entity.
     *
     * @deprecated 2.13. Use {@see getObject} instead.
     *
     * @return object
     */
    public function getEntity()
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/9875',
            'Method %s() is deprecated and will be removed in Doctrine ORM 3.0. Use getObjectManager() instead.',
            __METHOD__
        );

        return $this->getObject();
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
}
