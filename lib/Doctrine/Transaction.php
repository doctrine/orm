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
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Transaction { 
    /**
     * Doctrine_Transaction is in open state when it is opened and there are no active transactions
     */
    const STATE_OPEN        = 0;
    /**
     * Doctrine_Transaction is in closed state when it is closed
     */
    const STATE_CLOSED      = 1;
    /**
     * Doctrine_Transaction is in active state when it has one active transaction
     */
    const STATE_ACTIVE      = 2;
    /**
     * Doctrine_Transaction is in busy state when it has multiple active transactions
     */
    const STATE_BUSY        = 3;
    /**
     * @var Doctrine_Connection $conn       the connection object
     */
    protected $conn;
    /**
     * @var integer $transaction_level      the nesting level of transactions, used by transaction methods
     */
    protected $transactionLevel  = 0;
    /**
     * the constructor
     *
     * @param Doctrine_Connection $conn     Doctrine_Connection object
     */
    public function __construct(Doctrine_Connection $conn) {
        $this->conn  = $conn;
    }
    /**
     * getState
     * returns the state of this connection
     *
     * @see Doctrine_Connection_Transaction::STATE_* constants
     * @return integer          the connection state
     */
    public function getState() {
        switch($this->transactionLevel) {
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
     * getTransactionLevel
     * get the current transaction nesting level
     *
     * @return integer
     */
    public function getTransactionLevel() {
        return $this->transactionLevel;
    }
    /**
     * beginTransaction
     * Start a transaction or set a savepoint.
     *
     * @param string $savepoint                 name of a savepoint to set
     * @throws Doctrine_Transaction_Exception   if trying to create a savepoint and there
     *                                          are no active transactions
     *
     * @return integer                          current transaction nesting level
     */
    public function beginTransaction($savepoint = null) {
        $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onPreTransactionBegin($this->conn);

        if( ! is_null($savepoint)) {
            if($this->transactionLevel == 0)
                throw new Doctrine_Transaction_Exception('Savepoint cannot be created when changes are auto committed');

            $this->createSavePoint($savepoint);
        } else {
            if($this->transactionLevel == 0) 
                $this->conn->getDbh()->beginTransaction();
        }

        $level = ++$this->transactionLevel;

        $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onTransactionBegin($this->conn);

        return $level;
    }
    /**
     * commit
     * Commit the database changes done during a transaction that is in
     * progress or release a savepoint. This function may only be called when
     * auto-committing is disabled, otherwise it will fail. Therefore, a new
     * transaction is implicitly started after committing the pending changes.
     *
     * @param string $savepoint                 name of a savepoint to release
     * @throws Doctrine_Transaction_Exception   if the transaction fails at PDO level
     * @throws Doctrine_Transaction_Exception   if there are no active transactions
     * @throws Doctrine_Validator_Exception     if the transaction fails due to record validations
     * @return void
     */
    public function commit($savepoint = null) {
        if($this->transactionLevel == 0)
            throw new Doctrine_Transaction_Exception('Commit/release savepoint cannot be done. There is no active transaction.');

        if ( ! is_null($savepoint)) {
            $this->releaseSavePoint($savepoint);
        } else {
            $this->transactionLevel--;
    
            if($this->transactionLevel == 0) {
                $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onPreTransactionCommit($this->conn);
    
                try {
                    $this->bulkDelete();
    
                } catch(Exception $e) {
                    $this->rollback();
    
                    throw new Doctrine_Connection_Transaction_Exception($e->__toString());
                }
    
                if($tmp = $this->unitOfWork->getInvalid()) {
                    $this->rollback();
    
                    throw new Doctrine_Validator_Exception($tmp);
                }
    
                $this->conn->getDbh()->commit();
    
                $this->unitOfWork->reset();
    
                $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onTransactionCommit($this->conn);
            }
        }
    }
    /**
     * rollback
     * Cancel any database changes done during a transaction or since a specific
     * savepoint that is in progress. This function may only be called when
     * auto-committing is disabled, otherwise it will fail. Therefore, a new
     * transaction is implicitly started after canceling the pending changes.
     *
     * this method listens to onPreTransactionRollback and onTransactionRollback
     * eventlistener methods
     *
     * @param string $savepoint                 name of a savepoint to rollback to
     * @throws Doctrine_Transaction_Exception   if there are no active transactions
     * @return void
     */
    public function rollback($savepoint = null) {
        if($this->transactionLevel == 0)
            throw new Doctrine_Transaction_Exception('Rollback cannot be done. There is no active transaction.');

        $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onPreTransactionRollback($this->conn);

        if ( ! is_null($savepoint)) {
            $this->rollbackSavePoint($savepoint);
        } else {
            $this->unitOfWork->reset();

            $this->transactionLevel = 0;

            $this->conn->getDbh()->rollback();
        }
        $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onTransactionRollback($this->conn);
    }
    /**
     * releaseSavePoint
     * creates a new savepoint
     *
     * @param string $savepoint     name of a savepoint to create
     * @return void
     */
    public function createSavePoint($savepoint) {
        throw new Doctrine_Transaction_Exception('Savepoints not supported by this driver.');
    }
    /**
     * releaseSavePoint
     * releases given savepoint
     *
     * @param string $savepoint     name of a savepoint to release
     * @return void
     */
    public function releaseSavePoint($savepoint) {
        throw new Doctrine_Transaction_Exception('Savepoints not supported by this driver.');
    }
    /**
     * rollbackSavePoint
     * releases given savepoint
     *
     * @param string $savepoint     name of a savepoint to rollback to
     * @return void
     */
    public function rollbackSavePoint($savepoint) {
        throw new Doctrine_Transaction_Exception('Savepoints not supported by this driver.');
    }
}
