<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
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
     * @param string      $entityClass      The entity to attach the listener.
     * @param string      $listenerClass    The listener class.
     * @param string      $eventName        The entity lifecycle event.
     * @param string|null $listenerCallback The listener callback method or NULL to use $eventName.
     */
    public function addEntityListener($entityClass, $listenerClass, $eventName, $listenerCallback = null)
    {
        $this->entityListeners[ltrim($entityClass, '\\')][] = [
            'event'  => $eventName,
            'class'  => $listenerClass,
            'method' => $listenerCallback ?: $eventName,
        ];
    }

    /**
     * Processes event and attach the entity listener.
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        /** @var ClassMetadata $metadata */
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
