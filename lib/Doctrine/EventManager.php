<?php

#namespace Doctrine::Common;

class Doctrine_EventManager
{
    private $_listeners = array();

    public function dispatchEvent($event) {
        $argIsCallback = is_string($event);
        $callback = $argIsCallback ? $event : $event->getType();
        
        if (isset($this->_listeners[$callback])) {
            $event = $argIsCallback ? new Doctrine_Event($event) : $event;
            foreach ($this->_listeners[$callback] as $listener) {
                $listener->$callback($event);
            }
        }

        return ! $event->getDefaultPrevented();
    }
    
    public function getListeners($callback = null) {
        return $callback ? $this->_listeners[$callback] : $this->_listeners;
    }
    
    public function hasListeners($callback) {
        return isset($this->_listeners[$callback]);
    }
    
    public function registerEventListener($listener, $callbacks) {
        // TODO: maybe check for duplicate registrations?
        if (is_array($callbacks)) {
            foreach ($callbacks as $callback) {
                $this->_listeners[$callback] = $listener;
            }
        } else {
            $this->_listeners[$callbacks] = $listener;
        }
    }
}

?>