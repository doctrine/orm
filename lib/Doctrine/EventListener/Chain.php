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

Doctrine::autoload('Doctrine_EventListener_Interface');
/**
 * Doctrine_EventListener_Chain
 * this class represents a chain of different listeners, 
 * useful for having multiple listeners listening the events at the same time
 *
 * @author      Konsta Vesterinen
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_EventListener_Chain extends Doctrine_Access implements Doctrine_EventListener_Interface {
    /**
     * @var array $listeners        an array containing all listeners
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
     * onLoad
     * an event invoked when Doctrine_Record is being loaded from database
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onLoad(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
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
    public function onPreLoad(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
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
    public function onSleep(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
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
    public function onWakeUp(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
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
    public function onUpdate(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
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
    public function onPreUpdate(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
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
    public function onCreate(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
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
    public function onPreCreate(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
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
    public function onSave(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
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
    public function onPreSave(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onPreSave($record);
        }
    }
    /**
     * onGetProperty
     * an event invoked when a property of Doctrine_Record is retrieved
     *
     * @param Doctrine_Record $record
     * @param string $property
     * @param mixed $value
     * @return mixed
     */
    public function onGetProperty(Doctrine_Record $record, $property, $value) {
        foreach($this->listeners as $listener) {
            $value = $listener->onGetProperty($record, $property, $value);
        }
        return $value;
    }
    /**
     * onSetProperty
     * an event invoked when a property of Doctrine_Record is being set
     *
     * @param Doctrine_Record $record
     * @param string $property
     * @param mixed $value
     * @return mixed
     */
    public function onSetProperty(Doctrine_Record $record, $property, $value) {
        foreach($this->listeners as $listener) {
            $value = $listener->onSetProperty($record, $property, $value);
        }
        return $value;
    }
    /**
     * onInsert
     * an event invoked after Doctrine_Record is inserted into database
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function onInsert(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
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
    public function onPreInsert(Doctrine_Record $record) {
        foreach($this->listeners as $listener) {
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
    public function onDelete(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
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
    public function onPreDelete(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
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
    public function onEvict(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
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
    public function onPreEvict(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onPreEvict($record);
        }
    }
    /**
     * onClose
     * an event invoked after Doctrine_Connection is closed
     *
     * @param Doctrine_Connection $connection
     * @return void
     */
    public function onClose(Doctrine_Connection $connection) {
        foreach($this->listeners as $listener) {
            $listener->onClose($connection);
        }
    }
    /**
     * onClose
     * an event invoked before Doctrine_Connection is closed
     *
     * @param Doctrine_Connection $connection
     * @return void
     */
    public function onPreClose(Doctrine_Connection $connection) { 
        foreach($this->listeners as $listener) {
            $listener->onPreClose($connection);
        }
    }
    /**
     * onOpen
     * an event invoked after Doctrine_Connection is opened
     *
     * @param Doctrine_Connection $connection
     * @return void
     */
    public function onOpen(Doctrine_Connection $connection) { 
        foreach($this->listeners as $listener) {
            $listener->onOpen($connection);
        }
    }
    /**
     * onTransactionCommit
     * an event invoked after a Doctrine_Connection transaction is committed
     *
     * @param Doctrine_Connection $connection
     * @return void
     */
    public function onTransactionCommit(Doctrine_Connection $connection) { 
        foreach($this->listeners as $listener) {
            $listener->onTransactionCommit($connection);
        }
    }
    /**
     * onPreTransactionCommit
     * an event invoked before a Doctrine_Connection transaction is committed
     *
     * @param Doctrine_Connection $connection
     * @return void
     */
    public function onPreTransactionCommit(Doctrine_Connection $connection) { 
        foreach($this->listeners as $listener) {
            $listener->onPreTransactionCommit($connection);
        }
    }
    /**
     * onTransactionRollback
     * an event invoked after a Doctrine_Connection transaction is being rolled back
     *
     * @param Doctrine_Connection $connection
     * @return void
     */
    public function onTransactionRollback(Doctrine_Connection $connection) { 
        foreach($this->listeners as $listener) {
            $listener->onTransactionRollback($connection);
        }
    }
    /**
     * onPreTransactionRollback
     * an event invoked before a Doctrine_Connection transaction is being rolled back
     *
     * @param Doctrine_Connection $connection
     * @return void
     */
    public function onPreTransactionRollback(Doctrine_Connection $connection) { 
        foreach($this->listeners as $listener) {
            $listener->onPreTransactionRollback($connection);
        }
    }
    /**
     * onTransactionBegin
     * an event invoked after a Doctrine_Connection transaction has been started
     *
     * @param Doctrine_Connection $connection
     * @return void
     */
    public function onTransactionBegin(Doctrine_Connection $connection) {
        foreach($this->listeners as $listener) {
            $listener->onTransactionBegin($connection);
        }
    }
    /**
     * onTransactionBegin
     * an event invoked before a Doctrine_Connection transaction is being started
     *
     * @param Doctrine_Connection $connection
     * @return void
     */
    public function onPreTransactionBegin(Doctrine_Connection $connection) { 
        foreach($this->listeners as $listener) {
            $listener->onPreTransactionBegin($connection);
        }
    }
    /**
     * onCollectionDelete
     * an event invoked after a Doctrine_Collection is being deleted
     *
     * @param Doctrine_Collection $collection
     * @return void
     */
    public function onCollectionDelete(Doctrine_Collection $collection) { 
        foreach($this->listeners as $listener) {
            $listener->onCollectionDelete($record);
        }
    }
    /**
     * onCollectionDelete
     * an event invoked after a Doctrine_Collection is being deleted
     *
     * @param Doctrine_Collection $collection
     * @return void
     */
    public function onPreCollectionDelete(Doctrine_Collection $collection) { 
        foreach($this->listeners as $listener) {
            $listener->onPreCollectionDelete($collection);
        }
    }
}

