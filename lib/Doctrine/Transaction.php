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
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine::DBAL::Transactions;

/**
 * Handles transaction savepoint and isolation abstraction
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Transaction extends Doctrine_Connection_Module
{
    /**
     * A transaction is in sleep state when it is not active.
     */
    const STATE_SLEEP       = 0;

    /**
     * A transaction is in active state when it is active.
     */
    const STATE_ACTIVE      = 1;

    /**
     * A transaction is in busy state when it is active and has a nesting level > 1.
     */
    const STATE_BUSY        = 2;

    /**
     * @var integer $_nestingLevel      The current nesting level of this transaction.
     *                                  A nesting level of 0 means there is currently no active
     *                                  transaction.
     */
    protected $_nestingLevel = 0;

    /**
     * @var array $savepoints               an array containing all savepoints
     */
    protected $savePoints = array();

    /**
     * Returns the state of this transaction module.
     *
     * @see Doctrine_Connection_Transaction::STATE_* constants
     * @return integer          the connection state
     */
    public function getState()
    {
        switch ($this->_nestingLevel) {
            case 0:
                return self::STATE_SLEEP;
                break;
            case 1:
                return self::STATE_ACTIVE;
                break;
            default:
                return self::STATE_BUSY;
        }
    }

    /**
     * Gets the current transaction nesting level.
     *
     * @return integer
     * @todo Name suggestion: getNestingLevel(). $transaction->getTransactionLevel() looks odd.
     */
    public function getTransactionLevel()
    {
        return $this->_nestingLevel;
    }

    /**
     * Start a transaction or set a savepoint.
     *
     * if trying to set a savepoint and there is no active transaction
     * a new transaction is being started
     *
     * This method should only be used by userland-code to initiate transactions.
     * To initiate a transaction from inside Doctrine use {@link beginInternalTransaction()}.
     *
     * Listeners: onPreTransactionBegin, onTransactionBegin
     *
     * @param string $savepoint                 name of a savepoint to set
     * @throws Doctrine_Transaction_Exception   if the transaction fails at database level     
     * @return integer                          current transaction nesting level
     */
    public function begin($savepoint = null)
    {
        $this->conn->connect();
        //$listener = $this->conn->getAttribute(Doctrine::ATTR_LISTENER);

        if ( ! is_null($savepoint)) {
            $this->savePoints[] = $savepoint;
            //$event = new Doctrine_Event($this, Doctrine_Event::SAVEPOINT_CREATE);
            //$listener->preSavepointCreate($event);
            //if ( ! $event->skipOperation) {
                $this->createSavePoint($savepoint);
            //}
            //$listener->postSavepointCreate($event);
        } else {
            if ($this->_nestingLevel == 0) {
                //$event = new Doctrine_Event($this, Doctrine_Event::TX_BEGIN);
                //$listener->preTransactionBegin($event);

                //if ( ! $event->skipOperation) {
                    try {
                        $this->_doBeginTransaction();
                    } catch (Exception $e) {
                        throw new Doctrine_Transaction_Exception($e->getMessage());
                    }
                //}
                //$listener->postTransactionBegin($event);
            }
        }

        $level = ++$this->_nestingLevel;

        return $level;
    }

    /**
     * Commits the database changes done during a transaction that is in
     * progress or release a savepoint. This function may only be called when
     * auto-committing is disabled, otherwise it will fail.
     *
     * Listeners: preTransactionCommit, postTransactionCommit
     *
     * @param string $savepoint                 name of a savepoint to release
     * @throws Doctrine_Transaction_Exception   if the transaction fails at database level
     * @throws Doctrine_Validator_Exception     if the transaction fails due to record validations
     * @return boolean                          false if commit couldn't be performed, true otherwise
     */
    public function commit($savepoint = null)
    {
        if ($this->_nestingLevel == 0) {
            throw new Doctrine_Transaction_Exception("Commit failed. There is no active transaction.");
        }
        
        $this->conn->connect();

        //$listener = $this->conn->getAttribute(Doctrine::ATTR_LISTENER);

        if ( ! is_null($savepoint)) {
            $this->_nestingLevel -= $this->removeSavePoints($savepoint);
            //$event = new Doctrine_Event($this, Doctrine_Event::SAVEPOINT_COMMIT);
            //$listener->preSavepointCommit($event);
            //if ( ! $event->skipOperation) {
                $this->releaseSavePoint($savepoint);
            //}
            //$listener->postSavepointCommit($event);
        } else {   
            if ($this->_nestingLevel == 1) {
                //$event = new Doctrine_Event($this, Doctrine_Event::TX_COMMIT);
                //$listener->preTransactionCommit($event);
                //if ( ! $event->skipOperation) {
                    $this->_doCommit();
                //}
                //$listener->postTransactionCommit($event);
            }
            
            if ($this->_nestingLevel > 0) {
                $this->_nestingLevel--;
            }
        }

        return true;
    }

    /**
     * Cancel any database changes done during a transaction or since a specific
     * savepoint that is in progress. This function may only be called when
     * auto-committing is disabled, otherwise it will fail. Therefore, a new
     * transaction is implicitly started after canceling the pending changes.
     *
     * this method can be listened with onPreTransactionRollback and onTransactionRollback
     * eventlistener methods
     *
     * @param string $savepoint                 name of a savepoint to rollback to   
     * @throws Doctrine_Transaction_Exception   if the rollback operation fails at database level
     * @return boolean                          false if rollback couldn't be performed, true otherwise
     */
    public function rollback($savepoint = null)
    {
        if ($this->_nestingLevel == 0) {
            throw new Doctrine_Transaction_Exception("Rollback failed. There is no active transaction.");
        }
        
        $this->conn->connect();

        if ($this->_nestingLevel > 1) {
            $this->_nestingLevel--;
            return false;
        }

        $listener = $this->conn->getAttribute(Doctrine::ATTR_LISTENER);

        if ( ! is_null($savepoint)) {
            $this->_nestingLevel -= $this->removeSavePoints($savepoint);
            //$event = new Doctrine_Event($this, Doctrine_Event::SAVEPOINT_ROLLBACK);
            //$listener->preSavepointRollback($event);
            //if ( ! $event->skipOperation) {
                $this->rollbackSavePoint($savepoint);
            //}
            //$listener->postSavepointRollback($event);
        } else {
            //$event = new Doctrine_Event($this, Doctrine_Event::TX_ROLLBACK);
            //$listener->preTransactionRollback($event);
            //if ( ! $event->skipOperation) {
                $this->_nestingLevel = 0;
                try {
                    $this->_doRollback();
                } catch (Exception $e) {
                    throw new Doctrine_Transaction_Exception($e->getMessage());
                }
            //}
            //$listener->postTransactionRollback($event);
        }

        return true;
    }

    /**
     * Creates a new savepoint.
     *
     * @param string $savepoint     name of a savepoint to create
     * @return void
     */
    protected function createSavePoint($savepoint)
    {
        throw new Doctrine_Transaction_Exception('Savepoints not supported by this driver.');
    }

    /**
     * Releases given savepoint.
     *
     * @param string $savepoint     name of a savepoint to release
     * @return void
     */
    protected function releaseSavePoint($savepoint)
    {
        throw new Doctrine_Transaction_Exception('Savepoints not supported by this driver.');
    }

    /**
     * Performs a rollback to a specified savepoint.
     *
     * @param string $savepoint     name of a savepoint to rollback to
     * @return void
     */
    protected function rollbackSavePoint($savepoint)
    {
        throw new Doctrine_Transaction_Exception('Savepoints not supported by this driver.');
    }
    
    /**
     * Performs the rollback.
     */
    protected function _doRollback()
    {
        $this->conn->getDbh()->rollback();
    }
    
    /**
     * Performs the commit.
     */
    protected function _doCommit()
    {
        $this->conn->getDbh()->commit();
    }
    
    /**
     * Begins a database transaction.
     */
    protected function _doBeginTransaction()
    {
        $this->conn->getDbh()->beginTransaction();
    }

    /**
     * removes a savepoint from the internal savePoints array of this transaction object
     * and all its children savepoints
     *
     * @param sring $savepoint      name of the savepoint to remove
     * @return integer              removed savepoints
     */
    private function removeSavePoints($savepoint)
    {
        $this->savePoints = array_values($this->savePoints);

        $found = false;
        $i = 0;

        foreach ($this->savePoints as $key => $sp) {
            if ( ! $found) {
                if ($sp === $savepoint) {
                    $found = true;
                }
            }
            if ($found) {
                $i++;
                unset($this->savePoints[$key]);
            }
        }

        return $i;
    }

    /**
     * Set the transacton isolation level.
     * (implemented by the connection drivers)
     *
     * example:
     *
     * <code>
     * $tx->setIsolation('READ UNCOMMITTED');
     * </code>
     *
     * @param   string  standard isolation level
     *                  READ UNCOMMITTED (allows dirty reads)
     *                  READ COMMITTED (prevents dirty reads)
     *                  REPEATABLE READ (prevents nonrepeatable reads)
     *                  SERIALIZABLE (prevents phantom reads)
     *
     * @throws Doctrine_Transaction_Exception           if the feature is not supported by the driver
     * @throws PDOException                             if something fails at the PDO level
     * @return void
     */
    public function setIsolation($isolation)
    {
        throw new Doctrine_Transaction_Exception('Transaction isolation levels not supported by this driver.');
    }

    /**
     * Fetches the current session transaction isolation level.
     *
     * note: some drivers may support setting the transaction isolation level
     * but not fetching it
     *
     * @throws Doctrine_Transaction_Exception           if the feature is not supported by the driver
     * @throws PDOException                             if something fails at the PDO level
     * @return string                                   returns the current session transaction isolation level
     */
    public function getIsolation()
    {
        throw new Doctrine_Transaction_Exception('Fetching transaction isolation level not supported by this driver.');
    }    
}
