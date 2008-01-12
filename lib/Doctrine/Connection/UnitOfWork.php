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
 * Doctrine_Connection_UnitOfWork
 *
 * @package     Doctrine
 * @subpackage  Connection
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Connection_UnitOfWork extends Doctrine_Connection_Module
{
    protected $_autoflush = true;
    protected $_inserts = array();
    protected $_updates = array();
    protected $_deletes = array();
    
    public function flush()
    {
        return $this->saveAll();
    }
    
    public function addInsert()
    {
        
    }
    
    public function addUpdate()
    {
        
    }
    
    public function addDelete()
    {
        
    }
    
    
    /**
     * buildFlushTree
     * builds a flush tree that is used in transactions
     *
     * The returned array has all the initialized components in
     * 'correct' order. Basically this means that the records of those
     * components can be saved safely in the order specified by the returned array.
     *
     * @param array $tables     an array of Doctrine_Table objects or component names
     * @return array            an array of component names in flushing order
     */
    public function buildFlushTree(array $tables)
    {
        $tree = array();
        foreach ($tables as $k => $table) {
            if ( ! ($table instanceof Doctrine_Mapper_Abstract)) {
                $table = $this->conn->getMapper($table);
            }
            $nm     = $table->getComponentName();

            $index  = array_search($nm, $tree);

            if ($index === false) {
                $tree[] = $nm;
                $index  = max(array_keys($tree));
            }

            $rels = $table->getTable()->getRelations();

            // group relations

            foreach ($rels as $key => $rel) {
                if ($rel instanceof Doctrine_Relation_ForeignKey) {
                    unset($rels[$key]);
                    array_unshift($rels, $rel);
                }
            }

            foreach ($rels as $rel) {
                $name   = $rel->getTable()->getComponentName();
                $index2 = array_search($name,$tree);
                $type   = $rel->getType();

                // skip self-referenced relations
                if ($name === $nm) {
                    continue;
                }

                if ($rel instanceof Doctrine_Relation_ForeignKey) {
                    if ($index2 !== false) {
                        if ($index2 >= $index)
                            continue;

                        unset($tree[$index]);
                        array_splice($tree,$index2,0,$nm);
                        $index = $index2;
                    } else {
                        $tree[] = $name;
                    }

                } else if ($rel instanceof Doctrine_Relation_LocalKey) {
                    if ($index2 !== false) {
                        if ($index2 <= $index)
                            continue;

                        unset($tree[$index2]);
                        array_splice($tree,$index,0,$name);
                    } else {
                        array_unshift($tree,$name);
                        $index++;
                    }
                } else if ($rel instanceof Doctrine_Relation_Association) {
                    $t = $rel->getAssociationFactory();
                    $n = $t->getComponentName();

                    if ($index2 !== false)
                        unset($tree[$index2]);

                    array_splice($tree, $index, 0, $name);
                    $index++;

                    $index3 = array_search($n, $tree);

                    if ($index3 !== false) {
                        if ($index3 >= $index)
                            continue;

                        unset($tree[$index]);
                        array_splice($tree, $index3, 0, $n);
                        $index = $index2;
                    } else {
                        $tree[] = $n;
                    }
                }
            }
        }
        return array_values($tree);
    }

    /**
     * saves the given record
     *
     * @param Doctrine_Record $record
     * @return void
     */
    /*public function saveGraph(Doctrine_Record $record)
    {
        $conn = $this->getConnection();

        $state = $record->state();
        if ($state === Doctrine_Record::STATE_LOCKED) {
            return false;
        }

        $record->state(Doctrine_Record::STATE_LOCKED);
        
        $conn->beginInternalTransaction();
        $saveLater = $this->saveRelated($record);

        $record->state($state);

        if ($record->isValid()) {
            $event = new Doctrine_Event($record, Doctrine_Event::RECORD_SAVE);

            $record->preSave($event);

            $record->getTable()->getRecordListener()->preSave($event);
            $state = $record->state();

            if ( ! $event->skipOperation) {
                switch ($state) {
                    case Doctrine_Record::STATE_TDIRTY:
                        $this->insert($record);
                        break;
                    case Doctrine_Record::STATE_DIRTY:
                    case Doctrine_Record::STATE_PROXY:
                        $this->update($record);
                        break;
                    case Doctrine_Record::STATE_CLEAN:
                    case Doctrine_Record::STATE_TCLEAN:

                        break;
                }
            }

            $record->getTable()->getRecordListener()->postSave($event);
             
            $record->postSave($event);
        } else {
            $conn->transaction->addInvalid($record);
        }

        $state = $record->state();

        $record->state(Doctrine_Record::STATE_LOCKED);

        foreach ($saveLater as $fk) {
            $alias = $fk->getAlias();

            if ($record->hasReference($alias)) {
                $obj = $record->$alias;
            
                // check that the related object is not an instance of Doctrine_Null
                if ( ! ($obj instanceof Doctrine_Null)) {
                    $obj->save($conn);
                }
            }
        }

        // save the MANY-TO-MANY associations
        $this->saveAssociations($record);

        $record->state($state);
        
        $conn->commit();

        return true;
    }*/
    
    /**
     * saves the given record
     *
     * @param Doctrine_Record $record
     * @return void
     */
    /*public function save(Doctrine_Record $record)
    {
        $event = new Doctrine_Event($record, Doctrine_Event::RECORD_SAVE);

        $record->preSave($event);

        $record->getTable()->getRecordListener()->preSave($event);

        if ( ! $event->skipOperation) {
            switch ($record->state()) {
                case Doctrine_Record::STATE_TDIRTY:
                    $this->insert($record);
                    break;
                case Doctrine_Record::STATE_DIRTY:
                case Doctrine_Record::STATE_PROXY:
                    $this->update($record);
                    break;
                case Doctrine_Record::STATE_CLEAN:
                case Doctrine_Record::STATE_TCLEAN:
                    // do nothing
                    break;
            }
        }

        $record->getTable()->getRecordListener()->postSave($event);
        
        $record->postSave($event);
    }*/

    /**
     * deletes given record and all the related composites
     * this operation is isolated by a transaction
     *
     * this event can be listened by the onPreDelete and onDelete listeners
     *
     * @return boolean      true on success, false on failure
     * @todo Move to Doctrine_Table (which will become Doctrine_Mapper).
     */
    /*public function delete(Doctrine_Record $record)
    {
        if ( ! $this->_autoflush) {
            return true;
        }
        if ( ! $record->exists()) {
            return false;
        }
        $this->conn->beginInternalTransaction();

        $event = new Doctrine_Event($record, Doctrine_Event::RECORD_DELETE);

        $record->preDelete($event);
        
        $table = $record->getTable();

        $table->getRecordListener()->preDelete($event);

        $state = $record->state();

        $record->state(Doctrine_Record::STATE_LOCKED);

        $this->deleteComposites($record);

        if ( ! $event->skipOperation) {
            $record->state(Doctrine_Record::STATE_TDIRTY);
            
            if ($table->getInheritanceType() == Doctrine::INHERITANCETYPE_JOINED) {
                foreach ($table->getOption('joinedParents') as $parent) {
                    $parentTable = $table->getConnection()->getTable($parent);
                    $this->conn->delete($parentTable, $record->identifier());
                }
            }
            
            $this->conn->delete($table, $record->identifier());
            $record->state(Doctrine_Record::STATE_TCLEAN);
        } else {
            // return to original state   
            $record->state($state);
        }

        $table->getRecordListener()->postDelete($event);

        $record->postDelete($event);
        
        $record->getMapper()->removeRecord($record);

        $this->conn->commit();

        return true;
    }*/
    
    /**
     * @todo Description. See also the todo for deleteMultiple().
     */
    /*public function deleteRecord(Doctrine_Record $record)
    {
        $ids = $record->identifier();
        $tmp = array();
        
        foreach (array_keys($ids) as $id) {
            $tmp[] = $id . ' = ? ';
        }
        
        $params = array_values($ids);

        $query = 'DELETE FROM '
               . $this->conn->quoteIdentifier($record->getTable()->getTableName())
               . ' WHERE ' . implode(' AND ', $tmp);


        return $this->conn->exec($query, $params);
    }*/

    /**
     * DOESNT SEEM TO BE USED ANYWHERE. 
     *
     * deleteMultiple
     * deletes all records from the pending delete list
     *
     * @return void
     * @todo Refactor. Maybe move to the Connection class? Sometimes UnitOfWork constructs
     *       queries itself and sometimes it leaves the sql construction to Connection.
     *       This should be changed.
     */
    /*public function deleteMultiple(array $records)
    {        
        foreach ($this->delete as $name => $deletes) {
            $record = false;
            $ids = array();
            
            // Note: Why is the last element's table identifier checked here and then 
            // the table object from $deletes[0] used???
            if (is_array($deletes[count($deletes)-1]->getTable()->getIdentifier()) &&
                    count($deletes) > 0) {
                $table = $deletes[0]->getTable();
                $query = 'DELETE FROM '
                       . $this->conn->quoteIdentifier($table->getTableName())
                       . ' WHERE ';

                $params = array();
                $cond = array();
                foreach ($deletes as $k => $record) {
                    $ids = $record->identifier();
                    $tmp = array();
                    foreach (array_keys($ids) as $id) {
                        $tmp[] = $table->getColumnName($id) . ' = ? ';
                    }
                    $params = array_merge($params, array_values($ids));
                    $cond[] = '(' . implode(' AND ', $tmp) . ')';
                }
                $query .= implode(' OR ', $cond);

                $this->conn->execute($query, $params);
            } else {
                foreach ($deletes as $k => $record) {
                    $ids[] = $record->getIncremented();
                }
                // looks pretty messy. $record should be already out of scope. ugly php behaviour.
                // even the php manual agrees on that and recommends to unset() the last element
                // immediately after the loop ends.
                $table = $record->getTable();
                if ($record instanceof Doctrine_Record) {
                    $params = substr(str_repeat('?, ', count($ids)), 0, -2);
    
                    $query = 'DELETE FROM '
                           . $this->conn->quoteIdentifier($record->getTable()->getTableName())
                           . ' WHERE '
                           . $table->getColumnName($table->getIdentifier())
                           . ' IN(' . $params . ')';
        
                    $this->conn->execute($query, $ids);
                }
            }
        }
    }*/

    /**
     * saveRelated
     * saves all related records to $record
     *
     * @throws PDOException         if something went wrong at database level
     * @param Doctrine_Record $record
     */
    /*public function saveRelated(Doctrine_Record $record)
    {
        $saveLater = array();
        foreach ($record->getReferences() as $k => $v) {
            $rel = $record->getTable()->getRelation($k);

            $local = $rel->getLocal();
            $foreign = $rel->getForeign();

            if ($rel instanceof Doctrine_Relation_ForeignKey) {
                $saveLater[$k] = $rel;
            } else if ($rel instanceof Doctrine_Relation_LocalKey) {
                // ONE-TO-ONE relationship
                $obj = $record->get($rel->getAlias());

                // Protection against infinite function recursion before attempting to save
                if ($obj instanceof Doctrine_Record && $obj->isModified()) {
                    $obj->save($this->conn);
                }
            }
        }

        return $saveLater;
    }*/

    /**
     * saveAssociations
     *
     * this method takes a diff of one-to-many / many-to-many original and
     * current collections and applies the changes
     *
     * for example if original many-to-many related collection has records with
     * primary keys 1,2 and 3 and the new collection has records with primary keys
     * 3, 4 and 5, this method would first destroy the associations to 1 and 2 and then
     * save new associations to 4 and 5
     *
     * @throws Doctrine_Connection_Exception         if something went wrong at database level
     * @param Doctrine_Record $record
     * @return void
     */
    /*public function saveAssociations(Doctrine_Record $record)
    {
        foreach ($record->getReferences() as $k => $v) {
            $rel = $record->getTable()->getRelation($k);
            
            if ($rel instanceof Doctrine_Relation_Association) {   
                $v->save($this->conn);

                $assocTable = $rel->getAssociationTable();
                foreach ($v->getDeleteDiff() as $r) {
                    $query = 'DELETE FROM ' . $assocTable->getTableName()
                           . ' WHERE ' . $rel->getForeign() . ' = ?'
                           . ' AND ' . $rel->getLocal() . ' = ?';

                    $this->conn->execute($query, array($r->getIncremented(), $record->getIncremented()));
                }

                foreach ($v->getInsertDiff() as $r) {
                    $assocRecord = $assocTable->create();
                    $assocRecord->set($assocTable->getFieldName($rel->getForeign()), $r);
                    $assocRecord->set($assocTable->getFieldName($rel->getLocal()), $record);

                    $this->saveGraph($assocRecord);
                }
            }
        }
    }*/

    /**
     * deletes all related composites
     * this method is always called internally when a record is deleted
     *
     * @throws PDOException         if something went wrong at database level
     * @return void
     */
    /*public function deleteComposites(Doctrine_Record $record)
    {
        foreach ($record->getTable()->getRelations() as $fk) {
            if ($fk->isComposite()) {
                $obj = $record->get($fk->getAlias());
                if ($obj instanceof Doctrine_Record && 
                        $obj->state() != Doctrine_Record::STATE_LOCKED)  {
                    $obj->delete($this->conn);
                }
            }
        }
    }*/

    /**
     * saveAll
     * persists all the pending records from all tables
     *
     * @throws PDOException         if something went wrong at database level
     * @return void
     */
    /*public function saveAll()
    {
        // get the flush tree
        $tree = $this->buildFlushTree($this->conn->getTables());

        // save all records
        foreach ($tree as $name) {
            $table = $this->conn->getTable($name);

            foreach ($table->getRepository() as $record) {
                $table->save($record);
            }
        }

        // save all associations
        foreach ($tree as $name) {
            $table = $this->conn->getTable($name);

            foreach ($table->getRepository() as $record) {
                $table->saveAssociations($record);
            }
        }
    }*/
    
    /**
     * saveAll
     * persists all the pending records from all tables
     *
     * @throws PDOException         if something went wrong at database level
     * @return void
     */
    public function saveAll()
    {
        //echo "<br /><br />flushin all.<br /><br />";
        // get the flush tree
        $tree = $this->buildFlushTree($this->conn->getMappers());

        // save all records
        foreach ($tree as $name) {
            $mapper = $this->conn->getMapper($name);
            foreach ($mapper->getRepository() as $record) {
                //echo $record->getOid() . "<br />";
                $mapper->saveSingleRecord($record);
            }
        }

        // save all associations
        foreach ($tree as $name) {
            $mapper = $this->conn->getMapper($name);
            foreach ($mapper->getRepository() as $record) {
                $mapper->saveAssociations($record);
            }
        }
    }

    /**
     * updates given record
     *
     * @param Doctrine_Record $record   record to be updated
     * @return boolean                  whether or not the update was successful
     * @todo Move to Doctrine_Table (which will become Doctrine_Mapper).
     */
    /*public function update(Doctrine_Record $record)
    {
        if ( ! $this->_autoflush) {
            return true;
        }
        
        $event = new Doctrine_Event($record, Doctrine_Event::RECORD_UPDATE);

        $record->preUpdate($event);

        $table = $record->getTable();

        $table->getRecordListener()->preUpdate($event);

        if ( ! $event->skipOperation) {
            $identifier = $record->identifier();

            if ($table->getInheritanceType() == Doctrine::INHERITANCETYPE_JOINED
                    && count($table->getOption('joinedParents')) > 0) {
                $dataSet = $this->formatDataSet($record);
                
                $component = $table->getComponentName();

                $classes = $table->getOption('joinedParents');
                $classes[] = $component;

                foreach ($record as $field => $value) {
                    if ($value instanceof Doctrine_Record) {
                        if ( ! $value->exists()) {
                            $value->save();
                        }
                        $record->set($field, $value->getIncremented());
                    }
                }

                foreach ($classes as $class) {
                    $parentTable = $this->conn->getTable($class);
                    $this->conn->update($parentTable, $dataSet[$class], $identifier);
                }
            } else {
                $array = $record->getPrepared();
                
                $this->conn->update($table, $array, $identifier);
            }
            $record->assignIdentifier(true);
        }
        
        $table->getRecordListener()->postUpdate($event);

        $record->postUpdate($event);

        return true;
    }*/
    
    /**
     * inserts a record into database
     *
     * @param Doctrine_Record $record   record to be inserted
     * @return boolean
     * @todo Move to Doctrine_Table (which will become Doctrine_Mapper).
     */
    /*public function insert(Doctrine_Record $record)
    {
        if ( ! $this->_autoflush) {
            return true;
        }
        
        // listen the onPreInsert event
        $event = new Doctrine_Event($record, Doctrine_Event::RECORD_INSERT);

        $record->preInsert($event);
        
        $table = $record->getTable();

        $table->getRecordListener()->preInsert($event);

        if ( ! $event->skipOperation) {
            if ($table->getInheritanceType() == Doctrine::INHERITANCETYPE_JOINED &&
                    count($table->getOption('joinedParents')) > 0) {
                $dataSet = $this->formatDataSet($record);
                
                $component = $table->getComponentName();

                $classes = $table->getOption('joinedParents');
                $classes[] = $component;

                foreach ($classes as $k => $parent) {
                    if ($k === 0) {
                        $rootRecord = new $parent();

                        $rootRecord->merge($dataSet[$parent]);

                        $this->processSingleInsert($rootRecord);

                        $record->assignIdentifier($rootRecord->identifier());
                    } else {
                        foreach ((array) $rootRecord->identifier() as $id => $value) {
                            $dataSet[$parent][$id] = $value;
                        }

                        $this->conn->insert($this->conn->getTable($parent), $dataSet[$parent]);
                    }
                }
            } else {
                $this->processSingleInsert($record);
            }
        }

        $table->addRecord($record);

        $table->getRecordListener()->postInsert($event);

        $record->postInsert($event);

        return true;
    }*/
    
    /**
     * @todo DESCRIBE WHAT THIS METHOD DOES, PLEASE!
     */
    /*public function formatDataSet(Doctrine_Record $record)
    {
    	$table = $record->getTable();

        $dataSet = array();
    
        $component = $table->getComponentName();
    
        $array = $record->getPrepared();
    
        foreach ($table->getColumns() as $columnName => $definition) {
            $fieldName = $table->getFieldName($columnName);
            if (isset($definition['primary']) && $definition['primary']) {
                continue;
            }
    
            if (isset($definition['owner'])) {
                $dataSet[$definition['owner']][$fieldName] = $array[$fieldName];
            } else {
                $dataSet[$component][$fieldName] = $array[$fieldName];
            }
        }    
        
        return $dataSet;
    }*/
    
}
