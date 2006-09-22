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
/**
 * Doctrine_DB_EventListener
 *
 * @author      Konsta Vesterinen
 * @license     LGPL
 * @package     Doctrine
 */
class Doctrine_DB_EventListener_Chain implements Doctrine_DB_EventListener_Interface {
    private $listeners = array();


    public function onPreQuery(Doctrine_DB $dbh, array $args) {
        foreach($this->listeners as $listener) {
            $listener->onPreQuery($dbh, $args);
        }
    }
    public function onQuery(Doctrine_DB $dbh, array $args) { 
        foreach($this->listeners as $listener) {
            $listener->onPreQuery($dbh, $args);
        }
    }

    public function onPrePrepare(Doctrine_DB $dbh, array $args) { 
        foreach($this->listeners as $listener) {
            $listener->onPrePrepare($dbh, $args);
        }
    }
    public function onPrepare(Doctrine_DB $dbh, array $args) { 
        foreach($this->listeners as $listener) {
            $listener->onPrepare($dbh, $args);
        }
    }

    public function onPreCommit(Doctrine_DB $dbh) { 
        foreach($this->listeners as $listener) {
            $listener->onPreCommit($dbh);
        }
    }
    public function onCommit(Doctrine_DB $dbh) { 
        foreach($this->listeners as $listener) {
            $listener->onCommit($dbh);
        }
    }

    public function onPreRollBack(Doctrine_DB $dbh) {
        foreach($this->listeners as $listener) {
            $listener->onPreRollBack($dbh);
        }
    }
    public function onRollBack(Doctrine_DB $dbh) { 
        foreach($this->listeners as $listener) {
            $listener->onRollBack($dbh);
        }
    }

    public function onPreBeginTransaction(Doctrine_DB $dbh) { 
        foreach($this->listeners as $listener) {
            $listener->onPreBeginTransaction($dbh);
        }
    }
    public function onBeginTransaction(Doctrine_DB $dbh) { 
        foreach($this->listeners as $listener) {
            $listener->onBeginTransaction($dbh);
        }
    }

    public function onPreExecute(Doctrine_DB_Statement $stmt, array $params) { 
        foreach($this->listeners as $listener) {
            $listener->onPreExecute($stmt, $params);
        }
    }
    public function onExecute(Doctrine_DB_Statement $stmt, array $params) {
        foreach($this->listeners as $listener) {
            $listener->onExecute($stmt, $params);
        }
    }
}
