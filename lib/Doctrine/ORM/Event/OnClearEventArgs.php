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
    /**
     * Retrieves associated EntityManager.
     *
     * @deprecated 2.13. Use {@see getObjectManager} instead.
     */
    public function getEntityManager(): EntityManagerInterface
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/9875',
            'Method %s() is deprecated and will be removed in Doctrine ORM 3.0. Use getObjectManager() instead.',
            __METHOD__,
        );

        return $this->getObjectManager();
    }
}
