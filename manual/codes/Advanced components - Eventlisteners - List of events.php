<?php
interface iDoctrine_EventListener {
    public function onLoad(Doctrine_Record $record);
    public function onPreLoad(Doctrine_Record $record);

    public function onUpdate(Doctrine_Record $record);
    public function onPreUpdate(Doctrine_Record $record);

    public function onCreate(Doctrine_Record $record);
    public function onPreCreate(Doctrine_Record $record);

    public function onSave(Doctrine_Record $record);
    public function onPreSave(Doctrine_Record $record);

    public function onInsert(Doctrine_Record $record);
    public function onPreInsert(Doctrine_Record $record);

    public function onDelete(Doctrine_Record $record);
    public function onPreDelete(Doctrine_Record $record);

    public function onEvict(Doctrine_Record $record);
    public function onPreEvict(Doctrine_Record $record);
    
    public function onSleep(Doctrine_Record $record);
    
    public function onWakeUp(Doctrine_Record $record);
    
    public function onClose(Doctrine_Session $session);
    public function onPreClose(Doctrine_Session $session);
    
    public function onOpen(Doctrine_Session $session);

    public function onTransactionCommit(Doctrine_Session $session);
    public function onPreTransactionCommit(Doctrine_Session $session);

    public function onTransactionRollback(Doctrine_Session $session);
    public function onPreTransactionRollback(Doctrine_Session $session);

    public function onTransactionBegin(Doctrine_Session $session);
    public function onPreTransactionBegin(Doctrine_Session $session);
    
    public function onCollectionDelete(Doctrine_Collection $collection);
    public function onPreCollectionDelete(Doctrine_Collection $collection);
}
?>
