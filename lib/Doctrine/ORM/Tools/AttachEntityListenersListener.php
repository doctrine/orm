<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping;
use function ltrim;

/**
 * Mechanism to programmatically attach entity listeners.
 */
class AttachEntityListenersListener
{
    /** @var mixed[][] */
    private $entityListeners = [];

    /**
     * Adds a entity listener for a specific entity.
     *
     * @param string $entityClass      The entity to attach the listener.
     * @param string $listenerClass    The listener class.
     * @param string $eventName        The entity lifecycle event.
     * @param string $listenerCallback The listener callback method.
     */
    public function addEntityListener(
        string $entityClass,
        string $listenerClass,
        string $eventName,
        string $listenerCallback
    ) : void {
        $this->entityListeners[ltrim($entityClass, '\\')][] = [
            'event'  => $eventName,
            'class'  => $listenerClass,
            'method' => $listenerCallback,
        ];
    }

    /**
     * Processes event and attach the entity listener.
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event) : void
    {
        /** @var Mapping\ClassMetadata $metadata */
        $metadata = $event->getClassMetadata();

        if (! isset($this->entityListeners[$metadata->getClassName()])) {
            return;
        }

        foreach ($this->entityListeners[$metadata->getClassName()] as $listener) {
            $listenerClassName = $listener['class'];

            $metadata->addEntityListener($listener['event'], $listenerClassName, $listener['method']);
        }

        unset($this->entityListeners[$metadata->getClassName()]);
    }
}
