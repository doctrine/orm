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

#namespace Doctrine\ORM\Persisters;

/**
 * Base class for all EntityPersisters.
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 3406 $
 * @link        www.phpdoctrine.org
 * @since       2.0
 * @todo Rename to AbstractEntityPersister
 */
abstract class Doctrine_ORM_Persisters_AbstractEntityPersister
{
    /**
     * The names of all the fields that are available on entities. 
     */
    protected $_fieldNames = array();
    
    /**
     * Metadata object that descibes the mapping of the mapped entity class.
     *
     * @var Doctrine_ClassMetadata
     */
    protected $_classMetadata;
    
    /**
     * The name of the Entity the persister is used for.
     * 
     * @var string
     */
    protected $_entityName;

    /**
     * The Doctrine_Connection object that the database connection of this mapper.
     *
     * @var Doctrine::DBAL::Connection $conn
     */
    protected $_conn;
    
    /**
     * The EntityManager.
     *
     * @var Doctrine::ORM::EntityManager
     */
    protected $_em;
    
    /**
     * Null object.
     */
    private $_nullObject;

    /**
     * Constructs a new EntityPersister.
     */
    public function __construct(Doctrine_ORM_EntityManager $em, Doctrine_ORM_Mapping_ClassMetadata $classMetadata)
    {
        $this->_em = $em;
        $this->_entityName = $classMetadata->getClassName();
        $this->_conn = $em->getConnection();
        $this->_classMetadata = $classMetadata;
        $this->_nullObject = Doctrine_ORM_Internal_Null::$INSTANCE;
    }
    
    /**
     * Inserts an entity.
     *
     * @param Doctrine::ORM::Entity $entity The entity to insert.
     * @return void
     */
    public function insert($entity)
    {
        $insertData = array();
        $this->_prepareData($entity, $insertData, true);
        $this->_conn->insert($this->_classMetadata->getTableName(), $insertData);
    }
    
    /**
     * Updates an entity.
     *
     * @param Doctrine::ORM::Entity $entity The entity to update.
     * @return void
     */
    public function update(Doctrine_ORM_Entity $entity)
    {
        $dataChangeSet = $entity->_getDataChangeSet();
        $referenceChangeSet = $entity->_getReferenceChangeSet();
        
        foreach ($referenceChangeSet as $field => $change) {
            $assocMapping = $entity->getClass()->getAssociationMapping($field);
            if ($assocMapping instanceof Doctrine_Association_OneToOneMapping) {
                if ($assocMapping->isInverseSide()) {
                    continue; // ignore inverse side
                }
                // ... null out the foreign key
                
            }
            //...
        }
        
        //TODO: perform update
    }
    
    /**
     * Deletes an entity.
     *
     * @param Doctrine::ORM::Entity $entity The entity to delete.
     * @return void
     */
    public function delete(Doctrine_ORM_Entity $entity)
    {
        //TODO: perform delete
    }
    
    /**
     * Inserts a row into a table.
     *
     * @todo This method could be used to allow mapping to secondary table(s).
     * @see http://www.oracle.com/technology/products/ias/toplink/jpa/resources/toplink-jpa-annotations.html#SecondaryTable
     */
    protected function _insertRow($tableName, array $data)
    {
        $this->_conn->insert($tableName, $data);
    }
    
    /**
     * Deletes rows of a table.
     *
     * @todo This method could be used to allow mapping to secondary table(s).
     * @see http://www.oracle.com/technology/products/ias/toplink/jpa/resources/toplink-jpa-annotations.html#SecondaryTable
     */
    protected function _deleteRow($tableName, array $identifierToMatch)
    {
        $this->_conn->delete($tableName, $identifierToMatch);
    }
    
    /**
     * Deletes rows of a table.
     *
     * @todo This method could be used to allow mapping to secondary table(s).
     * @see http://www.oracle.com/technology/products/ias/toplink/jpa/resources/toplink-jpa-annotations.html#SecondaryTable
     */
    protected function _updateRow($tableName, array $data, array $identifierToMatch)
    {
        $this->_conn->update($tableName, $data, $identifierToMatch);
    }
    
    public function getClassMetadata()
    {
        return $this->_classMetadata;
    }
    
    /**
     * @todo Move to ClassMetadata?
     */
    public function getFieldNames()
    {
        if ($this->_fieldNames) {
            return $this->_fieldNames;
        }
        $this->_fieldNames = $this->_classMetadata->getFieldNames();
        return $this->_fieldNames;
    }

