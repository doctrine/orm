<?php
/*
 *  $Id: EventListener.php 1976 2007-07-11 22:03:47Z zYne $
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
 * @version     $Revision: 1976 $
 */
class Doctrine_EventListener implements Doctrine_EventListener_Interface
{
    public function preClose(Doctrine_Event $event)
    { }
    public function postClose(Doctrine_Event $event)
    { }

    public function onCollectionDelete(Doctrine_Collection $collection)
    { }
    public function onPreCollectionDelete(Doctrine_Collection $collection)
    { }

    public function onOpen(Doctrine_Connection $connection)
    { }

    public function preTransactionCommit(Doctrine_Event $event)
    { }
    public function postTransactionCommit(Doctrine_Event $event)
    { }

    public function preTransactionRollback(Doctrine_Event $event)
    { }
    public function postTransactionRollback(Doctrine_Event $event)
    { }

    public function preTransactionBegin(Doctrine_Event $event)
    { }
    public function postTransactionBegin(Doctrine_Event $event)
    { }


    public function preSavepointCommit(Doctrine_Event $event)
    { }
    public function postSavepointCommit(Doctrine_Event $event)
    { }

    public function preSavepointRollback(Doctrine_Event $event)
    { }
    public function postSavepointRollback(Doctrine_Event $event)
    { }

    public function preSavepointCreate(Doctrine_Event $event)
    { }
    public function postSavepointCreate(Doctrine_Event $event)
    { }

    public function postConnect(Doctrine_Event $event)
    { }
    public function preConnect(Doctrine_Event $event)
    { }

    public function preQuery(Doctrine_Event $event)
    { }
    public function postQuery(Doctrine_Event $event)
    { }

    public function prePrepare(Doctrine_Event $event)
    { }
    public function postPrepare(Doctrine_Event $event)
    { }

    public function preExec(Doctrine_Event $event)
    { }
    public function postExec(Doctrine_Event $event)
    { }

    public function preError(Doctrine_Event $event)
    { }
    public function postError(Doctrine_Event $event)
    { }

    public function preFetch(Doctrine_Event $event)
    { }
    public function postFetch(Doctrine_Event $event)
    { }

    public function preFetchAll(Doctrine_Event $event)
    { }
    public function postFetchAll(Doctrine_Event $event)
    { }

    public function preStmtExecute(Doctrine_Event $event)
    { }
    public function postStmtExecute(Doctrine_Event $event)
    { }
}
