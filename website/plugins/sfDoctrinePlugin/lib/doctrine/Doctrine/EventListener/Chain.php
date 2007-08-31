<?php
/*
 *  $Id: Chain.php 2186 2007-08-09 22:16:33Z jackbravo $
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
 * Doctrine_EventListener_Chain
 * this class represents a chain of different listeners,
 * useful for having multiple listeners listening the events at the same time
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 2186 $
 */
class Doctrine_EventListener_Chain extends Doctrine_Access implements Doctrine_EventListener_Interface
{
    /**
     * @var array $listeners        an array containing all listeners
     */
    private $listeners = array();
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
        if ( ! ($listener instanceof Doctrine_EventListener_Interface) &&
             ! ($listener instanceof Doctrine_Overloadable)) {
            
            throw new Doctrine_EventListener_Exception("Couldn't add eventlistener. EventListeners should implement either Doctrine_EventListener_Interface or Doctrine_Overloadable");
        }
        if ($name === null) {
            $this->listeners[] = $listener;
        } else {
            $this->listeners[$name] = $listener;
        }
    }
    /**
     * returns a Doctrine_EventListener on success
     * and null on failure
     *
     * @param mixed $key
     * @return mixed
     */
    public function get($key)
    {
        if ( ! isset($this->listeners[$key])) {
            return null;
        }
        return $this->listeners[$key];
    }
    /**
     * set
     *
     * @param mixed $key
     * @param Doctrine_EventListener $listener
     * @return void
     */
    public function set($key, Doctrine_EventListener $listener)
    {
        $this->listeners[$key] = $listener;
    }
    /**
     * onLoad
     * an event invoked when Doctrine_Record is being loaded from database
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onLoad(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onLoad($record);
        }
    }
    /**
     * onPreLoad
     * an event invoked when Doctrine_Record is being loaded
     * from database but not yet initialized
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onPreLoad(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreLoad($record);
        }
    }
    /**
     * onSleep
     * an event invoked when Doctrine_Record is serialized
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onSleep(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onSleep($record);
        }
    }
    /**
     * onWakeUp
     * an event invoked when Doctrine_Record is unserialized
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onWakeUp(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onWakeUp($record);
        }
    }
    /**
     * postClose
     * an event invoked after Doctrine_Connection is closed
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function postClose(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postClose($event);
        }
    }
    /**
     * preClose
     * an event invoked before Doctrine_Connection is closed
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function preClose(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preClose($event);
        }
    }
    /**
     * onOpen
     * an event invoked after Doctrine_Connection is opened
     *
     * @param Doctrine_Connection $connection
     * @return void
     */
    public function onOpen(Doctrine_Connection $connection)
    {
        foreach ($this->listeners as $listener) {
            $listener->onOpen($connection);
        }
    }
    /**
     * onTransactionCommit
     * an event invoked after a Doctrine_Connection transaction is committed
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function postTransactionCommit(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postTransactionCommit($event);
        }
    }
    /**
     * onPreTransactionCommit
     * an event invoked before a Doctrine_Connection transaction is committed
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function preTransactionCommit(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preTransactionCommit($event);
        }
    }
    /**
     * onTransactionRollback
     * an event invoked after a Doctrine_Connection transaction is being rolled back
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function postTransactionRollback(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postTransactionRollback($event);
        }
    }
    /**
     * onPreTransactionRollback
     * an event invoked before a Doctrine_Connection transaction is being rolled back
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function preTransactionRollback(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preTransactionRollback($event);
        }
    }
    /**
     * onTransactionBegin
     * an event invoked after a Doctrine_Connection transaction has been started
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function postTransactionBegin(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postTransactionBegin($event);
        }
    }
    /**
     * onTransactionBegin
     * an event invoked before a Doctrine_Connection transaction is being started
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function preTransactionBegin(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preTransactionBegin($event);
        }
    }
    /**
     * onCollectionDelete
     * an event invoked after a Doctrine_Collection is being deleted
     *
     * @param Doctrine_Collection $collection
     * @return void
     */
    public function onCollectionDelete(Doctrine_Collection $collection)
    {
        foreach ($this->listeners as $listener) {
            $listener->onCollectionDelete($collection);
        }
    }
    /**
     * onCollectionDelete
     * an event invoked after a Doctrine_Collection is being deleted
     *
     * @param Doctrine_Collection $collection
     * @return void
     */
    public function onPreCollectionDelete(Doctrine_Collection $collection)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreCollectionDelete($collection);
        }
    }
    public function postConnect(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postConnect($event);
        }
    }
    public function preConnect(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preConnect($event);
        }
    }
    public function preQuery(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->preQuery($event);
        }
    }
    public function postQuery(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postQuery($event);
        }
    }

    public function prePrepare(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->prePrepare($event);
        }
    }
    public function postPrepare(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postPrepare($event);
        }
    }

    public function preExec(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preExec($event);
        }
    }
    public function postExec(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postExec($event);
        }
    }

    public function preError(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->preError($event);
        }
    }
    public function postError(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postError($event);
        }
    }

    public function preFetch(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->preFetch($event);
        }
    }
    public function postFetch(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postFetch($event);
        }
    }

    public function preFetchAll(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->preFetchAll($event);
        }
    }

    public function postFetchAll(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postFetchAll($event);
        }
    }

    public function preStmtExecute(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->preStmtExecute($event);
        }
    }

    public function postStmtExecute(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->postStmtExecute($event);
        }
    }
}
