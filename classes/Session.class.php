<?php
require_once("Configurable.class.php");
require_once("Record.class.php");
/**
 * @author      Konsta Vesterinen
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 * @version     1.0 alpha
 */
abstract class Doctrine_Session extends Doctrine_Configurable implements Countable, IteratorAggregate {
    /**
     * Doctrine_Session is in open state when it is opened and there are no active transactions
     */
    const STATE_OPEN        = 0;
    /**
     * Doctrine_Session is in closed state when it is closed
     */
    const STATE_CLOSED      = 1;
    /**
     * Doctrine_Session is in active state when it has one active transaction
     */
    const STATE_ACTIVE      = 2;
    /**
     * Doctrine_Session is in busy state when it has multiple active transactions
     */
    const STATE_BUSY        = 3;
    /**
     * @var $dbh                            the database handle
     */
    private $dbh;
    /**
     * @var array $tables                   an array containing all the initialized Doctrine_Table objects
     *                                      keys representing Doctrine_Table component names and values as Doctrine_Table objects
     */
    private $tables   = array();
    /**
     * @see Doctrine_Session::STATE_* constants
     * @var boolean $state                  the current state of the session
     */
    private $state       = 0;
    /**
     * @var array $update                   two dimensional pending update list, the records in
     *                                      this list will be updated when transaction is committed
     */
    private $update     = array(array());
    /**
     * @var array $insert                   two dimensional pending insert list, the records in
     *                                      this list will be inserted when transaction is committed
     */
    private $insert     = array(array());
    /**
     * @var array $delete                   two dimensional pending delete list, the records in
     *                                      this list will be deleted when transaction is committed
     */
    private $delete     = array(array());
    /** 
     * @var integer $transaction_level      the nesting level of transactions, used by transaction methods
     */
    private $transaction_level = 0;
    
    private $validator;
    /**
     * @var PDO $cacheHandler
     */
    private $cacheHandler;



    /**
     * the constructor
     * @param PDO $pdo  -- database handle
     */
    public function __construct(Doctrine_Manager $manager,PDO $pdo) {
        $this->dbh      = $pdo;

        $this->setParent($manager);

        $this->state = Doctrine_Session::STATE_OPEN;

        $this->dbh->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);   

        switch($this->getAttribute(Doctrine::ATTR_CACHE)):
            case Doctrine::CACHE_SQLITE:
                $dir = $this->getAttribute(Doctrine::ATTR_CACHE_DIR).DIRECTORY_SEPARATOR;
                $dsn = "sqlite:".$dir."data.cache";

                $this->cacheHandler = Doctrine_DB::getConn($dsn);
                $this->cacheHandler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->cacheHandler->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
            break;
        endswitch;

