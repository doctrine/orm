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
 * Doctrine_Connection_Transaction
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Connection_Transaction implements Countable, IteratorAggregate {
    /**
     * Doctrine_Connection_Transaction is in open state when it is opened and there are no active transactions
     */
    const STATE_OPEN        = 0;
    /**
     * Doctrine_Connection_Transaction is in closed state when it is closed
     */
    const STATE_CLOSED      = 1;
    /**
     * Doctrine_Connection_Transaction is in active state when it has one active transaction
     */
    const STATE_ACTIVE      = 2;
    /**
     * Doctrine_Connection_Transaction is in busy state when it has multiple active transactions
     */
    const STATE_BUSY        = 3;
    /**
     * @var Doctrine_Connection $conn       the connection object
     */
    private $conn;
    /**
     * @see Doctrine_Connection_Transaction::STATE_* constants
     * @var boolean $state                  the current state of the connection
     */
    private $state              = 0;
    /**
     * @var integer $transaction_level      the nesting level of transactions, used by transaction methods
     */
    private $transaction_level  = 0;
    /**
     * @var array $invalid                  an array containing all invalid records within this transaction
     */
    protected $invalid          = array();
    /**
     * @var array $update                   two dimensional pending update list, the records in
     *                                      this list will be updated when transaction is committed
     */
    protected $update           = array();
    /**
     * @var array $insert                   two dimensional pending insert list, the records in
     *                                      this list will be inserted when transaction is committed
     */
    protected $insert           = array();
    /**
     * @var array $delete                   two dimensional pending delete list, the records in
     *                                      this list will be deleted when transaction is committed
     */
    protected $delete           = array();
    /**
     * the constructor
     *
     * @param Doctrine_Connection $conn
     */
    public function __construct(Doctrine_Connection $conn) {
        $this->conn  = $conn;
        $this->state = Doctrine_Connection_Transaction::STATE_OPEN;
    }
    /**
     * returns the state of this connection
     *
     * @see Doctrine_Connection_Transaction::STATE_* constants
     * @return integer          the connection state
     */
    public function getState() {
        return $this->state;
    }
    /**
     * get the current transaction nesting level
     *
     * @return integer
     */
    public function getTransactionLevel() {
        return $this->transaction_level;
    }
    /**
     * beginTransaction
     * starts a new transaction
     * if the lockmode is long this starts db level transaction
     *
     * @return void
     */
    public function beginTransaction() {
        if($this->transaction_level == 0) {

            if($this->conn->getAttribute(Doctrine::ATTR_LOCKMODE) == Doctrine::LOCK_PESSIMISTIC) {
                $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onPreTransactionBegin($this->conn);

                $this->conn->getDBH()->beginTransaction();

                $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onTransactionBegin($this->conn);
            }
            $this->state  = Doctrine_Connection_Transaction::STATE_ACTIVE;
        } else {
            $this->state = Doctrine_Connection_Transaction::STATE_BUSY;
        }
        $this->transaction_level++;
    }
    /**
     * commits the current transaction
     * if lockmode is short this method starts a transaction
     * and commits it instantly
     *
     * @throws Doctrine_Connection_Transaction_Exception    if the transaction fails at PDO level
     * @throws Doctrine_Validator_Exception                 if the transaction fails due to record validations
     * @return void
     */
    public function commit() {

        $this->transaction_level--;
    
        if($this->transaction_level == 0) {
            $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onPreTransactionCommit($this->conn);

            if($this->conn->getAttribute(Doctrine::ATTR_LOCKMODE) == Doctrine::LOCK_OPTIMISTIC) {
                $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onPreTransactionBegin($this->conn);

                $this->conn->getDBH()->beginTransaction();

                $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onTransactionBegin($this->conn);
            }

            try {
                $this->bulkDelete();

            } catch(Exception $e) {
                $this->rollback();

                throw new Doctrine_Connection_Transaction_Exception($e->__toString());
            }
            
            if(count($this->invalid) > 0) {
                $this->rollback();
                
                $tmp = $this->invalid;
                $this->invalid = array();
                
                throw new Doctrine_Validator_Exception($tmp);
            }
            
            $this->conn->getDBH()->commit();   
            
            $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onTransactionCommit($this->conn);

            $this->state     = Doctrine_Connection_Transaction::STATE_OPEN;
            $this->invalid   = array();

        } elseif($this->transaction_level == 1)
            $this->state = Doctrine_Connection_Transaction::STATE_ACTIVE;
    }
    /**
     * rollback
     * rolls back all transactions
     *
     * this method also listens to onPreTransactionRollback and onTransactionRollback
     * eventlisteners
     *
     * @return void
     */
    public function rollback() {
        $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onPreTransactionRollback($this->conn);
        
        $this->delete = array();
        $this->insert = array();
        $this->update = array();

        $this->transaction_level = 0;
        $this->conn->getDBH()->rollback();
        $this->state = Doctrine_Connection_Transaction::STATE_OPEN;

        $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onTransactionRollback($this->conn);
    }
    /**
     * bulkDelete
     * deletes all records from the pending delete list
     *
     * @return void
     */
    public function bulkDelete() {
        foreach($this->delete as $name => $deletes) {
            $record = false;
            $ids    = array();
            foreach($deletes as $k => $record) {
                $ids[] = $record->getIncremented();
                $record->assignIdentifier(false);
            }
            if($record instanceof Doctrine_Record) {
                $params  = substr(str_repeat("?, ",count($ids)),0,-2);
                
                $query   = 'DELETE FROM '
                         . $record->getTable()->getTableName() 
                         . ' WHERE ' 
                         . $record->getTable()->getIdentifier()
                         . ' IN(' . $params . ')';

                $this->conn->execute($query, $ids);
            }

        }
        $this->delete = array();
    }
    /**
     * updates the given record
     *
     * @param Doctrine_Record $record
     * @return boolean
     */
    public function update(Doctrine_Record $record) {
        $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onPreUpdate($record);

        $array = $record->getPrepared();

        if(empty($array))
            return false;

        $set   = array();
        foreach($array as $name => $value):
                $set[] = $name." = ?";

                if($value instanceof Doctrine_Record) {
                    switch($value->getState()):
                        case Doctrine_Record::STATE_TCLEAN:
                        case Doctrine_Record::STATE_TDIRTY:
                            $record->save();
                        default:
                            $array[$name] = $value->getIncremented();
                            $record->set($name, $value->getIncremented());
                    endswitch;
                }
        endforeach;

        $params   = array_values($array);
        $id       = $record->obtainIdentifier();


        if( ! is_array($id))
            $id = array($id);

        $id     = array_values($id);
        $params = array_merge($params, $id);


        $sql  = "UPDATE ".$record->getTable()->getTableName()." SET ".implode(", ",$set)." WHERE ".implode(" = ? AND ",$record->getTable()->getPrimaryKeys())." = ?";

        $stmt = $this->conn->getDBH()->prepare($sql);
        $stmt->execute($params);

        $record->assignIdentifier(true);

        $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onUpdate($record);

        return true;
    }
    /**
     * inserts a record into database
     *
     * @param Doctrine_Record $record
     * @return boolean
     */
    public function insert(Doctrine_Record $record) {
         // listen the onPreInsert event
        $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onPreInsert($record);
        
        $array = $record->getPrepared();

        if(empty($array))
            return false;

        $table     = $record->getTable();
        $keys      = $table->getPrimaryKeys();


            

        $seq = $record->getTable()->getSequenceName();

        if( ! empty($seq)) {
            $id             = $this->getNextID($seq);
            $name           = $record->getTable()->getIdentifier();
            $array[$name]   = $id;
        }

        $strfields = join(", ", array_keys($array));
        $strvalues = substr(str_repeat("?, ",count($array)),0,-2); 
        $sql  = "INSERT INTO ".$record->getTable()->getTableName()." (".$strfields.") VALUES (".$strvalues.")";

        $stmt = $this->conn->getDBH()->prepare($sql);

        $stmt->execute(array_values($array));
        

        if(count($keys) == 1 && $keys[0] == $table->getIdentifier()) {
            $id = $this->conn->getDBH()->lastInsertID();

            if( ! $id)
                $id = $table->getMaxIdentifier();
            
            $record->assignIdentifier($id);
        } else
            $record->assignIdentifier(true);

        // listen the onInsert event
        $table->getAttribute(Doctrine::ATTR_LISTENER)->onInsert($record);

        return true;
    }
    /**
     * adds record into pending insert list
     * @param Doctrine_Record $record
     */
    public function addInsert(Doctrine_Record $record) {
        $name = $record->getTable()->getComponentName();
        $this->insert[$name][] = $record;
    }
    /**
     * adds record into penging update list
     * @param Doctrine_Record $record
     */
    public function addUpdate(Doctrine_Record $record) {
        $name = $record->getTable()->getComponentName();
        $this->update[$name][] = $record;
    }
    /**
     * adds record into pending delete list
     * @param Doctrine_Record $record
     */
    public function addDelete(Doctrine_Record $record) {
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
    public function addInvalid(Doctrine_Record $record) {
        if(in_array($record, $this->invalid))
            return false;

        $this->invalid[] = $record;
        return true;
    }
    /**
     * returns the pending insert list
     *
     * @return array
     */
    public function getInserts() {
        return $this->insert;
    }
    /**
     * returns the pending update list
     *
     * @return array
     */
    public function getUpdates() {
        return $this->update;
    }
    /**
     * returns the pending delete list
     *
     * @return array
     */
    public function getDeletes() {
        return $this->delete;
    }
    public function getIterator() { }
    /**
     * an alias for getTransactionLevel
     *
     * @return integer          returns the nesting level of this transaction
     */
    public function count() {
        return $this->transaction_level;
    }
}
