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
 * Doctrine_Db_EventListener
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Db_EventListener_Chain extends Doctrine_Access implements Doctrine_Db_EventListener_Interface
{
    private $listeners = array();

    public function add($listener, $name = null)
    {
        if ( ! ($listener instanceof Doctrine_Db_EventListener_Interface)
            && ! ($listener instanceof Doctrine_Overloadable)
        ) {
            throw new Doctrine_Db_Exception("Couldn't add eventlistener. EventListeners should implement either Doctrine_Db_EventListener_Interface or Doctrine_Overloadable");
        }
        if ($name === null) {
            $this->listeners[] = $listener;
        } else {
            $this->listeners[$name] = $listener;
        }
    }

    public function get($name)
    {
        if ( ! isset($this->listeners[$name])) {
            throw new Doctrine_Db_Exception("Unknown listener $name");
        }
        return $this->listeners[$name];
    }

    public function set($name, $listener)
    {
        if ( ! ($listener instanceof Doctrine_Db_EventListener_Interface)
            && ! ($listener instanceof Doctrine_Overloadable)
        ) {
            throw new Doctrine_Db_Exception("Couldn't set eventlistener. EventListeners should implement either Doctrine_Db_EventListener_Interface or Doctrine_Overloadable");
        }
        $this->listeners[$name] = $listener;
    }

    public function onPreConnect(Doctrine_Db_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onPreConnect($event);
        }
    }
    public function onConnect(Doctrine_Db_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onConnect($event);
        }
    }
    public function onQuery(Doctrine_Db_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onQuery($event);
        }
    }
    public function onPreQuery(Doctrine_Db_Event $event)
    {
    	$return = null;

        foreach ($this->listeners as $listener) {
            $tmp = $listener->onPreQuery($event);

            if ($tmp !== null) {
                $return = $tmp;
            }
        }
        return $return;
    }

    public function onPreExec(Doctrine_Db_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreExec($event);
        }
    }
    public function onExec(Doctrine_Db_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onExec($event);
        }
    }

    public function onPrePrepare(Doctrine_Db_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPrePrepare($event);
        }
    }
    public function onPrepare(Doctrine_Db_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPrepare($event);
        }
    }

    public function onPreCommit(Doctrine_Db_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreCommit($event);
        }
    }
    public function onCommit(Doctrine_Db_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onCommit($event);
        }
    }
    public function onPreFetch(Doctrine_Db_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onPreFetch($event);
        }
    }
    public function onFetch(Doctrine_Db_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onFetch($event);
        }
    }

    public function onPreFetchAll(Doctrine_Db_Event $event)
    {
    	$return = null;

        foreach ($this->listeners as $listener) {
            $tmp = $listener->onPreFetchAll($event);

            if ($tmp !== null) {
                $return = $tmp;
            }
        }
        return $return;
    }
    public function onFetchAll(Doctrine_Db_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onFetchAll($event);
        }
    }

    public function onPreRollBack(Doctrine_Db_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreRollBack($event);
        }
    }
    public function onRollBack(Doctrine_Db_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onRollBack($event);
        }
    }

    public function onPreBeginTransaction(Doctrine_Db_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreBeginTransaction($event);
        }
    }
    public function onBeginTransaction(Doctrine_Db_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onBeginTransaction($event);
        }
    }

    public function onPreExecute(Doctrine_Db_Event $event)
    {
    	$return = null;
    	
        foreach ($this->listeners as $listener) {
            $tmp = $listener->onPreExecute($event);

            if ($tmp !== null) {
                $return = $tmp;
            }
        }
        return $return;
    }
    public function onExecute(Doctrine_Db_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onExecute($event);
        }
    }
}
