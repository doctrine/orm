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
 */
class Doctrine_EventListener implements Doctrine_EventListener_Interface {

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

    public function onGetProperty(Doctrine_Record $record, $property, $value) {
        return $value;
    }
    public function onSetProperty(Doctrine_Record $record, $property, $value) {
        return $value;
    }

    public function onInsert(Doctrine_Record $record) { }
    public function onPreInsert(Doctrine_Record $record) { }

    public function onDelete(Doctrine_Record $record) { }
    public function onPreDelete(Doctrine_Record $record) { }

    public function onEvict(Doctrine_Record $record) { }
    public function onPreEvict(Doctrine_Record $record) { }

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
