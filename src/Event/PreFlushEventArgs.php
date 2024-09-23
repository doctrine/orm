<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\ManagerEventArgs;
use Doctrine\Persistence\ObjectManager;

/**
 * Provides event arguments for the preFlush event.
 *
 * @link        www.doctrine-project.com
 *
 * @extends ManagerEventArgs<EntityManagerInterface>
 */
class PreFlushEventArgs extends ManagerEventArgs implements CommitEventIdAware
{
    /** @var string|null */
    private $commitEventId = null;

    public function __construct(ObjectManager $objectManager, ?string $commitEventId = null)
    {
        $this->commitEventId = $commitEventId;

        parent::__construct($objectManager);
    }

    public function getCommitEventId(): ?string
    {
        return $this->commitEventId;
    }

    /**
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
