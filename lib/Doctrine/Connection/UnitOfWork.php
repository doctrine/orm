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
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Connection_UnitOfWork extends Doctrine_Connection_Module implements IteratorAggregate, Countable
{
    /**
     * buildFlushTree
     * builds a flush tree that is used in transactions
     *
     * The returned array has all the initialized components in
     * 'correct' order. Basically this means that the records of those
     * components can be saved safely in the order specified by the returned array.
     *
     * @param array $tables
     * @return array
     */
    public function buildFlushTree(array $tables)
    {
        $tree = array();
        foreach ($tables as $k => $table) {
            $k = $k.$table;
            if ( ! ($table instanceof Doctrine_Table)) {
                $table = $this->conn->getTable($table);
            }
            $nm     = $table->getComponentName();

            $index  = array_search($nm,$tree);

            if ($index === false) {
                $tree[] = $nm;
                $index  = max(array_keys($tree));
            }

            $rels = $table->getRelations();

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
                if ($name === $nm)
                    continue;

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

                } elseif ($rel instanceof Doctrine_Relation_LocalKey) {
                    if ($index2 !== false) {
                        if ($index2 <= $index)
                            continue;

                        unset($tree[$index2]);
                        array_splice($tree,$index,0,$name);
                    } else {
                        array_unshift($tree,$name);
                        $index++;
                    }
                } elseif ($rel instanceof Doctrine_Relation_Association) {
                    $t = $rel->getAssociationFactory();
                    $n = $t->getComponentName();

                    if ($index2 !== false)
                        unset($tree[$index2]);

                    array_splice($tree,$index, 0,$name);
                    $index++;

                    $index3 = array_search($n,$tree);

                    if ($index3 !== false) {
                        if ($index3 >= $index)
                            continue;

                        unset($tree[$index]);
                        array_splice($tree,$index3,0,$n);
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
     * saveRelated
     * saves all related records to $record
     *
     * @throws PDOException         if something went wrong at database level
     * @param Doctrine_Record $record
     */
    public function saveRelated(Doctrine_Record $record)
    {
        $saveLater = array();
        foreach ($record->getReferences() as $k=>$v) {
            $fk = $record->getTable()->getRelation($k);
            if ($fk instanceof Doctrine_Relation_ForeignKey ||
               $fk instanceof Doctrine_Relation_LocalKey) {
                $local = $fk->getLocal();
                $foreign = $fk->getForeign();

                if ($record->getTable()->hasPrimaryKey($fk->getLocal())) {
                    if ( ! $record->exists()) {
                        $saveLater[$k] = $fk;
                    } else {
                        $v->save();
                    }
                } else {
                    // ONE-TO-ONE relationship
                    $obj = $record->get($fk->getAlias());

                    if ($obj->state() != Doctrine_Record::STATE_TCLEAN) {
                        $obj->save();
                    }
                }

            } elseif ($fk instanceof Doctrine_Relation_Association) {
                $v->save();
            }
        }
        return $saveLater;
    }
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
     * @throws PDOException         if something went wrong at database level
     * @param Doctrine_Record $record
     * @return void
     */
    public function saveAssociations(Doctrine_Record $record)
    {
        foreach ($record->getTable()->getRelations() as $rel) {
            $table   = $rel->getTable();
            $alias   = $rel->getAlias();

            $rel->processDiff($record);
        }
    }
    /**
     * deletes all related composites
     * this method is always called internally when a record is deleted
     *
     * @throws PDOException         if something went wrong at database level
     * @return void
     */
    public function deleteComposites(Doctrine_Record $record)
    {
        foreach ($record->getTable()->getRelations() as $fk) {
            switch ($fk->getType()) {
                case Doctrine_Relation::ONE_COMPOSITE:
                case Doctrine_Relation::MANY_COMPOSITE:
                    $obj = $record->get($fk->getAlias());
                    $obj->delete();
                    break;
            };
        }
    }
    /**
     * saveAll
     * persists all the pending records from all tables
     *
     * @throws PDOException         if something went wrong at database level
     * @return void
     */
    public function saveAll()
    {
        // get the flush tree
        $tree = $this->buildFlushTree($this->conn->getTables());

        // save all records
        foreach ($tree as $name) {
            $table = $this->conn->getTable($name);

            foreach ($table->getRepository() as $record) {
                $this->conn->save($record);
            }
        }

        // save all associations
        foreach ($tree as $name) {
            $table = $this->conn->getTable($name);

            foreach ($table->getRepository() as $record) {
                $this->saveAssociations($record);
            }
        }
    }
    /**
     * updates the given record
     *
     * @param Doctrine_Record $record
     * @return boolean
     */
    public function update(Doctrine_Record $record)
    {
        $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onPreUpdate($record);

        $array = $record->getPrepared();

        if (empty($array)) {
            return false;
        }
        $set   = array();
        foreach ($array as $name => $value) {
                $set[] = $name." = ?";

                if ($value instanceof Doctrine_Record) {
                    switch ($value->state()) {
                        case Doctrine_Record::STATE_TCLEAN:
                        case Doctrine_Record::STATE_TDIRTY:
                            $record->save();
                        default:
                            $array[$name] = $value->getIncremented();
                            $record->set($name, $value->getIncremented());
                    };
                }
        };

        $params   = array_values($array);
        $id       = $record->obtainIdentifier();

        if ( ! is_array($id)) {
            $id = array($id);
        }
        $id     = array_values($id);
        $params = array_merge($params, $id);

        $sql  = 'UPDATE ' . $record->getTable()->getTableName()
              . ' SET ' . implode(', ', $set)
              . ' WHERE ' . implode(' = ? AND ', $record->getTable()->getPrimaryKeys())
              . ' = ?';

        $stmt = $this->conn->getDBH()->prepare($sql);
        $stmt->execute($params);

        $record->assignIdentifier(true);

        $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onUpdate($record);

        return true;
    }
    /**
     * inserts a record into database
     *
     * @param Doctrine_Record $record   record to be inserted
     * @return boolean
     */
    public function insert(Doctrine_Record $record)
    {
         // listen the onPreInsert event
        $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onPreInsert($record);

        $array = $record->getPrepared();

        if (empty($array)) {
            return false;
        }
        $table     = $record->getTable();
        $keys      = $table->getPrimaryKeys();

        $seq       = $record->getTable()->getSequenceName();

        if ( ! empty($seq)) {
            $id             = $this->conn->sequence->nextId($seq);
            $name           = $record->getTable()->getIdentifier();
            $array[$name]   = $id;
        }

        $this->conn->insert($table->getTableName(), $array);

        if (count($keys) == 1 && $keys[0] == $table->getIdentifier()) {
            $id = $this->conn->getDBH()->lastInsertID();

            if ( ! $id)
                $id = $table->getMaxIdentifier();

            $record->assignIdentifier($id);
        } else {
            $record->assignIdentifier(true);
        }

        // listen the onInsert event
        $table->getAttribute(Doctrine::ATTR_LISTENER)->onInsert($record);

        return true;
    }
    public function getIterator()
    { }

    public function count()
    { }
}
