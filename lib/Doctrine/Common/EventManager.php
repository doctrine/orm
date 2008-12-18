<?php
/*
 *  $Id$
 *
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
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine::Common;

/**
 * The EventManager is the central point of Doctrine's event listener system.
 * Listeners are registered on the manager and events are dispatched through the
 * manager.
 * 
 * @author Roman Borschel <roman@code-factory.org>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @since 2.0
 */
class Doctrine_Common_EventManager
{
    /**
     * Map of registered listeners.
     * <event> => <listeners> 
     *
     * @var array
     */
    private $_listeners = array();

    /**
     * Dispatches an event to all registered listeners.
     *
     * @param string|Event $event  The name of the event or the event object.
     * @return boolean
     */
    public function dispatchEvent($event)
    {
        $argIsCallback = is_string($event);
        $callback = $argIsCallback ? $event : $event->getType();

        if (isset($this->_listeners[$callback])) {
            $event = $argIsCallback ? new Doctrine_Event($event) : $event;
            foreach ($this->_listeners[$callback] as $listener) {
                $listener->$callback($event);
            }
            return ! $event->getDefaultPrevented();
        }
        return true;
    }

    /**
     * Gets the listeners of a specific event or all listeners.
     *
     * @param string $event  The name of the event.
     * @return 
     */
    public function getListeners($event = null)
    {
        return $event ? $this->_listeners[$event] : $this->_listeners;
    }

    /**
     * Checks whether an event has any registered listeners.
     *
     * @param string $event
     * @return boolean
     */
    public function hasListeners($event)
    {
        return isset($this->_listeners[$event]);
    }

    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param string|array $events  The event(s) to listen on.
     * @param object $listener  The listener object.
     */
    public function addEventListener($events, $listener)
    {
        // TODO: maybe check for duplicate registrations?
        foreach ((array)$events as $event) {
            $this->_listeners[$event] = $listener;
        }
    }
    
    /**
     * Adds an EventSubscriber. The subscriber is asked for all the events he is
     * interested in and added as a listener for these events.
     * 
     * @param Doctrine::Common::Event::EventSubscriber $subscriber  The subscriber.
     */
    public function addEventSubscriber(Doctrine_Common_EventSubscriber $subscriber)
    {
        $this->addEventListener($subscriber->getSubscribedEvents(), $subscriber);
    }
}

?>