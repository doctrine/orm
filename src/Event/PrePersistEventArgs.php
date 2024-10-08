<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;

final class PrePersistEventArgs extends LifecycleEventArgs implements CommitEventIdAware
{
    /** @var string|null */
    private $commitEventId = null;

    public function __construct($object, EntityManagerInterface $objectManager, ?string $commitEventId = null)
    {
        $this->commitEventId = $commitEventId;

        parent::__construct($object, $objectManager);
    }

    public function getCommitEventId(): ?string
    {
        return $this->commitEventId;
    }
}
