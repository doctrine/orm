<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;

final class PostRemoveEventArgs extends LifecycleEventArgs
{
    /**
     * @var mixed
     */
    private $identifier;

    /**
     * @param mixed $identifier
     */
    public function __construct($entity, EntityManagerInterface $objectManager, $identifier)
    {
        parent::__construct($entity, $objectManager);

        $this->identifier = $identifier;
    }

    /**
     * Retrieves the first entity identifier as it was before removal.
     *
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
}
