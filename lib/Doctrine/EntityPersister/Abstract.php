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

/**
 * 
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 3406 $
 * @link        www.phpdoctrine.org
 * @since       2.0
 */
abstract class Doctrine_EntityPersister_Abstract
{
    /**
     * The names of all the fields that are available on entities created by this mapper. 
     */
    protected $_fieldNames = array();
    
    /**
     * Metadata object that descibes the mapping of the mapped entity class.
     *
     * @var Doctrine_ClassMetadata
     */
    protected $_classMetadata;
    
    /**
     * The name of the domain class this mapper is used for.
     */
    protected $_domainClassName;

    /**
     * The Doctrine_Connection object that the database connection of this mapper.
     *
     * @var Doctrine_Connection $conn
     */
    protected $_conn;
    
    /**
     * The EntityManager.
     *
     * @var unknown_type
     */
    protected $_em;
    
    /**
     * The concrete mapping strategy that is used.
     */
    protected $_mappingStrategy;
    
    /**
     * Null object.
     */
    private $_nullObject;
    
    /**
     * A list of registered entity listeners.
     */
    private $_entityListeners = array();
    
    /**
     * Enter description here...
     *
     * @var unknown_type
     * @todo To EntityManager.
     */
    private $_dataTemplate = array();


    /**
     * Constructs a new mapper.
     *
     * @param string $name                    The name of the domain class this mapper is used for.
     * @param Doctrine_Table $table           The table object used for the mapping procedure.
     * @throws Doctrine_Connection_Exception  if there are no opened connections
     */
    public function __construct(Doctrine_EntityManager $em, Doctrine_ClassMetadata $classMetadata)
    {
        $this->_em = $em;
        $this->_domainClassName = $classMetadata->getClassName();
        $this->_conn = $classMetadata->getConnection();
        $this->_classMetadata = $classMetadata;
        $this->_nullObject = Doctrine_Null::$INSTANCE;
    }
    
    /**
     * Assumes that the keys of the given field array are field names and converts
     * them to column names.
     *
     * @return array
     */
    protected function _convertFieldToColumnNames(array $fields, Doctrine_ClassMetadata $class)
    {
        $converted = array();
        foreach ($fields as $fieldName => $value) {
            $converted[$class->getColumnName($fieldName)] = $value;
        }
        
        return $converted;
    }
    
    /**
     * deletes all related composites
     * this method is always called internally when a record is deleted
     *
     * @throws PDOException         if something went wrong at database level
     * @return void
     */
    protected function _deleteComposites(Doctrine_Entity $record)
    {
        $classMetadata = $this->_classMetadata;
        foreach ($classMetadata->getRelations() as $fk) {
            if ($fk->isComposite()) {
                $obj = $record->get($fk->getAlias());
                if ($obj instanceof Doctrine_Entity && 
                        $obj->_state() != Doctrine_Entity::STATE_LOCKED)  {
                    $obj->delete($this->_mapper->getConnection());
                }
            }
        }
    }

