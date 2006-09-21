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

    public function onLoad(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onLoad($record);
        }
    }
    public function onPreLoad(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onPreLoad($record);
        }
    }

    public function onSleep(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onSleep($record);
        }
    }

    public function onWakeUp(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onWakeUp($record);
        }
    }

    public function onUpdate(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onUpdate($record);
        }
    }
    public function onPreUpdate(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onPreUpdate($record);
        }
    }

    public function onCreate(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onCreate($record);
        }
    }
    public function onPreCreate(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onPreCreate($record);
        }
    }

    public function onSave(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onSave($record);
        }
    }
    public function onPreSave(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onPreSave($record);
        }
    }

    public function onGetProperty(Doctrine_Record $record, $property, $value) {
        foreach($this->listeners as $listener) {
            $listener->onGetProperty($record, $property, $value);
        }
    }
    public function onSetProperty(Doctrine_Record $record, $property, $value) {
        foreach($this->listeners as $listener) {
            $listener->onSetProperty($record, $property, $value);
        }
    }

    public function onInsert(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onInsert($record);
        }
    }
    public function onPreInsert(Doctrine_Record $record) {
        foreach($this->listeners as $listener) {
            $listener->onPreInsert($record);
        }
    }

    public function onDelete(Doctrine_Record $record) { 
        foreach($this->listeners as $listener) {
            $listener->onDelete($record);
        }
    }
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