    /**
     * Gets the name of the class in the entity hierarchy that owns the field with
     * the given name. The owning class is the one that defines the field.
     *
     * @param string $fieldName
     * @return string
     * @todo Consider using 'inherited' => 'ClassName' to make the lookup simpler.
     */
    public function getOwningClass($fieldName)
    {
        if ($this->_classMetadata->isInheritanceTypeNone()) {
            return $this->_classMetadata;
        } else {
            foreach ($this->_classMetadata->getParentClasses() as $parentClass) {
                $parentClassMetadata = Doctrine_ORM_Mapping_ClassMetadataFactory::getInstance()
                        ->getMetadataFor($parentClass);
                if ( ! $parentClassMetadata->isInheritedField($fieldName)) {
                    return $parentClassMetadata;
                }
            }
        }
        throw new Doctrine_Exception("Unable to find defining class of field '$fieldName'.");
    }
    
    /**
     * Callback that is invoked during the SQL construction process.
     * @todo Move to ClassMetadata?
     */
    public function getCustomJoins()
    {
        return array();
    }
    
    /**
     * Callback that is invoked during the SQL construction process.
     * @todo Move to ClassMetadata?
     */
    public function getCustomFields()
    {
        return array();
    }
    
    /**
     * Assumes that the keys of the given field array are field names and converts
     * them to column names.
     *
     * @return array
     */
    /*protected function _convertFieldToColumnNames(array $fields, Doctrine_ClassMetadata $class)
    {
        $converted = array();
        foreach ($fields as $fieldName => $value) {
            $converted[$class->getColumnName($fieldName)] = $value;
        }
        
        return $converted;
    }*/
    
    /**
     * Returns an array of modified fields and values with data preparation
     * adds column aggregation inheritance and converts Records into primary key values
     *
     * @param array $array
     * @return void
     */
    protected function _prepareData($entity, array &$result, $isInsert = false)
    {
        foreach ($this->_em->getUnitOfWork()->getDataChangeSet($entity) as $field => $change) {
            list ($oldVal, $newVal) = each($change);
            $type = $this->_classMetadata->getTypeOfField($field);
            $columnName = $this->_classMetadata->getColumnName($field);

            if ($newVal === Doctrine_ORM_Internal_Null::$INSTANCE) {
                $result[$columnName] = null;
            } else if (is_object($newVal)) {
                $assocMapping = $this->_classMetadata->getAssociationMapping($field);
                if ( ! $assocMapping->isOneToOne() || $assocMapping->isInverseSide()) {
                    //echo "NOT TO-ONE OR INVERSE!";
                    continue;
                }

                foreach ($assocMapping->getSourceToTargetKeyColumns() as $sourceColumn => $targetColumn) {
                    //TODO: What if both join columns (local/foreign) are just db-only
                    // columns (no fields in models) ? Currently we assume the foreign column
                    // is mapped to a field in the foreign entity.
                    //TODO: throw exc if field not set
                    $otherClass = $this->_em->getClassMetadata($assocMapping->getTargetEntityName());
                    $result[$sourceColumn] = $otherClass->getReflectionProperty(
                            $otherClass->getFieldName($targetColumn))->getValue($newVal);
                }
            } else {
                switch ($type) {
                    case 'array':
                    case 'object':
                        $result[$columnName] = serialize($newVal);
                        break;
                    case 'gzip':
                        $result[$columnName] = gzcompress($newVal, 5);
                        break;
                    case 'boolean':
                        $result[$columnName] = $this->_em->getConnection()->convertBooleans($newVal);
                    break;
                    default:
                        $result[$columnName] = $newVal;
                }
            }
            /*$result[$columnName] = $type->convertToDatabaseValue(
                    $newVal, $this->_em->getConnection()->getDatabasePlatform());*/
        }
        
        // populates the discriminator column on insert in Single & Class Table Inheritance
        if ($isInsert && ($this->_classMetadata->isInheritanceTypeJoined() ||
                $this->_classMetadata->isInheritanceTypeSingleTable())) {
            $discColumn = $this->_classMetadata->getInheritanceOption('discriminatorColumn');
            $discMap = $this->_classMetadata->getInheritanceOption('discriminatorMap');
            $result[$discColumn] = array_search($this->_entityName, $discMap);
        }
    }

    
    
    #############################################################
   
