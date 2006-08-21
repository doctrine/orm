<?php
Doctrine::autoload('Doctrine_EventListener_Interface');
/**
 * Doctrine_EventListener     all event listeners extend this base class
 *                      the empty methods allow child classes to only implement the methods they need to implement
 *
 *
 * @author      Konsta Vesterinen
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 * @version     1.0 alpha
 *
 */
abstract class Doctrine_EventListener implements Doctrine_EventListener_Interface {

    public function onLoad(Doctrine_Record $record) { }
    public function onPreLoad(Doctrine_Record $record) { }

    public function onSleep(Doctrine_Record $record) { }

    public function onWakeUp(Doctrine_Record $record) { }

    public function onUpdate(Doctrine_Record $record) { }
    public function onPreUpdate(Doctrine_Record $record) { }

    public function onCreate(Doctrine_Record $record) { }
    public function onPreCreate(Doctrine_Record $record) { }

    public function onSave(Doctrine_Record $record) { }
    public function onPreSave(Doctrine_Record $record) { }

    public function onInsert(Doctrine_Record $record) { }
    public function onPreInsert(Doctrine_Record $record) { }

    public function onDelete(Doctrine_Record $record) { }
    public function onPreDelete(Doctrine_Record $record) { }

    public function onEvict(Doctrine_Record $record) { }
    public function onPreEvict(Doctrine_Record $record) { }

    public function onSaveCascade(Doctrine_Record $record) { }
    public function onPreSaveCascade(Doctrine_Record $record) { }

    public function onDeleteCascade(Doctrine_Record $record) { }
    public function onPreDeleteCascade(Doctrine_Record $record) { }

    public function onClose(Doctrine_Connection $connection) { }
    public function onPreClose(Doctrine_Connection $connection) { }

    public function onOpen(Doctrine_Connection $connection) { }

    public function onTransactionCommit(Doctrine_Connection $connection) { }
    public function onPreTransactionCommit(Doctrine_Connection $connection) { }

    public function onTransactionRollback(Doctrine_Connection $connection) { }
    public function onPreTransactionRollback(Doctrine_Connection $connection) { }

    public function onTransactionBegin(Doctrine_Connection $connection) { }
    public function onPreTransactionBegin(Doctrine_Connection $connection) { }
    
    public function onCollectionDelete(Doctrine_Collection $collection) { }
    public function onPreCollectionDelete(Doctrine_Collection $collection) { }
}


?>