    /**
     * sets the connection for this class
     *
     * @params Doctrine_Connection      a connection object 
     * @return Doctrine_Table           this object
     * @todo refactor
     */
    /*public function setConnection(Doctrine_Connection $conn)
    {
        $this->_conn = $conn;
        return $this;
    }*/

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
     * prepareValue
     * this method performs special data preparation depending on
     * the type of the given column
     *
     * 1. It unserializes array and object typed columns
     * 2. Uncompresses gzip typed columns
     * 3. Gets the appropriate enum values for enum typed columns
     * 4. Initializes special null object pointer for null values (for fast column existence checking purposes)
     *
     * example:
     * <code type='php'>
     * $field = 'name';
     * $value = null;
     * $table->prepareValue($field, $value); // Doctrine_Null
     * </code>
     *
     * @throws Doctrine_Table_Exception     if unserialization of array/object typed column fails or
     * @throws Doctrine_Table_Exception     if uncompression of gzip typed column fails         *
     * @param string $field     the name of the field
     * @param string $value     field value
     * @param string $typeHint  A hint on the type of the value. If provided, the type lookup
     *                          for the field can be skipped. Used i.e. during hydration to
     *                          improve performance on large and/or complex results.
     * @return mixed            prepared value
     * @todo To EntityManager. Make private and use in createEntity().
     *       .. Or, maybe better: Move to hydrator for performance reasons.
     */
    /*public function prepareValue($fieldName, $value, $typeHint = null)
    {
        if ($value === $this->_nullObject) {
            return $this->_nullObject;
        } else if ($value === null) {
            return null;
        } else {
            $type = is_null($typeHint) ? $this->_classMetadata->getTypeOf($fieldName) : $typeHint;
            switch ($type) {
                case 'integer':
                case 'string';
                    // don't do any casting here PHP INT_MAX is smaller than what the databases support
                break;
                case 'enum':
                    return $this->_classMetadata->enumValue($fieldName, $value);
                break;
                case 'boolean':
                    return (boolean) $value;
                break;
                case 'array':
                case 'object':
                    if (is_string($value)) {
                        $value = unserialize($value);
                        if ($value === false) {
                            throw new Doctrine_Mapper_Exception('Unserialization of ' . $fieldName . ' failed.');
                        }
                        return $value;
                    }
                break;
                case 'gzip':
                    $value = gzuncompress($value);
                    if ($value === false) {
                        throw new Doctrine_Mapper_Exception('Uncompressing of ' . $fieldName . ' failed.');
                    }
                    return $value;
                break;
            }
        }
        return $value;
    }*/

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
     * Saves an entity and all it's related entities.
     *
     * @param Doctrine_Entity $record    The entity to save.
     * @param Doctrine_Connection $conn  The connection to use. Will default to the mapper's
     *                                   connection.
     * @throws Doctrine_Mapper_Exception If the mapper is unable to save the given entity.
     */
    public function save(Doctrine_Entity $record, Doctrine_Connection $conn = null)
    {
        if ( ! ($record instanceof $this->_domainClassName)) {
            throw new Doctrine_Mapper_Exception("Mapper of type " . $this->_domainClassName . " 
                    can't save instances of type" . get_class($record) . ".");
        }
        
        if ($conn === null) {
            $conn = $this->_conn;
        }

        $state = $record->_state();
        if ($state === Doctrine_Entity::STATE_LOCKED) {
            return false;
        }
        
        $record->_state(Doctrine_Entity::STATE_LOCKED);
        
        try {
            $conn->beginInternalTransaction();
            $saveLater = $this->_saveRelated($record);

            $record->_state($state);

            if ($record->isValid()) {
                $this->_insertOrUpdate($record);
            } else {
                $conn->transaction->addInvalid($record);
            }

            $state = $record->_state();
            $record->_state(Doctrine_Entity::STATE_LOCKED);

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
    protected function _insertOrUpdate(Doctrine_Entity $record)
    {
        $record->preSave();
        $this->notifyEntityListeners($record, 'preSave', Doctrine_Event::RECORD_SAVE);
        
        switch ($record->_state()) {
            case Doctrine_Entity::STATE_TDIRTY:
                $this->_insert($record);
                break;
            case Doctrine_Entity::STATE_DIRTY:
            case Doctrine_Entity::STATE_PROXY:
                $this->_update($record);
                break;
            case Doctrine_Entity::STATE_CLEAN:
            case Doctrine_Entity::STATE_TCLEAN:
                // do nothing
                break;
        }
        
        $record->postSave();
        $this->notifyEntityListeners($record, 'postSave', Doctrine_Event::RECORD_SAVE);
    }
    
    /**
     * saves the given record
     *
     * @param Doctrine_Entity $record
     * @return void
     */
    public function saveSingleRecord(Doctrine_Entity $record)
    {
        $this->_insertOrUpdate($record);
    }
    
    /**
     * _saveRelated
     * saves all related records to $record
     *
     * @throws PDOException         if something went wrong at database level
     * @param Doctrine_Entity $record
     */
    protected function _saveRelated(Doctrine_Entity $record)
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
                if ($obj instanceof Doctrine_Entity && $obj->isModified()) {
                    $obj->save($this->_conn);
                    
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
    public function saveAssociations(Doctrine_Entity $record)
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
    protected function _update(Doctrine_Entity $record)
    {
        $record->preUpdate();
        $this->notifyEntityListeners($record, 'preUpdate', Doctrine_Event::RECORD_UPDATE);
        
        $table = $this->_classMetadata;
        $this->_doUpdate($record);
        
        $record->postUpdate();
        $this->notifyEntityListeners($record, 'postUpdate', Doctrine_Event::RECORD_UPDATE);

        return true;
    }
    
    abstract protected function _doUpdate(Doctrine_Entity $entity);
    
    /**
     * Inserts an entity.
     *
     * @param Doctrine_Entity $record   record to be inserted
     * @return boolean
     */
    protected function _insert(Doctrine_Entity $record)
    {
        $record->preInsert();
        $this->notifyEntityListeners($record, 'preInsert', Doctrine_Event::RECORD_INSERT);

        $this->_doInsert($record);
        $this->addRecord($record);
        
        $record->postInsert();
        $this->notifyEntityListeners($record, 'postInsert', Doctrine_Event::RECORD_INSERT);
        
        return true;
    }
    
    abstract protected function _doInsert(Doctrine_Entity $entity);
    
    /**
     * Deletes given entity and all it's related entities.
     *
     * Triggered Events: onPreDelete, onDelete.
     *
     * @return boolean      true on success, false on failure
     * @throws Doctrine_Mapper_Exception
     */
    public function delete(Doctrine_Entity $record, Doctrine_Connection $conn = null)
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
        $record->_state(Doctrine_Entity::STATE_LOCKED);
        
        $this->_doDelete($record);
        
        $record->postDelete();
        $this->notifyEntityListeners($record, 'postDelete', Doctrine_Event::RECORD_DELETE);

        return true;
    }
    
    abstract protected function _doDelete(Doctrine_Entity $entity);
    
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
    
    /*public function getFieldName($columnName)
    {
        return $this->_classMetadata->getFieldName($columnName);
    }*/
    
    public function getFieldNames()
    {
        if ($this->_fieldNames) {
            return $this->_fieldNames;
        }
        $this->_fieldNames = $this->_classMetadata->getFieldNames();
        return $this->_fieldNames;
    }
    
    public function getOwningClass($fieldName)
    {
        return $this->_classMetadata;
    }
    
    /* Hooks used during SQL query construction to manipulate the query. */
    
    /**
     * Callback that is invoked during the SQL construction process.
     */
    public function getCustomJoins()
    {
        return array();
    }
    
    /**
     * Callback that is invoked during the SQL construction process.
     */
    public function getCustomFields()
    {
        return array();
    }
}