    # The following is old code that needs to be removed/ported
    
    
    /**
     * deletes all related composites
     * this method is always called internally when a record is deleted
     *
     * @throws PDOException         if something went wrong at database level
     * @return void
     */
    protected function _deleteComposites(Doctrine_ORM_Entity $record)
    {
        $classMetadata = $this->_classMetadata;
        foreach ($classMetadata->getRelations() as $fk) {
            if ($fk->isComposite()) {
                $obj = $record->get($fk->getAlias());
                if ($obj instanceof Doctrine_ORM_Entity && 
                        $obj->_state() != Doctrine_ORM_Entity::STATE_LOCKED)  {
                    $obj->delete($this->_mapper->getConnection());
                }
            }
        }
    }

    /**
     * Returns the connection the mapper is currently using.
     *
     * @return Doctrine_Connection|null  The connection object.
     */
    public function getConnection()
    {
        return $this->_conn;
    }
    
    public function getEntityManager()
    {
        return $this->_em;
    }

    /**
     * getComponentName
     *
     * @return void
     * @deprecated Use getMappedClassName()
     */
    public function getComponentName()
    {
        return $this->_domainClassName;
    }
    
    /**
     * Saves an entity.
     *
     * @param Doctrine_Entity $record    The entity to save.
     * @param Doctrine_Connection $conn  The connection to use. Will default to the mapper's
     *                                   connection.
     */
    public function save(Doctrine_ORM_Entity $record)
    {
        if ( ! ($record instanceof $this->_domainClassName)) {
            throw new Doctrine_Mapper_Exception("Mapper of type " . $this->_domainClassName . " 
                    can't save instances of type" . get_class($record) . ".");
        }
        
        if ($conn === null) {
            $conn = $this->_conn;
        }

        $state = $record->_state();
        if ($state === Doctrine_ORM_Entity::STATE_LOCKED) {
            return false;
        }
        
        $record->_state(Doctrine_ORM_Entity::STATE_LOCKED);
        
        try {
            $conn->beginInternalTransaction();
            $saveLater = $this->_saveRelated($record);

            $record->_state($state);

            if ($record->isValid()) {
                $this->_insertOrUpdate($record);
            } else {
                $conn->getTransaction()->addInvalid($record);
            }

            $state = $record->_state();
            $record->_state(Doctrine_ORM_Entity::STATE_LOCKED);

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
            // reset state
            $record->_state($state);
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
        return true;
    }
    
    /**
     * Inserts or updates an entity, depending on it's state. 
     *
     * @param Doctrine_Entity $record  The entity to insert/update.
     */
    protected function _insertOrUpdate(Doctrine_ORM_Entity $record)
    {
        //$record->preSave();
        //$this->notifyEntityListeners($record, 'preSave', Doctrine_Event::RECORD_SAVE);
        
        switch ($record->_state()) {
            case Doctrine_ORM_Entity::STATE_TDIRTY:
                $this->_insert($record);
                break;
            case Doctrine_ORM_Entity::STATE_DIRTY:
            case Doctrine_ORM_Entity::STATE_PROXY:
                $this->_update($record);
                break;
            case Doctrine_ORM_Entity::STATE_CLEAN:
            case Doctrine_ORM_Entity::STATE_TCLEAN:
                // do nothing
                break;
        }
        
        //$record->postSave();
        //$this->notifyEntityListeners($record, 'postSave', Doctrine_Event::RECORD_SAVE);
    }
    
    /**
     * saves the given record
     *
     * @param Doctrine_Entity $record
     * @return void
     */
    public function saveSingleRecord(Doctrine_ORM_Entity $record)
    {
        $this->_insertOrUpdate($record);
    }
    
    /**
     * saves all related records to $record
     *
     * @throws PDOException         if something went wrong at database level
     * @param Doctrine_Entity $record
     */
    protected function _saveRelated(Doctrine_ORM_Entity $record)
    {
        $saveLater = array();
        foreach ($record->_getReferences() as $k => $v) {
            $rel = $record->getTable()->getRelation($k);

            $local = $rel->getLocal();
            $foreign = $rel->getForeign();

            if ($rel instanceof Doctrine_Relation_ForeignKey) {
                $saveLater[$k] = $rel;
            } else if ($rel instanceof Doctrine_Relation_LocalKey) {
                // ONE-TO-ONE relationship
                $obj = $record->get($rel->getAlias());

                // Protection against infinite function recursion before attempting to save
                if ($obj instanceof Doctrine_ORM_Entity && $obj->isModified()) {
                    $obj->save();
                    
                    /** Can this be removed?
                    $id = array_values($obj->identifier());

                    foreach ((array) $rel->getLocal() as $k => $field) {
                        $record->set($field, $id[$k]);
                    }
                    */
                }
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
     * @throws Doctrine_Connection_Exception         if something went wrong at database level
     * @param Doctrine_Entity $record
     * @return void
     */
    public function saveAssociations(Doctrine_ORM_Entity $record)
    {
        foreach ($record->_getReferences() as $relationName => $relatedObject) {
            if ($relatedObject === Doctrine_Null::$INSTANCE) {
                continue;
            }
            $rel = $record->getTable()->getRelation($relationName);
            
            if ($rel instanceof Doctrine_Relation_Association) {
                $relatedObject->save($this->_conn);
                $assocTable = $rel->getAssociationTable();
                
                foreach ($relatedObject->getDeleteDiff() as $r) {
                    $query = 'DELETE FROM ' . $assocTable->getTableName()
                           . ' WHERE ' . $rel->getForeign() . ' = ?'
                           . ' AND ' . $rel->getLocal() . ' = ?';
                    // FIXME: composite key support
                    $ids1 = $r->identifier();
                    $id1 = count($ids1) > 0 ? array_pop($ids1) : null;
                    $ids2 = $record->identifier();
                    $id2 = count($ids2) > 0 ? array_pop($ids2) : null;
                    $this->_conn->execute($query, array($id1, $id2));
                }
                
                $assocMapper = $this->_conn->getMapper($assocTable->getComponentName());
                foreach ($relatedObject->getInsertDiff() as $r)  {    
                    $assocRecord = $assocMapper->create();
                    $assocRecord->set($assocTable->getFieldName($rel->getForeign()), $r);
                    $assocRecord->set($assocTable->getFieldName($rel->getLocal()), $record);
                    $assocMapper->save($assocRecord);
                }
            }
        }
    }
    
    /**
     * Updates an entity.
     *
     * @param Doctrine_Entity $record   record to be updated
     * @return boolean                  whether or not the update was successful
     * @todo Move to Doctrine_Table (which will become Doctrine_Mapper).
     */
    protected function _update(Doctrine_ORM_Entity $record)
    {
        $record->preUpdate();
        $this->notifyEntityListeners($record, 'preUpdate', Doctrine_Event::RECORD_UPDATE);
        
        $table = $this->_classMetadata;
        $this->_doUpdate($record);
        
        $record->postUpdate();
        $this->notifyEntityListeners($record, 'postUpdate', Doctrine_Event::RECORD_UPDATE);

        return true;
    }
    
    abstract protected function _doUpdate(Doctrine_ORM_Entity $entity);
    
    /**
     * Inserts an entity.
     *
     * @param Doctrine_Entity $record   record to be inserted
     * @return boolean
     */
    protected function _insert(Doctrine_ORM_Entity $record)
    {
        //$record->preInsert();
        //$this->notifyEntityListeners($record, 'preInsert', Doctrine_Event::RECORD_INSERT);

        $this->_doInsert($record);
        $this->addRecord($record);
        
        //$record->postInsert();
        //$this->notifyEntityListeners($record, 'postInsert', Doctrine_Event::RECORD_INSERT);
        
        return true;
    }
    
    abstract protected function _doInsert(Doctrine_ORM_Entity $entity);
    
    /**
     * Deletes given entity and all it's related entities.
     *
     * Triggered Events: onPreDelete, onDelete.
     *
     * @return boolean      true on success, false on failure
     * @throws Doctrine_Mapper_Exception
     */
    public function delete_old(Doctrine_ORM_Entity $record)
    {
        if ( ! $record->exists()) {
            return false;
        }
        
        if ( ! ($record instanceof $this->_domainClassName)) {
            throw new Doctrine_Mapper_Exception("Mapper of type " . $this->_domainClassName . " 
                    can't save instances of type" . get_class($record) . ".");
        }
        
        if ($conn == null) {
            $conn = $this->_conn;
        }

        $record->preDelete();
        $this->notifyEntityListeners($record, 'preDelete', Doctrine_Event::RECORD_DELETE);
        
        $table = $this->_classMetadata;

        $state = $record->_state();
        $record->_state(Doctrine_ORM_Entity::STATE_LOCKED);
        
        $this->_doDelete($record);
        
        $record->postDelete();
        $this->notifyEntityListeners($record, 'postDelete', Doctrine_Event::RECORD_DELETE);

        return true;
    }
    

}
