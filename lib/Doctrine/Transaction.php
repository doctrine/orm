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
Doctrine::autoload('Doctrine_Connection_Module');
/**
 * Doctrine_Transaction
 * Handles transaction savepoint and isolation abstraction
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @subpackage  Transaction
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Transaction extends Doctrine_Connection_Module
{
    /**
     * Doctrine_Transaction is in sleep state when it has no active transactions
     */
    const STATE_SLEEP       = 0;

    /**
     * Doctrine_Transaction is in active state when it has one active transaction
     */
    const STATE_ACTIVE      = 1;

    /**
     * Doctrine_Transaction is in busy state when it has multiple active transactions
     */
    const STATE_BUSY        = 2;

    /**
     * @var integer $_nestingLevel      The current nesting level of this transaction.
     *                                  A nesting level of 0 means there is currently no active
     *                                  transaction.
     */
    protected $_nestingLevel = 0;
    
    /**
     * @var integer $_internalNestingLevel  The current internal nesting level of this transaction.
     *                                      "Internal" means transactions started by Doctrine itself.
     *                                      Therefore the internal nesting level is always
     *                                      lower or equal to the overall nesting level.
     *                                      A level of 0 means there is currently no active
     *                                      transaction that was initiated by Doctrine itself.
     */
    protected $_internalNestingLevel = 0;

    /**
     * @var array $invalid                  an array containing all invalid records within this transaction
     * @todo What about a more verbose name? $invalidRecords?
     */
    protected $invalid          = array();

    /**
     * @var array $savepoints               an array containing all savepoints
     */
    protected $savePoints       = array();

    /**
     * @var array $_collections             an array of Doctrine_Collection objects that were affected during the Transaction
     */
    protected $_collections     = array();

    /**
     * addCollection
     * adds a collection in the internal array of collections
     *
     * at the end of each commit this array is looped over and
     * of every collection Doctrine then takes a snapshot in order
     * to keep the collections up to date with the database
     *
     * @param Doctrine_Collection $coll     a collection to be added
     * @return Doctrine_Transaction         this object
     */
    public function addCollection(Doctrine_Collection $coll)
    {
        $this->_collections[] = $coll;

        return $this;
    }

    /**
     * getState
     * returns the state of this transaction module.
     *
     * @see Doctrine_Connection_Transaction::STATE_* constants
     * @return integer          the connection state
     */
    public function getState()
    {
        switch ($this->_nestingLevel) {
            case 0:
                return Doctrine_Transaction::STATE_SLEEP;
                break;
            case 1:
                return Doctrine_Transaction::STATE_ACTIVE;
                break;
            default:
                return Doctrine_Transaction::STATE_BUSY;
        }
    }

    /**
     * addInvalid
     * adds record into invalid records list
     *
     * @param Doctrine_Record $record
     * @return boolean        false if record already existed in invalid records list,
     *                        otherwise true
     */
    public function addInvalid(Doctrine_Record $record)
    {
        if (in_array($record, $this->invalid, true)) {
            return false;
        }
        $this->invalid[] = $record;
        return true;
    }


   /**
    * Return the invalid records
    *
    * @return array An array of invalid records
    */ 
    public function getInvalid()
    {
        return $this->invalid;
    }

    /**
     * getTransactionLevel
     * get the current transaction nesting level
     *
     * @return integer
     */
    public function getTransactionLevel()
    {
        return $this->_nestingLevel;
    }
    
    /**
     * getInternalTransactionLevel
     * get the current internal transaction nesting level
     *
     * @return integer
     */
    public function getInternalTransactionLevel()
    {
        return $this->_internalNestingLevel;
    }

    /**
     * beginTransaction
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
    public function beginTransaction($savepoint = null)
    {
        $this->conn->connect();
        
        $listener = $this->conn->getAttribute(Doctrine::ATTR_LISTENER);

        if ( ! is_null($savepoint)) {
            $this->savePoints[] = $savepoint;

            $event = new Doctrine_Event($this, Doctrine_Event::SAVEPOINT_CREATE);

            $listener->preSavepointCreate($event);

            if ( ! $event->skipOperation) {
                $this->createSavePoint($savepoint);
            }

            $listener->postSavepointCreate($event);
        } else {
            if ($this->_nestingLevel == 0) {
                $event = new Doctrine_Event($this, Doctrine_Event::TX_BEGIN);

                $listener->preTransactionBegin($event);

                if ( ! $event->skipOperation) {
                    try {
                        $this->conn->getDbh()->beginTransaction();
                    } catch (Exception $e) {
                        throw new Doctrine_Transaction_Exception($e->getMessage());
                    }
                }
                $listener->postTransactionBegin($event);
            }
        }

        $level = ++$this->_nestingLevel;

        return $level;
    }

    /**
     * commit
     * Commit the database changes done during a transaction that is in
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

        $listener = $this->conn->getAttribute(Doctrine::ATTR_LISTENER);

        if ( ! is_null($savepoint)) {
            $this->_nestingLevel -= $this->removeSavePoints($savepoint);

            $event = new Doctrine_Event($this, Doctrine_Event::SAVEPOINT_COMMIT);

            $listener->preSavepointCommit($event);

            if ( ! $event->skipOperation) {
                $this->releaseSavePoint($savepoint);
            }

            $listener->postSavepointCommit($event);
        } else {
                 
            if ($this->_nestingLevel == 1 || $this->_internalNestingLevel == 1) {
                if ( ! empty($this->invalid)) {
                    if ($this->_internalNestingLevel == 1) {
                        // transaction was started by doctrine, so we are responsible
                        // for a rollback
                        $this->rollback();
                        $tmp = $this->invalid;
                        $this->invalid = array();
                        throw new Doctrine_Validator_Exception($tmp);
                    }
                }
                if ($this->_nestingLevel == 1) {
                    // take snapshots of all collections used within this transaction
                    foreach ($this->_collections as $coll) {
                        $coll->takeSnapshot();
                    }
                    $this->_collections = array();

                    $event = new Doctrine_Event($this, Doctrine_Event::TX_COMMIT);

                    $listener->preTransactionCommit($event);
                    if ( ! $event->skipOperation) {
                        $this->conn->getDbh()->commit();
                    }
                    $listener->postTransactionCommit($event);
                }
            }
            
            if ($this->_nestingLevel > 0) {
                $this->_nestingLevel--;
            }            
            if ($this->_internalNestingLevel > 0) {
                $this->_internalNestingLevel--;
            } 
        }

        return true;
    }

    /**
     * rollback
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
     * @todo Shouldnt this method only commit a rollback if the transactionLevel is 1
     *       (STATE_ACTIVE)? Explanation: Otherwise a rollback that is triggered from inside doctrine
     *       in an (emulated) nested transaction would lead to a complete database level
     *       rollback even though the client code did not yet want to do that.
     *       In other words: if the user starts a transaction doctrine shouldnt roll it back.
     *       Doctrine should only roll back transactions started by doctrine. Thoughts?
     */
    public function rollback($savepoint = null)
    {
        if ($this->_nestingLevel == 0) {
            throw new Doctrine_Transaction_Exception("Rollback failed. There is no active transaction.");
        }
        
        $this->conn->connect();

        if ($this->_internalNestingLevel > 1 || $this->_nestingLevel > 1) {
            $this->_internalNestingLevel--;
            $this->_nestingLevel--;
            return false;
        }

        $listener = $this->conn->getAttribute(Doctrine::ATTR_LISTENER);

        if ( ! is_null($savepoint)) {
            $this->_nestingLevel -= $this->removeSavePoints($savepoint);

            $event = new Doctrine_Event($this, Doctrine_Event::SAVEPOINT_ROLLBACK);

            $listener->preSavepointRollback($event);
            
            if ( ! $event->skipOperation) {
                $this->rollbackSavePoint($savepoint);
            }

            $listener->postSavepointRollback($event);
        } else {
            $event = new Doctrine_Event($this, Doctrine_Event::TX_ROLLBACK);
    
            $listener->preTransactionRollback($event);
            
            if ( ! $event->skipOperation) {
                $this->_nestingLevel = 0;
                $this->_internalNestingLevel = 0;
                try {
                    $this->conn->getDbh()->rollback();
                } catch (Exception $e) {
                    throw new Doctrine_Transaction_Exception($e->getMessage());
                }
            }

            $listener->postTransactionRollback($event);
        }

        return true;
    }

    /**
     * releaseSavePoint
     * creates a new savepoint
     *
     * @param string $savepoint     name of a savepoint to create
     * @return void
     */
    protected function createSavePoint($savepoint)
    {
        throw new Doctrine_Transaction_Exception('Savepoints not supported by this driver.');
    }

    /**
     * releaseSavePoint
     * releases given savepoint
     *
     * @param string $savepoint     name of a savepoint to release
     * @return void
     */
    protected function releaseSavePoint($savepoint)
    {
        throw new Doctrine_Transaction_Exception('Savepoints not supported by this driver.');
    }

    /**
     * rollbackSavePoint
     * releases given savepoint
     *
     * @param string $savepoint     name of a savepoint to rollback to
     * @return void
     */
    protected function rollbackSavePoint($savepoint)
    {
        throw new Doctrine_Transaction_Exception('Savepoints not supported by this driver.');
    }

    /**
     * removeSavePoints
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
     * setIsolation
     *
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
     * getTransactionIsolation
     *
     * fetches the current session transaction isolation level
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
    
    /**
     * Initiates a transaction.
     *
     * This method must only be used by Doctrine itself to initiate transactions.
     * Userland-code must use {@link beginTransaction()}.
     */
    public function beginInternalTransaction($savepoint = null)
    {
        $this->_internalNestingLevel++;
        return $this->beginTransaction($savepoint);
    }
    
}
