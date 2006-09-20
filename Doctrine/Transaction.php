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
 * Doctrine_Transaction
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Transaction implements Countable, IteratorAggregate {
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
    private $connection;
    /**
     * @see Doctrine_Transaction::STATE_* constants
     * @var boolean $state                  the current state of the connection
     */
    private $state              = 0;
    /**
     * @var integer $transaction_level      the nesting level of transactions, used by transaction methods
     */
    private $transaction_level  = 0;
    /**
     * @var Doctrine_Validator $validator   transaction validator
     */
    private $validator;
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
        $this->conn = $conn;
        $this->state = Doctrine_Transaction::STATE_OPEN;
    }
    /**
     * returns the state of this connection
     *
     * @see Doctrine_Transaction::STATE_* constants
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
            $this->state  = Doctrine_Transaction::STATE_ACTIVE;
        } else {
            $this->state = Doctrine_Transaction::STATE_BUSY;
        }
        $this->transaction_level++;
    }
    /**
     * commits the current transaction
     * if lockmode is short this method starts a transaction
     * and commits it instantly
     *
     * @return void
     */
    public function commit() {

        $this->transaction_level--;
    
        if($this->transaction_level == 0) {


            if($this->conn->getAttribute(Doctrine::ATTR_LOCKMODE) == Doctrine::LOCK_OPTIMISTIC) {
                $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onPreTransactionBegin($this->conn);

                $this->conn->getDBH()->beginTransaction();

                $this->getAttribute(Doctrine::ATTR_LISTENER)->onTransactionBegin($this->conn);
            }
    
            if($this->conn->getAttribute(Doctrine::ATTR_VLD))
                $this->validator = new Doctrine_Validator();

            try {
                
                $this->bulkInsert();
                $this->bulkUpdate();
                $this->bulkDelete();

                if($this->conn->getAttribute(Doctrine::ATTR_VLD)) {

                    if($this->validator->hasErrors()) {
                        $this->rollback();
                        throw new Doctrine_Validator_Exception($this->validator);
                    }
                }

                $this->conn->getDBH()->commit();

            } catch(PDOException $e) {
                $this->rollback();

                throw new Doctrine_Exception($e->__toString());
            }

            $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onTransactionCommit($this->conn);

            $this->delete = array();
            $this->state  = Doctrine_Transaction::STATE_OPEN;
    
            $this->validator = null;
    
        } elseif($this->transaction_level == 1)
            $this->state = Doctrine_Transaction::STATE_ACTIVE;
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

        $this->transaction_level = 0;
        $this->conn->getDBH()->rollback();
        $this->state = Doctrine_Transaction::STATE_OPEN;

        $this->conn->getAttribute(Doctrine::ATTR_LISTENER)->onTransactionRollback($this->conn);
    }
    /**
     * bulkInsert
     * inserts all the objects in the pending insert list into database
     *
     * @return void
     */
    public function bulkInsert() {
        if(empty($this->insert))
            return false;

        foreach($this->insert as $name => $inserts) {
            if( ! isset($inserts[0]))
                continue;

            $record    = $inserts[0];
            $table     = $record->getTable();
            $seq       = $table->getSequenceName();

            $increment = false;
            $keys      = $table->getPrimaryKeys();
            $id        = null;

            if(count($keys) == 1 && $keys[0] == $table->getIdentifier()) {
                $increment = true;
            }

            foreach($inserts as $k => $record) {
                $table->getAttribute(Doctrine::ATTR_LISTENER)->onPreSave($record);
                // listen the onPreInsert event
                $table->getAttribute(Doctrine::ATTR_LISTENER)->onPreInsert($record);


                $this->insert($record);
                if($increment) {
                    if($k == 0) {
                        // record uses auto_increment column

                        $id = $this->conn->getDBH()->lastInsertID();

                        if( ! $id)
                            $id = $table->getMaxIdentifier();
                    }
    
                    $record->assignIdentifier($id);
                    $id++;
                } else
                    $record->assignIdentifier(true);

                // listen the onInsert event
                $table->getAttribute(Doctrine::ATTR_LISTENER)->onInsert($record);

                $table->getAttribute(Doctrine::ATTR_LISTENER)->onSave($record);
            }
        }
        $this->insert = array();
        return true;
    }
    /**
     * bulkUpdate
     * updates all objects in the pending update list
     *
     * @return void
     */
    public function bulkUpdate() {
        foreach($this->update as $name => $updates) {
            $ids = array();

            foreach($updates as $k => $record) {
                $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onPreSave($record);
                // listen the onPreUpdate event
                $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onPreUpdate($record);

                $this->update($record);
                // listen the onUpdate event
                $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onUpdate($record);

                $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onSave($record);
            }
        }
        $this->update = array();
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
                $table  = $record->getTable();

                $params  = substr(str_repeat("?, ",count($ids)),0,-2);
                $query   = "DELETE FROM ".$record->getTable()->getTableName()." WHERE ".$table->getIdentifier()." IN(".$params.")";
                $this->conn->execute($query,$ids);

                $record->getTable()->getCache()->deleteMultiple($ids);
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

        if(isset($this->validator)) {
            if( ! $this->validator->validateRecord($record)) {
                return false;
            }
        }

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

        return true;
    }
    /**
     * inserts a record into database
     *
     * @param Doctrine_Record $record
     * @return boolean
     */
    public function insert(Doctrine_Record $record) {
        $array = $record->getPrepared();

        if(empty($array))
            return false;

        $seq = $record->getTable()->getSequenceName();

        if( ! empty($seq)) {
            $id             = $this->getNextID($seq);
            $name           = $record->getTable()->getIdentifier();
            $array[$name]   = $id;
        }

        if(isset($this->validator)) {
            if( ! $this->validator->validateRecord($record)) {
                return false;
            }
        }

        $strfields = join(", ", array_keys($array));
        $strvalues = substr(str_repeat("?, ",count($array)),0,-2); 
        $sql  = "INSERT INTO ".$record->getTable()->getTableName()." (".$strfields.") VALUES (".$strvalues.")";

        $stmt = $this->conn->getDBH()->prepare($sql);

        $stmt->execute(array_values($array));

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

    public function count() { }
}
