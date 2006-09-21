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
     * @return void
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
     * @return void
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

    public function onEvict(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onEvict($record);
        }
    }

    public function onPreEvict(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onPreEvict($record);
        }
    }

    public function onSaveCascade(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onSaveCascade($record);
        }
    }

    public function onPreSaveCascade(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onPreSaveCascade($record);
        }
    }

    public function onDeleteCascade(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onDeleteCascade($record);
        }
    }

    public function onPreDeleteCascade(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onPreDeleteCascade($record);
        }
    }

    public function onClose(Doctrine_Connection $connection) { 
        foreach($this->listeners as $listener) {
            $listener->onClose($connection);
        }
    }

    public function onPreClose(Doctrine_Connection $connection) { 
        foreach($this->listeners as $listener) {
            $listener->onPreClose($connection);
        }
    }

    public function onOpen(Doctrine_Connection $connection) { 
        foreach($this->listeners as $listener) {
            $listener->onOpen($connection);
        }
    }

    public function onTransactionCommit(Doctrine_Connection $connection) { 
        foreach($this->listeners as $listener) {
            $listener->onTransactionCommit($connection);
        }
    }

    public function onPreTransactionCommit(Doctrine_Connection $connection) { 
        foreach($this->listeners as $listener) {
            $listener->onPreTransactionCommit($connection);
        }
    }

    public function onTransactionRollback(Doctrine_Connection $connection) { 
        foreach($this->listeners as $listener) {
            $listener->onTransactionRollback($connection);
        }
    }

    public function onPreTransactionRollback(Doctrine_Connection $connection) { 
        foreach($this->listeners as $listener) {
            $listener->onPreTransactionRollback($connection);
        }
    }

    public function onTransactionBegin(Doctrine_Connection $connection) {
        foreach($this->listeners as $listener) {
            $listener->onTransactionBegin($connection);
        }
    }

    public function onPreTransactionBegin(Doctrine_Connection $connection) { 
        foreach($this->listeners as $listener) {
            $listener->onPreTransactionBegin($connection);
        }
    }

    public function onCollectionDelete(Doctrine_Collection $collection) { 
        foreach($this->listeners as $listener) {
            $listener->onCollectionDelete($record);
        }
    }

    public function onPreCollectionDelete(Doctrine_Collection $collection) { 
        foreach($this->listeners as $listener) {
            $listener->onPreCollectionDelete($collection);
        }
    }
}

