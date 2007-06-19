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
 * @version     $Revision$
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
     * returns a Doctrine_EvenListener on success
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
     * onUpdate
     * an event invoked after Doctrine_Record is updated
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onUpdate(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onUpdate($record);
        }
    }
    /**
     * onPreUpdate
     * an event invoked before Doctrine_Record is updated
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onPreUpdate(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreUpdate($record);
        }
    }
    /**
     * onCreate
     * an event invoked when a new Doctrine_Record is created
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onCreate(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onCreate($record);
        }
    }
    /**
     * onPreCreate
     * an event invoked when a new Doctrine_Record
     * but not yet initialized
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onPreCreate(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreCreate($record);
        }
    }
    /**
     * onSave
     * an event invoked after a Doctrine_Record is saved (inserted / updated)
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onSave(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onSave($record);
        }
    }
    /**
     * onSave
     * an event invoked after a Doctrine_Record is saved (inserted / updated)
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onPreSave(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreSave($record);
        }
    }
    /**
     * onInsert
     * an event invoked after Doctrine_Record is inserted into database
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onInsert(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onInsert($record);
        }
    }
    /**
     * onPreInsert
     * an event invoked before Doctrine_Record is inserted into database
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onPreInsert(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreInsert($record);
        }
    }
    /**
     * onDelete
     * an event invoked after Doctrine_Record is deleted from database
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onDelete(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onDelete($record);
        }
    }
    /**
     * onPreDelete
     * an event invoked before Doctrine_Record is deleted from database
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onPreDelete(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreDelete($record);
        }
    }
    /**
     * onEvict
     * an event invoked after Doctrine_Record is evicted from record repository
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onEvict(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onEvict($record);
        }
    }
    /**
     * onPreEvict
     * an event invoked before Doctrine_Record is evicted from record repository
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onPreEvict(Doctrine_Record $record)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreEvict($record);
        }
    }
    /**
     * onClose
     * an event invoked after Doctrine_Connection is closed
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function onClose(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onClose($event);
        }
    }
    /**
     * onClose
     * an event invoked before Doctrine_Connection is closed
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function onPreClose(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreClose($event);
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
    public function onTransactionCommit(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onTransactionCommit($event);
        }
    }
    /**
     * onPreTransactionCommit
     * an event invoked before a Doctrine_Connection transaction is committed
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function onPreTransactionCommit(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreTransactionCommit($event);
        }
    }
    /**
     * onTransactionRollback
     * an event invoked after a Doctrine_Connection transaction is being rolled back
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function onTransactionRollback(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onTransactionRollback($event);
        }
    }
    /**
     * onPreTransactionRollback
     * an event invoked before a Doctrine_Connection transaction is being rolled back
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function onPreTransactionRollback(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreTransactionRollback($event);
        }
    }
    /**
     * onTransactionBegin
     * an event invoked after a Doctrine_Connection transaction has been started
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function onTransactionBegin(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onTransactionBegin($event);
        }
    }
    /**
     * onTransactionBegin
     * an event invoked before a Doctrine_Connection transaction is being started
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function onPreTransactionBegin(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onPreTransactionBegin($event);
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
    public function onConnect(Doctrine_Event $event)
    {
        foreach ($this->listeners as $listener) {
            $listener->onConnect($event);
        }
    }
    public function onPreConnect(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onPreConnect($event);
        }
    }
    public function onPreQuery(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onPreQuery($event);
        }
    }
    public function onQuery(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onQuery($event);
        }
    }

    public function onPrePrepare(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onPrePrepare($event);
        }
    }
    public function onPrepare(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onPrepare($event);
        }
    }

    public function onPreExec(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onPreExec($event);
        }
    }
    public function onExec(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onExec($event);
        }
    }
    
    public function onPreFetch(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onPreFetch($event);
        }
    }
    public function onFetch(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onFetch($event);
        }
    }

    public function onPreFetchAll(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onPreFetchAll($event);
        }
    }

    public function onFetchAll(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onFetchAll($event);
        }
    }

    public function onPreExecute(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onPreExecute($event);
        }
    }

    public function onExecute(Doctrine_Event $event)
    { 
        foreach ($this->listeners as $listener) {
            $listener->onExecute($event);
        }
    }
}
