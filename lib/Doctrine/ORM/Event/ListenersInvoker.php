<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * A method invoker based on entity lifecycle.
 *
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since   2.4
 */
class ListenersInvoker
{
    const INVOKE_NONE       = 0;
    const INVOKE_LISTENERS  = 1;
    const INVOKE_CALLBACKS  = 2;
    const INVOKE_MANAGER    = 4;

    /**
     * @var \Doctrine\ORM\Mapping\EntityListenerResolver The Entity listener resolver.
     */
    private $resolver;

    /**
     * The EventManager used for dispatching events.
     *
     * @var \Doctrine\Common\EventManager
     */
    private $eventManager;

    /**
     * Initializes a new ListenersInvoker instance.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->eventManager = $em->getEventManager();
        $this->resolver     = $em->getConfiguration()->getEntityListenerResolver();
    }

    /**
     * Get the subscribed event systems
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $metadata  The entity metadata.
     * @param string                              $eventName The entity lifecycle event.
     *
     * @return integer Bitmask of subscribed event systems.
     */
    public function getSubscribedSystems(ClassMetadata $metadata, $eventName)
    {
        $invoke = self::INVOKE_NONE;

        if (isset($metadata->lifecycleCallbacks[$eventName])) {
            $invoke |= self::INVOKE_CALLBACKS;
        }

        if (isset($metadata->entityListeners[$eventName])) {
            $invoke |= self::INVOKE_LISTENERS;
        }

        if ($this->eventManager->hasListeners($eventName)) {
            $invoke |= self::INVOKE_MANAGER;
        }

        return $invoke;
    }

    /**
     * Dispatches the lifecycle event of the given entity.
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $metadata  The entity metadata.
     * @param string                              $eventName The entity lifecycle event.
     * @param object                              $entity    The Entity on which the event occurred.
     * @param \Doctrine\Common\EventArgs          $event     The Event args.
     * @param integer                             $invoke    Bitmask to invoke listeners.
     */
    public function invoke(ClassMetadata $metadata, $eventName, $entity, EventArgs $event, $invoke)
    {
        if ($invoke & self::INVOKE_CALLBACKS) {
            foreach ($metadata->lifecycleCallbacks[$eventName] as $callback) {
                $entity->$callback($event);
            }
        }

        if ($invoke & self::INVOKE_LISTENERS) {
            foreach ($metadata->entityListeners[$eventName] as $listener) {
                $class      = $listener['class'];
                $method     = $listener['method'];
                $instance   = $this->resolver->resolve($class);

                $instance->$method($entity, $event);
            }
        }

        if ($invoke & self::INVOKE_MANAGER) {
            $this->eventManager->dispatchEvent($eventName, $event);
        }
    }
}
