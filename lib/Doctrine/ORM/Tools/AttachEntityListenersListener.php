<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Mechanism to programmatically attach entity listeners.
 *
 * @author Fabio B. SIlva <fabio.bat.silva@gmail.com>
 *
 * @since 2.5
 */
class AttachEntityListenersListener
{
    /**
     * @var array[]
     */
    private $entityListeners = [];

    /**
     * Adds a entity listener for a specific entity.
     *
     * @param string      $entityClass      The entity to attach the listener.
     * @param string      $listenerClass    The listener class.
     * @param string      $eventName        The entity lifecycle event.
     * @param string|null $listenerCallback The listener callback method or NULL to use $eventName.
     *
     * @return void
     */
    public function addEntityListener($entityClass, $listenerClass, $eventName, $listenerCallback = null)
    {
        $this->entityListeners[ltrim($entityClass, '\\')][] = [
            'event'  => $eventName,
            'class'  => $listenerClass,
            'method' => $listenerCallback ?: $eventName
        ];
    }

    /**
     * Processes event and attach the entity listener.
     *
     * @param \Doctrine\ORM\Event\LoadClassMetadataEventArgs $event
     *
     * @return void
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        /** @var $metadata \Doctrine\ORM\Mapping\ClassMetadata */
        $metadata = $event->getClassMetadata();

        if ( ! isset($this->entityListeners[$metadata->getClassName()])) {
            return;
        }

        foreach ($this->entityListeners[$metadata->getClassName()] as $listener) {
            $listenerClassName = $metadata->fullyQualifiedClassName($listener['class']);

            $metadata->addEntityListener($listener['event'], $listenerClassName, $listener['method']);
        }

        unset($this->entityListeners[$metadata->getClassName()]);
    }
}
