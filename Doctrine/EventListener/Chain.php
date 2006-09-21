<?php
Doctrine::autoload('Doctrine_Access');

class Doctrine_EventListener_Chain extends Doctrine_Access {
    /**
     * @var array $listeners
     */
    private $listeners = array();
    /**
     * add
     *
     * @param Doctrine_EventListener $listener
     * @return void
     */
    public function add(Doctrine_EventListener $listener) {
        $this->listeners[] = $listener;
    }
    /**
     * returns a Doctrine_EvenListener on success
     * and null on failure
     *
     * @param mixed $key
     * @return mixed
     */
    public function get($key) {
        if( ! isset($this->listeners[$key]))
            return null;

        return $this->listeners[$key];
    }
    /**
     * set
     * 
     * @param mixed $key
     * @param Doctrine_EventListener $listener
     * @return void
     */
    public function set($key, Doctrine_EventListener $listener) {
        $this->listeners[$key] = $listener;
    }
    /**
     * this method should only be called internally by
     * doctrine, since it doesn't do any method existence checking
     *
     * @param method $method
     * @param array $args
     */
    public function __call($method, $args) {
        foreach($this->listeners as $listener) {
            $listener->$method($args[0]);
        }
    }
}

