<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;

final class PostRemoveEventArgs extends LifecycleEventArgs implements CommitEventIdAware
{
    /** @var string|null */
    private $commitEventId = null;

    public function __construct($object, EntityManagerInterface $objectManager, ?string $commitEventId = null)
    {
        $this->commitEventId = null;

        parent::__construct($object, $objectManager);
    }

    public function getCommitEventId(): ?string
    {
        return $this->commitEventId;
    }
}
