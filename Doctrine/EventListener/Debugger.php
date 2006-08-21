<?php
Doctrine::autoload("EventListener");

class Doctrine_DebugMessage {
    private $code;
    private $object;
    public function __construct($object, $code) {
        $this->object = $object;
        $this->code   = $code;
    }
    final public function getCode() {
        return $this->code;
    }
    final public function getObject() {
        return $this->object;
    }
}
class Doctrine_EventListener_Debugger extends Doctrine_EventListener {

    const EVENT_LOAD            = 1;
    const EVENT_PRELOAD         = 2;
    const EVENT_SLEEP           = 3;
    const EVENT_WAKEUP          = 4;
    const EVENT_UPDATE          = 5;
    const EVENT_PREUPDATE       = 6;
    const EVENT_CREATE          = 7;
    const EVENT_PRECREATE       = 8;

    const EVENT_SAVE            = 9;
    const EVENT_PRESAVE         = 10;
    const EVENT_INSERT          = 11;
    const EVENT_PREINSERT       = 12;
    const EVENT_DELETE          = 13;
    const EVENT_PREDELETE       = 14;
    const EVENT_EVICT           = 15;
    const EVENT_PREEVICT        = 16;
    const EVENT_CLOSE           = 17;
    const EVENT_PRECLOSE        = 18;

    const EVENT_OPEN            = 19;
    const EVENT_COMMIT          = 20;
    const EVENT_PRECOMMIT       = 21;
    const EVENT_ROLLBACK        = 22;
    const EVENT_PREROLLBACK     = 23;
    const EVENT_BEGIN           = 24;
    const EVENT_PREBEGIN        = 25;
    const EVENT_COLLDELETE      = 26;
    const EVENT_PRECOLLDELETE   = 27;
    private $debug;
    
    public function getMessages() {
        return $this->debug;                              	
    }


    public function onLoad(Doctrine_Record $record) {
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_LOAD);
    }
    public function onPreLoad(Doctrine_Record $record) {
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_PRELOAD);
    }

    public function onSleep(Doctrine_Record $record) { 
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_SLEEP);
    }

    public function onWakeUp(Doctrine_Record $record) { 
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_WAKEUP);
    }

    public function onUpdate(Doctrine_Record $record) { 
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_UPDATE);
    }
    public function onPreUpdate(Doctrine_Record $record) { 
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_PREUPDATE);
    }

    public function onCreate(Doctrine_Record $record) {
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_CREATE);
    }
    public function onPreCreate(Doctrine_Record $record) {
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_PRECREATE);
    }

    public function onSave(Doctrine_Record $record) {
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_SAVE);
    }
    public function onPreSave(Doctrine_Record $record) {
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_PRESAVE);
    }

    public function onInsert(Doctrine_Record $record) { 
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_INSERT);
    }
    public function onPreInsert(Doctrine_Record $record) { 
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_PREINSERT);
    }

    public function onDelete(Doctrine_Record $record) { 
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_DELETE);
    }
    public function onPreDelete(Doctrine_Record $record) { 
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_PREDELETE);
    }

    public function onEvict(Doctrine_Record $record) { 
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_EVICT);
    }
    public function onPreEvict(Doctrine_Record $record) { 
        $this->debug[] = new Doctrine_DebugMessage($record,self::EVENT_PREEVICT);
    }

    public function onClose(Doctrine_Connection $connection) { 
         $this->debug[] = new Doctrine_DebugMessage($connection,self::EVENT_CLOSE);
    }
    public function onPreClose(Doctrine_Connection $connection) { 
         $this->debug[] = new Doctrine_DebugMessage($connection,self::EVENT_PRECLOSE);
    }

    public function onOpen(Doctrine_Connection $connection) { 
         $this->debug[] = new Doctrine_DebugMessage($connection,self::EVENT_OPEN);
    }

    public function onTransactionCommit(Doctrine_Connection $connection) { 
         $this->debug[] = new Doctrine_DebugMessage($connection,self::EVENT_COMMIT);
    }
    public function onPreTransactionCommit(Doctrine_Connection $connection) { 
        $this->debug[] = new Doctrine_DebugMessage($connection,self::EVENT_PRECOMMIT);
    }

    public function onTransactionRollback(Doctrine_Connection $connection) {
        $this->debug[] = new Doctrine_DebugMessage($connection,self::EVENT_ROLLBACK);
    }
    public function onPreTransactionRollback(Doctrine_Connection $connection) {
        $this->debug[] = new Doctrine_DebugMessage($connection,self::EVENT_PREROLLBACK);
    }

    public function onTransactionBegin(Doctrine_Connection $connection) {
        $this->debug[] = new Doctrine_DebugMessage($connection,self::EVENT_BEGIN);
    }
    public function onPreTransactionBegin(Doctrine_Connection $connection) {
        $this->debug[] = new Doctrine_DebugMessage($connection,self::EVENT_PREBEGIN);
    }
    
    public function onCollectionDelete(Doctrine_Collection $collection) { 
        $this->debug[] = new Doctrine_DebugMessage($collection,self::EVENT_COLLDELETE);
    }
    public function onPreCollectionDelete(Doctrine_Collection $collection) {
        $this->debug[] = new Doctrine_DebugMessage($collection,self::EVENT_PRECOLLDELETE);
    }
}
?>
