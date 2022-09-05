<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;

class PostRemoveEventArgs extends LifecycleEventArgs
{
    /** @param object $entity */
    public function __construct($entity, EntityManagerInterface $objectManager, private mixed $identifier)
    {
        parent::__construct($entity, $objectManager);
    }

    public function getIdentifier(): mixed
    {
        return $this->identifier;
    }
}
