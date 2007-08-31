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
 * <http://www.phpdoctrine.com>.
 */
Doctrine::autoload('Doctrine_Access');

/**
 * Doctrine_Record_Listener_Chain
 * this class represents a chain of different listeners,
 * useful for having multiple listeners listening the events at the same time
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Record_Listener_Chain extends Doctrine_Access implements Doctrine_Record_Listener_Interface
{
    /**
     * @var array $listeners        an array containing all listeners
     */
    protected $_listeners = array();
    /**
     * add
     * adds a listener to the chain of listeners
     *
     * @param object $listener
     * @param string $name
     * @return void
     */
    public function add($listener, $name = null)
    {
        if ( ! ($listener instanceof Doctrine_Record_Listener_Interface) &&
             ! ($listener instanceof Doctrine_Overloadable)) {
            
            throw new Doctrine_EventListener_Exception("Couldn't add eventlistener. Record listeners should implement either Doctrine_EventListener_Interface or Doctrine_Overloadable");
        }
        if ($name === null) {
            $this->_listeners[] = $listener;
        } else {
            $this->_listeners[$name] = $listener;
        }
    }
    /**
     * returns a Doctrine_Record_Listener on success
     * and null on failure
     *
     * @param mixed $key
     * @return mixed
     */
    public function get($key)
    {
        if ( ! isset($this->_listeners[$key])) {
            return null;
        }
        return $this->_listeners[$key];
    }
    /**
     * set
     *
     * @param mixed $key
     * @param Doctrine_Record_Listener $listener    listener to be added
     * @return Doctrine_Record_Listener_Chain       this object
     */
    public function set($key, Doctrine_EventListener $listener)
    {
        $this->_listeners[$key] = $listener;
    }

    public function preSerialize(Doctrine_Event $event)
    { 
        foreach ($this->_listeners as $listener) {
            $listener->preSerialize($event);
        }
    }

    public function postSerialize(Doctrine_Event $event)
    { 
        foreach ($this->_listeners as $listener) {
            $listener->preSerialize($event);
        }
    }

    public function preUnserialize(Doctrine_Event $event)
    { 
        foreach ($this->_listeners as $listener) {
            $listener->preUnserialize($event);
        }
    }

    public function postUnserialize(Doctrine_Event $event)
    { 
        foreach ($this->_listeners as $listener) {
            $listener->postUnserialize($event);
        }
    }

    public function preSave(Doctrine_Event $event)
    { 
        foreach ($this->_listeners as $listener) {
            $listener->preSave($event);
        }
    }

    public function postSave(Doctrine_Event $event)
    { 
        foreach ($this->_listeners as $listener) {
            $listener->postSave($event);
        }
    }

    public function preDelete(Doctrine_Event $event)
    { 
        foreach ($this->_listeners as $listener) {
            $listener->preDelete($event);
        }
    }

    public function postDelete(Doctrine_Event $event)
    {
        foreach ($this->_listeners as $listener) {
            $listener->postDelete($event);
        }
    }

    public function preUpdate(Doctrine_Event $event)
    { 
        foreach ($this->_listeners as $listener) {
            $listener->preUpdate($event);
        }
    }

    public function postUpdate(Doctrine_Event $event)
    { 
        foreach ($this->_listeners as $listener) {
            $listener->postUpdate($event);
        }
    }

    public function preInsert(Doctrine_Event $event)
    { 
        foreach ($this->_listeners as $listener) {
            $listener->preInsert($event);
        }
    }

    public function postInsert(Doctrine_Event $event)
    { 
        foreach ($this->_listeners as $listener) {
            $listener->postInsert($event);
        }
    }
}
