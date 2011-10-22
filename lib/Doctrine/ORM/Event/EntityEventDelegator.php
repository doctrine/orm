<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
*/

namespace Doctrine\ORM\Event;

use \Doctrine\Common\EventSubscriber;
use \LogicException;

/**
 * Delegate events only for certain entities they are registered for.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @since 2.2
 */
class EntityEventDelegator implements EventSubscriber
{
    /**
     * Keeps track of all the event listeners.
     *
     * @var array
     */
    private $listeners = array();

    /**
     * If frozen no new event listeners can be added.
     *
     * @var bool
     */
    private $frozen = false;

    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param string|array $events The event(s) to listen on.
     * @param string|array $entities The entities to trigger this listener for
     * @param object $listener The listener object.
     */
    public function addEventListener($events, $entities, $listener)
    {
        if ($this->frozen) {
            throw new LogicException("Cannot add event listeners after EntityEventDelegator::getSubscribedEvents() " .
                "is called once. This happens when you register the delegator with the event manager.");
        }

        // Picks the hash code related to that listener
        $hash = spl_object_hash($listener);

        foreach ((array) $events as $event) {
            // Overrides listener if a previous one was associated already
            // Prevents duplicate listeners on same event (same instance only)
            $this->listeners[$event][$hash] = array('listener' => $listener, 'entities' => array_flip((array)$entities));
        }
    }

    /**
     * Adds an EventSubscriber. The subscriber is asked for all the events he is
     * interested in and added as a listener for these events.
     *
     * @param Doctrine\Common\EventSubscriber $subscriber The subscriber.
     */
    public function addEventSubscriber(EventSubscriber $subscriber, $entities)
    {
        $this->addEventListener($subscriber->getSubscribedEvents(), $entities, $subscriber);
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        $this->frozen = true;
        return array_keys($this->listeners);
    }

    /**
     * Delegate the event to an appropriate listener
     *
     * @param $eventName
     * @param $event
     * @return void
     */
    public function __call($eventName, $args)
    {
        $event = $args[0];
        foreach ($this->listeners[$eventName] AS $listenerData) {
            $class = get_class($event->getEntity());
            if (isset($listenerData['entities'][$class])) {
                $listenerData['listener']->$eventName($event);
            }
        }
    }
}
