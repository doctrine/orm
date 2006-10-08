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
 * Doctrine_Connection
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
abstract class Doctrine_Connection extends Doctrine_Configurable implements Countable, IteratorAggregate {
    /**
     * @var $dbh                                the database handler
     */
    private $dbh;
    /**
     * @var Doctrine_Transaction $transaction   the transaction object
     */
    private $transaction;
    /**
     * @var Doctrine_UnitOfWork $unitOfWork     the unit of work object
     */
    private $unitOfWork;
    /**
     * @var array $tables                       an array containing all the initialized Doctrine_Table objects
     *                                          keys representing Doctrine_Table component names and values as Doctrine_Table objects
     */
    protected $tables           = array();
    /**
     * @var Doctrine_DataDict $dataDict
     */
    private $dataDict;
    /**
     * the constructor
     *
     * @param Doctrine_Manager $manager     the manager object
     * @param PDO $pdo                      the database handler
     */
    public function __construct(Doctrine_Manager $manager,PDO $pdo) {
        $this->dbh   = $pdo;

        $this->transaction  = new Doctrine_Connection_Transaction($this);
        $this->unitOfWork   = new Doctrine_Connection_UnitOfWork($this);

        $this->setParent($manager);

        $this->dbh->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);   

        $this->getAttribute(Doctrine::ATTR_LISTENER)->onOpen($this);
    }
    /**
     * getUnitOfWork
     *
     * returns the unit of work object
     *
     * @return Doctrine_UnitOfWork
     */
    public function getUnitOfWork() {
        return $this->unitOfWork;
    }
    /**
     * getTransaction
     *
     * returns the current transaction object
     *
     * @return Doctrine_Transaction
     */
    public function getTransaction() {
        return $this->transaction;
    }
    /**
     * returns the manager that created this connection
     *
     * @return Doctrine_Manager
     */
    public function getManager() {
        return $this->getParent();
    }
    /**
     * returns the database handler of which this connection uses
     *
     * @return PDO              the database handler
     */
    public function getDBH() {
        return $this->dbh;
    }
    /**
     * returns a datadict object
     *
     * @return Doctrine_DataDict
     */
    public function getDataDict() {
        if(isset($this->dataDict))
            return $this->dataDict;

        $driver = $this->dbh->getAttribute(PDO::ATTR_DRIVER_NAME);
        switch($driver) {
            case "mysql":
                $this->dataDict = new Doctrine_DataDict_Mysql($this);
            break;
            case "sqlite":
            case "sqlite2":
                $this->dataDict = new Doctrine_DataDict_Sqlite($this);
            break;
            case "pgsql":
                $this->dataDict = new Doctrine_DataDict_Pgsql($this);
            break;
            case "oci":
            case "oci8":
                $this->dataDict = new Doctrine_DataDict_Oracle($this);
            break;
            case "mssql":
                $this->dataDict = new Doctrine_DataDict_Mssql($this);
            break;
            default:
                throw new Doctrine_Connection_Exception("No datadict driver availible for ".$driver);
        }
        return $this->dataDict;
    }
    /**
     * getRegexpOperator
     * returns the regular expression operator 
     * (implemented by the connection drivers)
     *
     * @return string
     */
    public function getRegexpOperator() {
        throw new Doctrine_Connection_Exception('Regular expression operator is not supported by this database driver.');                                    	
    }
    /**
     * query
     * queries the database with Doctrine Query Language
     *
     * @param string $query         DQL query
     * @param array $params         query parameters
     */
    final public function query($query,array $params = array()) {
        $parser = new Doctrine_Query($this);

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
     * @param string $query     sql query
     * @param array $params     query parameters
     *
     * @return PDOStatement
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
     * whether or not this connection has table $name initialized
     *
     * @param $mixed $name
     * @return boolean
     */
    public function hasTable($name) {
        return isset($this->tables[$name]);
    }
    /**
     * returns a table object for given component name
     *
     * @param string $name              component name
     * @return object Doctrine_Table
     */
    public function getTable($name) {
        if(isset($this->tables[$name]))
            return $this->tables[$name];

        $class = $name."Table";

        if(class_exists($class) && in_array("Doctrine_Table", class_parents($class))) {
            return new $class($name, $this);
        } else {

            return new Doctrine_Table($name, $this);
        }
    }
    /**
     * returns an array of all initialized tables
     *
     * @return array
     */
    public function getTables() {
        return $this->tables;
    }
    /**
     * returns an iterator that iterators through all 
     * initialized table objects
     *
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->tables);
    }
    /**
     * returns the count of initialized table objects
     *
     * @return integer
     */
    public function count() {
        return count($this->tables);
    }
    /**
     * addTable
     * adds a Doctrine_Table object into connection registry
     *
     * @param $objTable             a Doctrine_Table object to be added into registry
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
     * create
     * creates a record
     *
     * create                       creates a record
     * @param string $name          component name
     * @return Doctrine_Record      Doctrine_Record object
     */
    public function create($name) {
        return $this->getTable($name)->create();
    }
    /**
     * flush                        
     * saves all the records from all tables
     * this operation is isolated using a transaction
     *
     * @return void
     */
    public function flush() {
        $this->beginTransaction();
        $this->saveAll();
        $this->commit();
    }
    /**
     * saveAll                      
     * persists all the records from all tables
     *
     * @return void
     */
    private function saveAll() {
        $tree = $this->unitOfWork->buildFlushTree($this->tables);

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
     * clear
     * clears all repositories
     *
     * @return void
     */
    public function clear() {
        foreach($this->tables as $k => $table) {
            $table->getRepository()->evictAll();
            $table->clear();
        }
    }
    /**
     * evictTables
     * evicts all tables
     *
     * @return void
     */
    public function evictTables() {
        $this->tables = array();
    }
    /**
     * close
     * closes the connection
     *
     * @return void
     */
    public function close() {
        $this->getAttribute(Doctrine::ATTR_LISTENER)->onPreClose($this);

        $this->clear();
        $this->state = Doctrine_Connection::STATE_CLOSED;
        
        $this->getAttribute(Doctrine::ATTR_LISTENER)->onClose($this);
    }
    /**
     * get the current transaction nesting level
     *
     * @return integer
     */
    public function getTransactionLevel() {
        return $this->transaction->getTransactionLevel();
    }
    /**
     * beginTransaction
     * starts a new transaction
     * @return void
     */
    public function beginTransaction() {
        $this->transaction->beginTransaction();
    }
    /**
     * commits the current transaction
     * if lockmode is optimistic this method starts a transaction
     * and commits it instantly
     *
     * @return void
     */
    public function commit() {
        $this->transaction->commit();
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
        $this->transaction->rollback();
    }
    /**
     * returns maximum identifier values
     *
     * @param array $names          an array of component names
     * @return array
     */   
    public function getMaximumValues(array $names) {
        $values = array();
        foreach($names as $name) {
            $table     = $this->tables[$name];
            $keys      = $table->getPrimaryKeys();
            $tablename = $table->getTableName();

            if(count($keys) == 1 && $keys[0] == $table->getIdentifier()) {
                // record uses auto_increment column

                $sql    = "SELECT MAX(".$table->getIdentifier().") FROM ".$tablename;
                $stmt   = $this->dbh->query($sql);
                $data   = $stmt->fetch(PDO::FETCH_NUM);
                $values[$tablename] = $data[0];

                $stmt->closeCursor();
            }
        }
        return $values;
    }
    /**
     * saves a collection
     *
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
     * deletes all records from collection
     *
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
     * saves the given record
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function save(Doctrine_Record $record) {
        $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onPreSave($record);


        switch($record->getState()):
            case Doctrine_Record::STATE_TDIRTY:
                $this->transaction->insert($record);
            break;
            case Doctrine_Record::STATE_DIRTY:
            case Doctrine_Record::STATE_PROXY:
                $this->transaction->update($record);
            break;
            case Doctrine_Record::STATE_CLEAN:
            case Doctrine_Record::STATE_TCLEAN:
                // do nothing
            break;
        endswitch;

        $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onSave($record);
    }
    /**
     * saves all related records to $record
     *
     * @param Doctrine_Record $record
     */
    public function saveRelated(Doctrine_Record $record) {
        $saveLater = array();
        foreach($record->getReferences() as $k=>$v) {
            $fk = $record->getTable()->getRelation($k);
            if($fk instanceof Doctrine_Relation_ForeignKey ||
               $fk instanceof Doctrine_Relation_LocalKey) {
                if($fk->isComposite()) {
                    $local = $fk->getLocal();
                    $foreign = $fk->getForeign();

                    if($record->getTable()->hasPrimaryKey($fk->getLocal())) {
                        if( ! $record->exists())
                            $saveLater[$k] = $fk;
                        else
                            $v->save();
                    } else {
                        // ONE-TO-ONE relationship
                        $obj = $record->get($fk->getTable()->getComponentName());

                        if($obj->getState() != Doctrine_Record::STATE_TCLEAN)
                            $obj->save();

                    }
                }
            } elseif($fk instanceof Doctrine_Relation_Association) {
                $v->save();
            }
        }
        return $saveLater;
    }
    /**
     * deletes all related composites
     * this method is always called internally when a record is deleted
     *
     * @return void
     */
    final public function deleteComposites(Doctrine_Record $record) {
        foreach($record->getTable()->getRelations() as $fk) {
            switch($fk->getType()):
                case Doctrine_Relation::ONE_COMPOSITE:
                case Doctrine_Relation::MANY_COMPOSITE:
                    $obj = $record->get($fk->getAlias());
                    $obj->delete();
                break;
            endswitch;
        }
    }
    /**
     * saveAssociations
     * save the associations of many-to-many relations
     * this method also deletes associations that do not exist anymore
     * @return void
     */
    final public function saveAssociations(Doctrine_Record $record) {
        foreach($record->getTable()->table->getRelations() as $rel):
            $table   = $rel->getTable();
            $name    = $table->getComponentName();
            $alias   = $this->table->getAlias($name);

            if($rel instanceof Doctrine_Relation_Association) {
                switch($rel->getType()):
                    case Doctrine_Relation::MANY_COMPOSITE:
                    break;
                    case Doctrine_Relation::MANY_AGGREGATE:
                        $asf     = $rel->getAssociationFactory();

                        if($record->hasReference($alias)) {

                            $new = $record->getReference($alias);

                            if( ! $this->hasOriginalsFor($alias)) {
                                $record->loadReference($alias);
                            }

                            $operations = Doctrine_Relation::getDeleteOperations($this->originals[$alias],$new);

                            foreach($operations as $r) {
                                $query = "DELETE FROM ".$asf->getTableName()." WHERE ".$fk->getForeign()." = ?"
                                                                            ." AND ".$fk->getLocal()." = ?";
                                $this->table->getConnection()->execute($query, array($r->getIncremented(),$record->getIncremented()));
                            }

                            $operations = Doctrine_Relation::getInsertOperations($this->originals[$alias],$new);
                            foreach($operations as $r) {
                                $reldao = $asf->create();
                                $reldao->set($fk->getForeign(),$r);
                                $reldao->set($fk->getLocal(),$this);
                                $reldao->save();

                            }
                            $this->originals[$alias] = clone $this->references[$alias];
                        }
                    break;
                endswitch;
            } elseif($fk instanceof Doctrine_Relation_ForeignKey ||
                     $fk instanceof Doctrine_Relation_LocalKey) {

                switch($fk->getType()):
                    case Doctrine_Relation::ONE_COMPOSITE:
                        if(isset($this->originals[$alias]) && $this->originals[$alias]->obtainIdentifier() != $this->references[$alias]->obtainIdentifier())
                            $this->originals[$alias]->delete();

                    break;
                    case Doctrine_Relation::MANY_COMPOSITE:
                        if(isset($this->references[$alias])) {
                            $new = $this->references[$alias];

                            if( ! isset($this->originals[$alias]))
                                $record->loadReference($alias);

                            $operations = Doctrine_Relation::getDeleteOperations($this->originals[$alias], $new);

                            foreach($operations as $r) {
                                $r->delete();
                            }

                            $record->assignOriginals($alias, clone $this->references[$alias]);
                        }
                    break;
                endswitch;
            }
        endforeach;
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
        if( ! $record->exists())
            return false;

        $this->beginTransaction();

        $record->getTable()->getListener()->onPreDelete($record);

        $this->deleteComposites($record);

        $this->transaction->addDelete($record);

        $record->getTable()->getListener()->onDelete($record);

        $this->commit();
        return true;
    }
    /**
     * returns a string representation of this object
     * @return string
     */
    public function __toString() {
        return Doctrine_Lib::getConnectionAsString($this);
    }
}

