<?php
/*
 *  $Id: Transaction.php 2268 2007-08-24 21:43:50Z jackbravo $
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
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 2268 $
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
     * @var integer $transactionLevel      the nesting level of transactions, used by transaction methods
     */
    protected $transactionLevel  = 0;
    /**
     * @var array $invalid                  an array containing all invalid records within this transaction
     */
    protected $invalid          = array();
    /**
     * @var array $delete                   two dimensional pending delete list, the records in
     *                                      this list will be deleted when transaction is committed
     */
    protected $delete           = array();
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
     * returns the state of this connection
     *
     * @see Doctrine_Connection_Transaction::STATE_* constants
     * @return integer          the connection state
     */
    public function getState()
    {
        switch ($this->transactionLevel) {
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
     * addDelete
     * adds record into pending delete list
     *
     * @param Doctrine_Record $record       a record to be added
     * @return void
     */
    public function addDelete(Doctrine_Record $record)
    {
        $name = $record->getTable()->getComponentName();
        $this->delete[$name][] = $record;
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
     * returns the pending delete list
     *
     * @return array
     */
    public function getDeletes()
    {
        return $this->delete;
    }

    /**
     * bulkDelete
     * deletes all records from the pending delete list
     *
     * @return void
     */
    public function bulkDelete()
    {

        foreach ($this->delete as $name => $deletes) {
            $record = false;
            $ids    = array();

    	    if (is_array($deletes[count($deletes)-1]->getTable()->getIdentifier())) {
                if (count($deletes) > 0) {
                    $query = 'DELETE FROM '
                           . $this->conn->quoteIdentifier($deletes[0]->getTable()->getTableName())
                           . ' WHERE ';
    
                    $params = array();
                    $cond = array();
                    foreach ($deletes as $k => $record) {
                        $ids = $record->identifier();
                        $tmp = array();
                        foreach (array_keys($ids) as $id){
                            $tmp[] = $id . ' = ? ';
                        }
                        $params = array_merge($params, array_values($ids));
                        $cond[] = '(' . implode(' AND ', $tmp) . ')';
                    }
                    $query .= implode(' OR ', $cond);

                    $this->conn->execute($query, $params);
                }
    	    } else {
    		    foreach ($deletes as $k => $record) {
                    $ids[] = $record->getIncremented();
    		    }
    		    if ($record instanceof Doctrine_Record) {
        			$params = substr(str_repeat('?, ', count($ids)), 0, -2);
    
        			$query = 'DELETE FROM '
        				   . $this->conn->quoteIdentifier($record->getTable()->getTableName())
        				   . ' WHERE '
        				   . $record->getTable()->getIdentifier()
        				   . ' IN(' . $params . ')';
        
        			$this->conn->execute($query, $ids);
    		    }
    	    }

        }
        $this->delete = array();
    }
    /**
     * getTransactionLevel
     * get the current transaction nesting level
     *
     * @return integer
     */
    public function getTransactionLevel()
    {
        return $this->transactionLevel;
    }
    /**
     * getTransactionLevel
     * set the current transaction nesting level
     *
     * @return Doctrine_Transaction     this object
     */
    public function setTransactionLevel($level)
    {
        $this->transactionLevel = $level;

        return $this;
    }
    /**
     * beginTransaction
     * Start a transaction or set a savepoint.
     *
     * if trying to set a savepoint and there is no active transaction
     * a new transaction is being started
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
            if ($this->transactionLevel == 0) {
                $event = new Doctrine_Event($this, Doctrine_Event::TX_BEGIN);

                $listener->preTransactionBegin($event);

                if ( ! $event->skipOperation) {
                    try {
                        $this->conn->getDbh()->beginTransaction();
                    } catch(Exception $e) {
                        throw new Doctrine_Transaction_Exception($e->getMessage());
                    }
                }
                $listener->postTransactionBegin($event);
            }
        }

        $level = ++$this->transactionLevel;

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
    	$this->conn->connect();

        if ($this->transactionLevel == 0) {
            return false;
        }

        $listener = $this->conn->getAttribute(Doctrine::ATTR_LISTENER);

        if ( ! is_null($savepoint)) {
            $this->transactionLevel -= $this->removeSavePoints($savepoint);

            $event = new Doctrine_Event($this, Doctrine_Event::SAVEPOINT_COMMIT);

            $listener->preSavepointCommit($event);

            if ( ! $event->skipOperation) {
                $this->releaseSavePoint($savepoint);
            }

            $listener->postSavepointCommit($event);
        } else {

            if ($this->transactionLevel == 1) {
                $event = new Doctrine_Event($this, Doctrine_Event::TX_COMMIT);
                
                $listener->preTransactionCommit($event);

                if ( ! $event->skipOperation) {
                    try {
                        $this->bulkDelete();

                    } catch(Exception $e) {
                        $this->rollback();
    
                        throw new Doctrine_Transaction_Exception($e->getMessage());
                    }
                    if ( ! empty($this->invalid)) {
                        $this->rollback();
    
                        $tmp = $this->invalid;
                        $this->invalid = array();
    
                        throw new Doctrine_Validator_Exception($tmp);
                    }
    
                    // take snapshots of all collections used within this transaction
                    foreach ($this->_collections as $coll) {
                        $coll->takeSnapshot();
                    }
                    $this->_collections = array();
                    $this->conn->getDbh()->commit();
    
                    //$this->conn->unitOfWork->reset();
                }

                $listener->postTransactionCommit($event);
            }
            
            $this->transactionLevel--;
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
     */
    public function rollback($savepoint = null)
    {
        $this->conn->connect();

        if ($this->transactionLevel == 0) {
            return false;
        }

        $listener = $this->conn->getAttribute(Doctrine::ATTR_LISTENER);

        if ( ! is_null($savepoint)) {
            $this->transactionLevel -= $this->removeSavePoints($savepoint);

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
                $this->deteles = array();

                $this->transactionLevel = 0;
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
}
