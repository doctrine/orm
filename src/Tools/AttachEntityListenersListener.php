<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Builder\EntityListenerBuilder;

use function assert;
use function ltrim;

/**
 * Mechanism to programmatically attach entity listeners.
 */
class AttachEntityListenersListener
{
    /**
     * @var array<class-string, list<array{
     *     event: Events::*|null,
     *     class: class-string,
     *     method: string|null,
     * }>>
     */
    private array $entityListeners = [];

    /**
     * Adds an entity listener for a specific entity.
     *
     * @param class-string          $entityClass      The entity to attach the listener.
     * @param class-string          $listenerClass    The listener class.
     * @param Events::*|null        $eventName        The entity lifecycle event.
     * @param non-falsy-string|null $listenerCallback The listener callback method or NULL to use $eventName.
     */
    public function addEntityListener(
        string $entityClass,
        string $listenerClass,
        string|null $eventName = null,
        string|null $listenerCallback = null,
    ): void {
        $this->entityListeners[ltrim($entityClass, '\\')][] = [
            'event'  => $eventName,
            'class'  => $listenerClass,
            'method' => $listenerCallback ?? $eventName,
        ];
    }

    /**
     * Processes event and attach the entity listener.
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        $metadata = $event->getClassMetadata();

        if (! isset($this->entityListeners[$metadata->name])) {
            return;
        }

        $tagetEntities = $this->entityListeners[$metadata->name];

        foreach(class_implements($metadata->name, true) as $interface) {
            echo $interface;
            if(isset($this->entityListeners[$interface])) {
                $tagetEntities = array_merge($tagetEntities, $this->entityListeners[$interface]);
            }
        }

        foreach(class_parents($metadata->name, true) as $parent) {
            echo $parent;
            if(isset($this->entityListeners[$parent])) {
                $tagetEntities = array_merge($tagetEntities, $this->entityListeners[$parent]);
            }
        }

        foreach(class_uses($metadata->name, true) as $trait) {
            echo $trait;
            if(isset($this->entityListeners[$trait])) {
                $tagetEntities = array_merge($tagetEntities, $this->entityListeners[$trait]);
            }
        }		

        foreach ($tagetEntities as $listener) {
            if ($listener['event'] === null) {
                EntityListenerBuilder::bindEntityListener($metadata, $listener['class']);
            } else {
                assert($listener['method'] !== null);
                $metadata->addEntityListener($listener['event'], $listener['class'], $listener['method']);
            }
        }
    }
}
