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
class Doctrine_DB_EventListener_Chain extends Doctrine_Access implements Doctrine_DB_EventListener_Interface {
    private $listeners = array();

    public function add($listener, $name = null) {
        if( ! ($listener instanceof Doctrine_DB_EventListener_Interface) &&
            ! ($listener instanceof Doctrine_Overloadable))
            throw new Doctrine_DB_Exception("Couldn't add eventlistener. EventListeners should implement either Doctrine_DB_EventListener_Interface or Doctrine_Overloadable");

        if($name === null)
            $this->listeners[] = $listener;
        else
            $this->listeners[$name] = $listener;
    }

    public function get($name) {
        if( ! isset($this->listeners[$name]))
            throw new Doctrine_DB_Exception("Unknown listener $name");

        return $this->listeners[$name];
    }

    public function set($name, $listener) {
        if( ! ($listener instanceof Doctrine_DB_EventListener_Interface) &&
            ! ($listener instanceof Doctrine_Overloadable))
            throw new Doctrine_DB_Exception("Couldn't set eventlistener. EventListeners should implement either Doctrine_DB_EventListener_Interface or Doctrine_Overloadable");

        $this->listeners[$name] = $listener;
    }

    public function onQuery(Doctrine_DB2 $dbh, $statement, array $args, $queryId) {
        foreach($this->listeners as $listener) {
            $listener->onPreQuery($dbh, $args);
        }
    }
    public function onPreQuery(Doctrine_DB2 $dbh, $statement, array $args) {
        foreach($this->listeners as $listener) {
            $listener->onQuery($dbh, $args);
        }
    }

    public function onPreExec(Doctrine_DB2 $dbh, array $args) { 
        foreach($this->listeners as $listener) {
            $listener->onPreExec($dbh, $args);
        }
    }
    public function onExec(Doctrine_DB2 $dbh, array $args) { 
        foreach($this->listeners as $listener) {
            $listener->onExec($dbh, $args);
        }
    }

    public function onPrePrepare(Doctrine_DB2 $dbh, array $args) { 
        foreach($this->listeners as $listener) {
            $listener->onPrePrepare($dbh, $args);
        }
    }
    public function onPrepare(Doctrine_DB2 $dbh, array $args) { 
        foreach($this->listeners as $listener) {
            $listener->onPrepare($dbh, $args);
        }
    }

    public function onPreCommit(Doctrine_DB2 $dbh) { 
        foreach($this->listeners as $listener) {
            $listener->onPreCommit($dbh);
        }
    }
    public function onCommit(Doctrine_DB2 $dbh) { 
        foreach($this->listeners as $listener) {
            $listener->onCommit($dbh);
        }
    }

    public function onPreRollBack(Doctrine_DB2 $dbh) {
        foreach($this->listeners as $listener) {
            $listener->onPreRollBack($dbh);
        }
    }
    public function onRollBack(Doctrine_DB2 $dbh) { 
        foreach($this->listeners as $listener) {
            $listener->onRollBack($dbh);
        }
    }

    public function onPreBeginTransaction(Doctrine_DB2 $dbh) {
        foreach($this->listeners as $listener) {
            $listener->onPreBeginTransaction($dbh);
        }
    }
    public function onBeginTransaction(Doctrine_DB2 $dbh) { 
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
