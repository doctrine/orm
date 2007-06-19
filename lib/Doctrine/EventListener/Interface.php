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
 *                            the empty methods allow child classes to only implement the methods they need to implement
 *
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
interface Doctrine_EventListener_Interface
{
    public function onLoad(Doctrine_Record $record);
    public function onPreLoad(Doctrine_Record $record);

    public function onSleep(Doctrine_Record $record);
    public function onWakeUp(Doctrine_Record $record);

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

    public function onClose(Doctrine_Event $event);
    public function onPreClose(Doctrine_Event $event);

    public function onCollectionDelete(Doctrine_Collection $collection);
    public function onPreCollectionDelete(Doctrine_Collection $collection);

    public function onOpen(Doctrine_Connection $connection);
    


    public function onConnect(Doctrine_Event $event);
    public function onPreConnect(Doctrine_Event $event);

    public function onTransactionCommit(Doctrine_Event $event);
    public function onPreTransactionCommit(Doctrine_Event $event);

    public function onTransactionRollback(Doctrine_Event $event);
    public function onPreTransactionRollback(Doctrine_Event $event);

    public function onTransactionBegin(Doctrine_Event $event);
    public function onPreTransactionBegin(Doctrine_Event $event);

    public function onPreQuery(Doctrine_Event $event);
    public function onQuery(Doctrine_Event $event);

    public function onPrePrepare(Doctrine_Event $event);
    public function onPrepare(Doctrine_Event $event);

    public function onPreExec(Doctrine_Event $event);
    public function onExec(Doctrine_Event $event);
    
    public function onPreFetch(Doctrine_Event $event);
    public function onFetch(Doctrine_Event $event);

    public function onPreFetchAll(Doctrine_Event $event);
    public function onFetchAll(Doctrine_Event $event);

    public function onPreExecute(Doctrine_Event $event);
    public function onExecute(Doctrine_Event $event);
}