        $this->getAttribute(Doctrine::ATTR_LISTENER)->onOpen($this);
    }

    public function getCacheHandler() {
        return $this->cacheHandler;
    }
    /**
     * @return integer          the session state
     */
    public function getState() {
        return $this->state;
    }
    /**
     * @return Doctrine_Manager
     */
    public function getManager() {
        return $this->getParent();
    }
    /**
     * @return object PDO       the database handle
     */
    public function getDBH() {
        return $this->dbh;
    }
    /**
     * query
     * queries the database with Doctrine Query Language
     */
    final public function query($query,array $params = array()) {
        $parser = new Doctrine_DQL_Parser($this);

        return $parser->query($query, $params);
    }
    /**
     * queries the database with limit and offset
     * added to the query and returns a PDOStatement object
     *
     * @param string $query
     * @param integer $limit
     * @param integer $offset
     * @return PDOStatement
     */
    public function select($query,$limit = 0,$offset = 0) {
        if($limit > 0 || $offset > 0)
            $query = $this->modifyLimitQuery($query,$limit,$offset);
        
        return $this->dbh->query($query);
    }
    /**
     * @return object PDOStatement      -- the PDOStatement object
     */
    public function execute($query, array $params = array()) {
        if( ! empty($params)) {
            $stmt = $this->dbh->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } else {
            return $this->dbh->query($query);
        }
    }
    /**
     * @param $mixed -- Doctrine_Table name
     * @return boolean
     */
    public function hasTable($name) {
        return isset($this->tables[$name]);
    }
    /**
     * @param string $name              component name
     * @throws Doctrine_Table_Exception
     * @return object Doctrine_Table
     */
    public function getTable($name) {
        $name = ucwords(strtolower($name));

        if(isset($this->tables[$name]))
            return $this->tables[$name];
            
        $class = $name."Table";

        if(class_exists($class, false) && in_array("Doctrine_Table", class_parents($class)))
            return new $class($name);
        else
            return new Doctrine_Table($name);
    }
    /**
     * @return array -- an array of all initialized tables
     */
    public function getTables() {
        return $this->tables;
    }
    /**
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->tables);
    }
    /**
     * @return integer
     */
    public function count() {
        return count($this->tables);
    }
    /**
     * @param $objTable -- a Doctrine_Table object to be added into factory registry
     * @return boolean
     */
    public function addTable(Doctrine_Table $objTable) {
        $name = $objTable->getComponentName();

        if(isset($this->tables[$name]))
            return false;

        $this->tables[$name] = $objTable;
        return true;
    }
    /**
     * create                       creates a record
     * @param string $name          component name
     * @return Doctrine_Record      Doctrine_Record object
     */
    public function create($name) {
        return $this->getTable($name)->create();
    }
    /**
     * buildFlushTree
     * @return array
     */
    public function buildFlushTree() {
        $tree = array();
        foreach($this->tables as $k => $table) {
            $tmp = array();
            $tmp[] = $table->getComponentName();
            $pos = 0;
            foreach($table->getForeignKeys() as $fk) {
                if($fk instanceof Doctrine_ForeignKey ||
                   $fk instanceof Doctrine_LocalKey) {
                                                     	
                    $name  = $fk->getTable()->getComponentName();
                    $index = array_search($name,$tree);

                    if(isset($locked[$name])) {
                        $pos = $index;
                        continue;
                    }

                    if($index !== false)
                        unset($tree[$index]);

                    switch($fk->getType()):
                        case Doctrine_Table::ONE_COMPOSITE:
                        case Doctrine_Table::ONE_AGGREGATE:
                            array_unshift($tmp,$name);
                        break;
                        case Doctrine_Table::MANY_COMPOSITE:
                        case Doctrine_Table::MANY_AGGREGATE:
                            $tmp[] = $name;
                        break;
                    endswitch;
                    $locked[$name] = true;
                }
            }
            $index = array_search($k,$tree);

            if($index === false) {
                if($pos != 0) {
                    $first = array_splice($tree,0,$pos);
                    $tree  = array_merge($first, $tmp, $tree);
                } else {
                    $tree = array_merge($tree,$tmp);
                }
            } else {
                $first = array_splice($tree,0,$index);
                array_splice($tree, 0, ($index + 1));
                $tree  = array_merge($first, $tmp, $tree);
            }
        }
        return $tree;
    }

    /**
     * flush                        saves all the records from all tables
     *                              this operation is isolated using a transaction
     * @return void
     */
    public function flush() {
        $this->beginTransaction();
        $this->saveAll();
        $this->commit();
    }
    /**
     * saveAll                      save all the records from all tables
     */
    private function saveAll() {
        $tree = $this->buildFlushTree();

        foreach($tree as $name) {
            $table = $this->tables[$name];

            foreach($table->getRepository() as $record) {
                $this->save($record);
            }
        }
        foreach($tree as $name) {
            $table = $this->tables[$name];
            foreach($table->getRepository() as $record) {
                $record->saveAssociations();
            }
        }
    }
    /**
     * clear -- clears the whole registry
     * @return void
     */
    public function clear() {
        foreach($this->tables as $k => $objTable) {
            $objTable->getRepository()->evictAll();
        }
        $this->tables = array();
    }
    /**
     * close
     * closes the session
     * @return void
     */
    public function close() {
        $this->getAttribute(Doctrine::ATTR_LISTENER)->onPreClose($this);

        $this->clear();
        $this->state = Doctrine_Session::STATE_CLOSED;
        
        $this->getAttribute(Doctrine::ATTR_LISTENER)->onClose($this);
    }
    /**
     * get the current transaction nesting level
     * @return integer          transaction nesting level
     */
    public function getTransactionLevel() {
        return $this->transaction_level;
    }
    /**
     * beginTransaction
     * starts a new transaction
     * @return void
     */
    public function beginTransaction() {
        if($this->transaction_level == 0) {

            if($this->getAttribute(Doctrine::ATTR_LOCKMODE) == Doctrine::LOCK_PESSIMISTIC) {
                $this->getAttribute(Doctrine::ATTR_LISTENER)->onPreTransactionBegin($this);
                $this->dbh->beginTransaction();
                $this->getAttribute(Doctrine::ATTR_LISTENER)->onTransactionBegin($this);
            }
            $this->state  = Doctrine_Session::STATE_ACTIVE;
        } else {
            $this->state = Doctrine_Session::STATE_BUSY;
        }
        $this->transaction_level++;
    }
    /**
     * commits the current transaction
     * if lockmode is optimistic this method starts a transaction 
     * and commits it instantly
     * @return void
     */
    public function commit() {
        $this->transaction_level--;

        if($this->transaction_level == 0) {


            if($this->getAttribute(Doctrine::ATTR_LOCKMODE) == Doctrine::LOCK_OPTIMISTIC) {
                $this->getAttribute(Doctrine::ATTR_LISTENER)->onPreTransactionBegin($this);

                $this->dbh->beginTransaction();

                $this->getAttribute(Doctrine::ATTR_LISTENER)->onTransactionBegin($this);
            }

            if($this->getAttribute(Doctrine::ATTR_VLD)) {
                $this->validator = new Doctrine_Validator();
            }

            $this->bulkInsert();
            $this->bulkUpdate();
            $this->bulkDelete();


            if(isset($this->validator) && $this->validator->hasErrors()) {
                $this->rollback();
                throw new Doctrine_Validator_Exception($this->validator);
            }

            $this->dbh->commit();
            $this->getAttribute(Doctrine::ATTR_LISTENER)->onTransactionCommit($this);

            $this->delete = array(array());
            $this->state  = Doctrine_Session::STATE_OPEN;

            $this->validator = null;

        } elseif($this->transaction_level == 1)
            $this->state = Doctrine_Session::STATE_ACTIVE;
    }
    /**
     * rollback
     * rolls back all the transactions
     * @return void
     */
    public function rollback() {
        $this->getAttribute(Doctrine::ATTR_LISTENER)->onPreTransactionRollback($this);

        $this->transaction_level = 0;
        $this->dbh->rollback();
        $this->state = Doctrine_Session::STATE_OPEN;

        $this->getAttribute(Doctrine::ATTR_LISTENER)->onTransactionRollback($this);
    }
    /**
     * bulkInsert
     * inserts all the objects in the pending insert list into database
     * @return void
     */
    public function bulkInsert() {
        foreach($this->insert as $name => $inserts) {
            if( ! isset($inserts[0]))
                continue;

            $record    = $inserts[0];
            $table     = $record->getTable();
            $seq       = $table->getSequenceName();
            $increment = false;
            $id        = null;
            $keys      = $table->getPrimaryKeys();
            if(count($keys) == 1 && $keys[0] == "id") {

                // record uses auto_increment column

                $sql  = "SELECT MAX(id) FROM ".$record->getTable()->getTableName();
                $stmt = $this->dbh->query($sql);
                $data = $stmt->fetch(PDO::FETCH_NUM);
                $id   = $data[0];
                $stmt->closeCursor();
                $increment = true;
            }
            

            foreach($inserts as $k => $record) {
                $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onPreSave($record);
                // listen the onPreInsert event
                $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onPreInsert($record);

                if($increment) {
                    // record uses auto_increment column
                    $id++;
                }

                $this->insert($record,$id);
                // listen the onInsert event
                $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onInsert($record);

                $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onSave($record);
            }
        }
        $this->insert = array(array());
    }
    /**
     * bulkUpdate
     * updates all objects in the pending update list
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

                $ids[] = $record->getID();
            }
            if(isset($record))
                $record->getTable()->getCache()->deleteMultiple($ids);
        }
        $this->update = array(array());
    }
    /**
     * bulkDelete
     * @return void
     */
    public function bulkDelete() {
        foreach($this->delete as $name => $deletes) {
            $record = false;
            $ids = array();
            foreach($deletes as $k => $record) {
                $ids[] = $record->getID();
                $record->setID(null);
            }
            if($record instanceof Doctrine_Record) {
                $params  = substr(str_repeat("?, ",count($ids)),0,-2);
                $query   = "DELETE FROM ".$record->getTable()->getTableName()." WHERE id IN(".$params.")";
                $this->execute($query,$ids);

                $record->getTable()->getCache()->deleteMultiple($ids);
            }
        }
        $this->delete = array(array());
    }


    
    /**
     * @param Doctrine_Collection $coll
     * @return void
     */
    public function saveCollection(Doctrine_Collection $coll) {
        $this->beginTransaction();

        foreach($coll as $key=>$record):
                $record->save();
        endforeach;

        $this->commit();
    }
    /**
     * @param Doctrine_Collection $coll
     * @return void
     */
    public function deleteCollection(Doctrine_Collection $coll) {
        $this->beginTransaction();
        foreach($coll as $k=>$record) {
            $record->delete();
        }
        $this->commit();
    }
    /**
     * @param Doctrine_Record $record
     * @return void
     */

    public function save(Doctrine_Record $record) {

        switch($record->getState()):
            case Doctrine_Record::STATE_TDIRTY:
                $this->addInsert($record);
            break;
            case Doctrine_Record::STATE_DIRTY:
            case Doctrine_Record::STATE_PROXY:
                $this->addUpdate($record);
            break;
            case Doctrine_Record::STATE_CLEAN:
            case Doctrine_Record::STATE_TCLEAN:
                // do nothing
            break;
        endswitch;
    }
    /**
     * @param Doctrine_Record $record
     */
    final public function saveRelated(Doctrine_Record $record) {
        $saveLater = array();
        foreach($record->getReferences() as $k=>$v) {
            $fk = $record->getTable()->getForeignKey($k);
            if($fk instanceof Doctrine_ForeignKey ||
               $fk instanceof Doctrine_LocalKey) {
                switch($fk->getType()):
                    case Doctrine_Table::ONE_COMPOSITE:
                    case Doctrine_Table::MANY_COMPOSITE:
                        $local = $fk->getLocal();
                        $foreign = $fk->getForeign();

                        if($record->getTable()->hasPrimaryKey($fk->getLocal())) {
                            switch($record->getState()):
                                case Doctrine_Record::STATE_TDIRTY:
                                case Doctrine_Record::STATE_TCLEAN:
                                    $saveLater[$k] = $fk;
                                break;
                                case Doctrine_Record::STATE_CLEAN:
                                case Doctrine_Record::STATE_DIRTY:
                                    $v->save();    
                                break;
                            endswitch;
                        } else {
                            // ONE-TO-ONE relationship
                            $obj = $record->get($fk->getTable()->getComponentName());

                            if($obj->getState() != Doctrine_Record::STATE_TCLEAN)
                                $obj->save();

                        }
                    break;
                endswitch;
            } elseif($fk instanceof Doctrine_Association) {
                $v->save();
            }
        }
        return $saveLater;
    }
    /**
     * @param Doctrine_Record $record
     * @return boolean
     */
    private function update(Doctrine_Record $record) {
        $array = $record->getModified();
        if(empty($array))
            return false;



        $set   = array();
        foreach($array as $name => $value):
                $set[] = $name." = ?";

                if($value instanceof Doctrine_Record) {
                    $array[$name] = $value->getID();
                    $record->set($name, $value->getID());
                }
        endforeach;

        if(isset($this->validator)) {
            if( ! $this->validator->validateRecord($record)) {
                return false;
            }
        }

        $params   = array_values($array);
        $params[] = $record->getID();


        $sql  = "UPDATE ".$record->getTable()->getTableName()." SET ".implode(", ",$set)." WHERE ".implode(" = ? && ",$record->getTable()->getPrimaryKeys())." = ?";
        $stmt = $this->dbh->prepare($sql);
        $stmt->execute($params);

        $record->setID($record->getID());

        return true;
    }
    /**
     * @param Doctrine_Record $record
     * @return boolean
     */
    private function insert(Doctrine_Record $record,$id = null) {
        $array = $record->getModified();
        if(empty($array))
            return false;


        foreach($record->getTable()->getInheritanceMap() as $k=>$v):
            $array[$k] = $v;
        endforeach;

        $seq = $record->getTable()->getSequenceName();
        if( ! empty($seq)) {
            $id = $this->getNextID($seq);
            $array["id"] = $id;
        }

        foreach($array as $k => $value) {
            if($value instanceof Doctrine_Record) {
                $array[$k] = $value->getID();
                $record->set($k,$value->getID());
            }
        }

        if(isset($this->validator)) {
            if( ! $this->validator->validateRecord($record)) {
                return false;
            }
        }

        $strfields = join(", ", array_keys($array));
        $strvalues = substr(str_repeat("?, ",count($array)),0,-2);

        $sql  = "INSERT INTO ".$record->getTable()->getTableName()." (".$strfields.") VALUES (".$strvalues.")";

        $stmt = $this->dbh->prepare($sql);

        $stmt->execute(array_values($array));

        $record->setID($id);
        return true;
    }
    /**
     * deletes all related composites
     * this method is always called internally when this data access object is deleted
     *
     * @return void
     */
    final public function deleteComposites(Doctrine_Record $record) {
        foreach($record->getTable()->getForeignKeys() as $fk) {
            switch($fk->getType()):
                case Doctrine_Table::ONE_COMPOSITE:
                case Doctrine_Table::MANY_COMPOSITE:
                    $obj = $record->get($fk->getTable()->getComponentName());
                    $obj->delete();
                break;
            endswitch;
        }
    }
    /**
     * deletes this data access object and all the related composites
     * this operation is isolated by a transaction
     * 
     * this event can be listened by the onPreDelete and onDelete listeners
     *
     * @return boolean      true on success, false on failure
     */
    final public function delete(Doctrine_Record $record) {
        switch($record->getState()):
            case Doctrine_Record::STATE_PROXY:
            case Doctrine_Record::STATE_CLEAN:
            case Doctrine_Record::STATE_DIRTY:
                $this->beginTransaction();

                $this->deleteComposites($record);
                $this->addDelete($record);

                $this->commit();
                return true;
            break;
            default:
                return false;
        endswitch;    
    }
    /**
     * adds data access object into pending insert list
     * @param Doctrine_Record $record
     */
    public function addInsert(Doctrine_Record $record) {
        $name = $record->getTable()->getComponentName();
        $this->insert[$name][] = $record;
    }
    /**
     * adds data access object into penging update list
     * @param Doctrine_Record $record
     */
    public function addUpdate(Doctrine_Record $record) {
        $name = $record->getTable()->getComponentName();
        $this->update[$name][] = $record;
    }
    /**
     * adds data access object into pending delete list
     * @param Doctrine_Record $record
     */
    public function addDelete(Doctrine_Record $record) {
        $name = $record->getTable()->getComponentName();
        $this->delete[$name][] = $record;
    }
    /**
     * returns a string representation of this object
     * @return string
     */
    public function __toString() {
        return Doctrine_Lib::getSessionAsString($this);
    }
}
?>
