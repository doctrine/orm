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
 * A Mapper is responsible for mapping between the domain model and the database
 * back and forth. Each entity in the domain model has a corresponding mapper.
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 3406 $
 * @link        www.phpdoctrine.org
 * @since       1.0
 */
class Doctrine_Mapper
{
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
     * The concrete mapping strategy that is used.
     */
    protected $_mappingStrategy;

    /**
     * @var array $identityMap                          first level cache
     * @todo Move to UnitOfWork.
     */
    protected $_identityMap = array();
    
    /**
     * Null object.
     */
    private $_nullObject;
    
    /**
     * A list of registered entity listeners.
     */
    private $_entityListeners = array();


    /**
     * Constructs a new mapper.
     *
     * @param string $name                    The name of the domain class this mapper is used for.
     * @param Doctrine_Table $table           The table object used for the mapping procedure.
     * @throws Doctrine_Connection_Exception  if there are no opened connections
     */
    public function __construct($name, Doctrine_ClassMetadata $classMetadata)
    {        
        $this->_domainClassName = $name;
        $this->_conn = $classMetadata->getConnection();
        $this->_classMetadata = $classMetadata;
        $this->_nullObject = Doctrine_Null::getInstance();
        if ($classMetadata->getInheritanceType() == Doctrine::INHERITANCETYPE_JOINED) {
            $this->_mappingStrategy = new Doctrine_Mapper_JoinedStrategy($this);
        } else {
            $this->_mappingStrategy = new Doctrine_Mapper_DefaultStrategy($this);
        }
    }

    /**
     * createQuery
     * creates a new Doctrine_Query object and adds the component name
     * of this table as the query 'from' part
     *
     * @param string Optional alias name for component aliasing.
     *
     * @return Doctrine_Query
     */
    public function createQuery($alias = '')
    {
        if ( ! empty($alias)) {
            $alias = ' ' . trim($alias);
        }
        return Doctrine_Query::create($this->_conn)->from($this->getComponentName() . $alias);
    }

    /**
     * sets the connection for this class
     *
     * @params Doctrine_Connection      a connection object 
     * @return Doctrine_Table           this object
     * @todo refactor
     */
    public function setConnection(Doctrine_Connection $conn)
    {
        $this->_conn = $conn;
        return $this;
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

    /**
     * creates a new record
     *
     * @param $array             an array where keys are field names and
     *                           values representing field values
     * @return Doctrine_Record   the created record object
     */
    public function create(array $array = array()) 
    {
        $record = new $this->_domainClassName($this, true);
        $record->fromArray($array);

        return $record;
    }
    
    public function addEntityListener(Doctrine_Record_Listener $listener)
    {
        if ( ! in_array($listener, $this->_entityListeners)) {
            $this->_entityListeners[] = $listener;
            return true;
        }
        return false;
    }
    
    public function removeEntityListener(Doctrine_Record_Listener $listener)
    {
        if ($key = array_search($listener, $this->_entityListeners, true)) {
            unset($this->_entityListeners[$key]);
            return true;
        }
        return false;
    }
    
    public function notifyEntityListeners(Doctrine_Record $entity, $callback, $eventType)
    {
        if ($this->_entityListeners) {
            $event = new Doctrine_Event($entity, $eventType);
            foreach ($this->_entityListeners as $listener) {
                $listener->$callback($event);
            }
        }
    }
    
    public function detach(Doctrine_Record $entity)
    {
        return $this->_conn->unitOfWork->detach($entity);
    }

    /**
     * Finds an entity by its primary key.
     *
     * @param $id                       database row id
     * @param int $hydrationMode        Doctrine::HYDRATE_ARRAY or Doctrine::HYDRATE_RECORD
     * @return mixed                    Array or Doctrine_Record or false if no result
     */
    public function find($id, $hydrationMode = null)
    {
        if (is_null($id)) {
            return false;
        }

        $id = is_array($id) ? array_values($id) : array($id);
        
        return $this->createQuery()
                ->where(implode(' = ? AND ', (array) $this->_classMetadata->getIdentifier()) . ' = ?')
                ->fetchOne($id, $hydrationMode);
    }

    /**
     * Finds all entities of the mapper's class.
     * Use with care.
     *
     * @param int $hydrationMode        Doctrine::HYDRATE_ARRAY or Doctrine::HYDRATE_RECORD
     * @return Doctrine_Collection
     */
    public function findAll($hydrationMode = null)
    {
        return $this->createQuery()->execute(array(), $hydrationMode);
    }

    /**
     * findBySql
     * finds records with given SQL where clause
     * returns a collection of records
     *
     * @param string $dql               DQL after WHERE clause
     * @param array $params             query parameters
     * @param int $hydrationMode        Doctrine::FETCH_ARRAY or Doctrine::FETCH_RECORD
     * @return Doctrine_Collection
     * 
     * @todo This actually takes DQL, not SQL, but it requires column names 
     *       instead of field names. This should be fixed to use raw SQL instead.
     */
    public function findBySql($dql, array $params = array(), $hydrationMode = null)
    {
        return $this->createQuery()->where($dql)->execute($params, $hydrationMode);
    }

    /**
     * findByDql
     * finds records with given DQL where clause
     * returns a collection of records
     *
     * @param string $dql               DQL after WHERE clause
     * @param array $params             query parameters
     * @param int $hydrationMode        Doctrine::FETCH_ARRAY or Doctrine::FETCH_RECORD
     * @return Doctrine_Collection
     */
    public function findByDql($dql, array $params = array(), $hydrationMode = null)
    {
        $query = new Doctrine_Query($this->_conn);
        $component = $this->getComponentName();
        $dql = 'FROM ' . $component . ' WHERE ' . $dql;

        return $query->query($dql, $params, $hydrationMode);        
    }
    
    /**
     * Executes a named query.
     *
     * @param string $queryName     The name that was used when storing the query.
     * @param array $params         The query parameters.
     * @return mixed                The result.
     * @deprecated
     */
    public function executeNamedQuery($queryName, $params = array(), $hydrationMode = Doctrine::HYDRATE_RECORD)
    {
        return Doctrine_Manager::getInstance()
                ->createNamedQuery($queryName)
                ->execute($params, $hydrationMode);    
    }

    /**
     * clear
     * clears the first level cache (identityMap)
     *
     * @return void
     * @todo what about a more descriptive name? clearIdentityMap?
     */
    public function clear()
    {
        $this->_identityMap = array();
    }

    /**
     * addRecord
     * adds a record to identity map
     *
     * @param Doctrine_Record $record       record to be added
     * @return boolean
     * @todo Better name? registerRecord? Move elsewhere to the new location of the identity maps.
     */
    public function addRecord(Doctrine_Record $record)
    {
        $id = implode(' ', $record->identifier());

        if (isset($this->_identityMap[$id])) {
            return false;
        }

        $this->_identityMap[$id] = $record;

        return true;
    }
    
    /**
     * Tells the mapper to manage the entity if it's not already managed.
     *
     * @return boolean  TRUE if the entity was previously not managed and is now managed,
     *                  FALSE otherwise (the entity is already managed).
     */
    public function manage(Doctrine_Record $record)
    {
        return $this->_conn->unitOfWork->manage($record);
    }

    /**
     * removeRecord
     * removes a record from the identity map, returning true if the record
     * was found and removed and false if the record wasn't found.
     *
     * @param Doctrine_Record $record       record to be removed
     * @return boolean
     * @todo Move elsewhere to the new location of the identity maps.
     */
    public function removeRecord(Doctrine_Record $record)
    {
        $id = implode(' ', $record->identifier());

        if (isset($this->_identityMap[$id])) {
            unset($this->_identityMap[$id]);
            return true;
        }

        return false;
    }

    /**
     * getRecord
     * First checks if record exists in identityMap, if not
     * returns a new record.
     *
     * @return Doctrine_Record
     */
    public function getRecord(array $data)
    {
        if ( ! empty($data)) {
            $identifierFieldNames = (array)$this->_classMetadata->getIdentifier();

            $found = false;
            foreach ($identifierFieldNames as $fieldName) {
                if ( ! isset($data[$fieldName])) {
                    // primary key column not found return new record
                    $found = true;
                    break;
                }
                $id[] = $data[$fieldName];
            }

            if ($found) {
                $record = new $this->_domainClassName($this, true, $data);
                $data = array();
                return $record;
            }


            $id = implode(' ', $id);

            if (isset($this->_identityMap[$id])) {
                $record = $this->_identityMap[$id];
                $record->hydrate($data);
            } else {
                $record = new $this->_domainClassName($this, false, $data);
                $this->_identityMap[$id] = $record;
            }
            $data = array();
        } else {
            $record = new $this->_domainClassName($this, true, $data);
        }

        return $record;
    }

    /**
     * @param $id                       database row id
     */
    final public function getProxy($id = null)
    {
        if ($id !== null) {
            $identifierColumnNames = $this->_classMetadata->getIdentifierColumnNames();
            $query = 'SELECT ' . implode(', ', $identifierColumnNames)
                . ' FROM ' . $this->_classMetadata->getTableName()
                . ' WHERE ' . implode(' = ? && ', $identifierColumnNames) . ' = ?';
            $query = $this->applyInheritance($query);

            $params = array_merge(array($id),array());

            $data = $this->_conn->execute($query, $params)->fetch(PDO::FETCH_ASSOC);

            if ($data === false) {
                return false;
            }
        }
        
        return $this->getRecord($data);
    }

    /**
     * applyInheritance
     * @param $where                    query where part to be modified
     * @return string                   query where part with column aggregation inheritance added
     */
    final public function applyInheritance($where)
    {
        $discCol = $this->_classMetadata->getInheritanceOption('discriminatorColumn');
        if ( ! $discCol) {
            return $where;
        }
        
        $discMap = $this->_classMetadata->getInheritanceOption('discriminatorMap');
        $inheritanceMap = array($discCol => array_search($this->_domainClassName, $discMap));
        if ( ! empty($inheritanceMap)) {
            $a = array();
            foreach ($inheritanceMap as $column => $value) {
                $a[] = $column . ' = ?';
            }
            $i = implode(' AND ', $a);
            $where .= ' AND ' . $i;
        }

        return $where;
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
     */
    public function prepareValue($fieldName, $value, $typeHint = null)
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
    }
    
    /**
     * Hydrates the given data into the entity.
     * 
     */
    public function hydrate(Doctrine_Record $entity, array $data)
    {
        $this->_values = array_merge($this->_values, $this->cleanData($data));
        $this->_data   = array_merge($this->_data, $data);
        $this->_extractIdentifier(true);
    }

    /**
     * getTree
     *
     * getter for associated tree
     *
     * @return mixed  if tree return instance of Doctrine_Tree, otherwise returns false
     * @todo Part of the NestedSet Behavior plugin. Move outta here some day...
     */
    public function getTree()
    {
        return $this->_classMetadata->getTree();
    }
    
    /**
     * isTree
     *
     * determine if table acts as tree
     *
     * @return mixed  if tree return true, otherwise returns false
     * @todo Part of the NestedSet Behavior plugin. Move outta here some day...
     */
    public function isTree()
    {
        return $this->_classMetadata->isTree();
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
     * Gets the name of the class the mapper is used for.
     */
    public function getMappedClassName()
    {
        return $this->_domainClassName;
    }

    /**
     * returns a string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return Doctrine_Lib::getTableAsString($this);
    }
    
    /**
     * findBy
     *
     * @param string $column 
     * @param string $value 
     * @param string $hydrationMode 
     * @return void
     */
    protected function findBy($fieldName, $value, $hydrationMode = null)
    {
        return $this->createQuery()->where($fieldName . ' = ?')->execute(array($value), $hydrationMode);
    }
    
    /**
     * findOneBy
     *
     * @param string $column 
     * @param string $value 
     * @param string $hydrationMode 
     * @return void
     */
    protected function findOneBy($fieldName, $value, $hydrationMode = null)
    {
        $results = $this->createQuery()->where($fieldName . ' = ?')->limit(1)->execute(
                array($value), $hydrationMode);
        return $hydrationMode === Doctrine::HYDRATE_ARRAY ? array_shift($results) : $results->getFirst();
    }
    
    /**
     * __call
     *
     * Adds support for magic finders.
     * findByColumnName, findByRelationAlias
     * findById, findByContactId, etc.
     *
     * @return void
     * @throws Doctrine_Mapper_Exception  If the method called is an invalid find* method
     *                                    or no find* method at all and therefore an invalid
     *                                    method call.
     */
    public function __call($method, $arguments)
    {
        if (substr($method, 0, 6) == 'findBy') {
            $by = substr($method, 6, strlen($method));
            $method = 'findBy';
        } else if (substr($method, 0, 9) == 'findOneBy') {
            $by = substr($method, 9, strlen($method));
            $method = 'findOneBy';
        } else {
            try {
                throw new Exception();
            } catch (Exception $e) {
                echo $e->getTraceAsString() . "<br/><br/>";
            }
            throw new Doctrine_Mapper_Exception("Undefined method '$method'.");
        }
        
        if (isset($by)) {
            if ( ! isset($arguments[0])) {
                throw new Doctrine_Mapper_Exception('You must specify the value to findBy.');
            }
            
            $fieldName = Doctrine::tableize($by);
            $hydrationMode = isset($arguments[1]) ? $arguments[1]:null;
            
            if ($this->_classMetadata->hasField($fieldName)) {
                return $this->$method($fieldName, $arguments[0], $hydrationMode);
            } else if ($this->_classMetadata->hasRelation($by)) {
                $relation = $this->_classMetadata->getRelation($by);
                if ($relation['type'] === Doctrine_Relation::MANY) {
                    throw new Doctrine_Mapper_Exception('Cannot findBy many relationship.');
                }
                return $this->$method($relation['local'], $arguments[0], $hydrationMode);
            } else {
                throw new Doctrine_Mapper_Exception('Cannot find by: ' . $by . '. Invalid field or relationship alias.');
            }
        }
    }
    
    /**
     * Saves an entity and all it's related entities.
     *
     * @param Doctrine_Record $record    The entity to save.
     * @param Doctrine_Connection $conn  The connection to use. Will default to the mapper's
     *                                   connection.
     * @throws Doctrine_Mapper_Exception If the mapper is unable to save the given entity.
     */
    public function save(Doctrine_Record $record, Doctrine_Connection $conn = null)
    {
        if ( ! ($record instanceof $this->_domainClassName)) {
            throw new Doctrine_Mapper_Exception("Mapper of type " . $this->_domainClassName . " 
                    can't save instances of type" . get_class($record) . ".");
        }
        
        if ($conn === null) {
            $conn = $this->_conn;
        }

        $state = $record->state();
        if ($state === Doctrine_Record::STATE_LOCKED) {
            return false;
        }
        
        $record->state(Doctrine_Record::STATE_LOCKED);
        
        try {
            $conn->beginInternalTransaction();
            $saveLater = $this->_saveRelated($record);

            $record->state($state);

            if ($record->isValid()) {
                $this->_insertOrUpdate($record);
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
            // reset state
            $record->state($state);
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
     * @param Doctrine_Record $record  The entity to insert/update.
     */
    protected function _insertOrUpdate(Doctrine_Record $record)
    {
        $record->preSave();
        $this->notifyEntityListeners($record, 'preSave', Doctrine_Event::RECORD_SAVE);
        
        switch ($record->state()) {
            case Doctrine_Record::STATE_TDIRTY:
                $this->_insert($record);
                break;
            case Doctrine_Record::STATE_DIRTY:
            case Doctrine_Record::STATE_PROXY:
                $this->_update($record);
                break;
            case Doctrine_Record::STATE_CLEAN:
            case Doctrine_Record::STATE_TCLEAN:
                // do nothing
                break;
        }
        
        $record->postSave();
        $this->notifyEntityListeners($record, 'postSave', Doctrine_Event::RECORD_SAVE);
    }
    
    /**
     * saves the given record
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function saveSingleRecord(Doctrine_Record $record)
    {
        $this->_insertOrUpdate($record);
    }
    
    /**
     * _saveRelated
     * saves all related records to $record
     *
     * @throws PDOException         if something went wrong at database level
     * @param Doctrine_Record $record
     */
    protected function _saveRelated(Doctrine_Record $record)
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
     * @param Doctrine_Record $record
     * @return void
     */
    public function saveAssociations(Doctrine_Record $record)
    {
        foreach ($record->getReferences() as $relationName => $relatedObject) {
            
            $rel = $record->getTable()->getRelation($relationName);
            
            if ($rel instanceof Doctrine_Relation_Association) {
                $relatedObject->save($this->_conn);
                $assocTable = $rel->getAssociationTable();
                
                foreach ($relatedObject->getDeleteDiff() as $r) {
                    $query = 'DELETE FROM ' . $assocTable->getTableName()
                           . ' WHERE ' . $rel->getForeign() . ' = ?'
                           . ' AND ' . $rel->getLocal() . ' = ?';
                    $this->_conn->execute($query, array($r->getIncremented(), $record->getIncremented()));
                }
                
                $assocMapper = $this->_conn->getMapper($assocTable->getComponentName());
                foreach ($relatedObject->getInsertDiff() as $r)  {    
                    $assocRecord = $assocMapper->create();
                    $assocRecord->set($assocTable->getFieldName($rel->getForeign()), $r);
                    $assocRecord->set($assocTable->getFieldName($rel->getLocal()), $record);
                    $assocMapper->save($assocRecord);
                    //$this->saveSingleRecord($assocRecord);
                }
            }
        }
    }
    
    /**
     * Updates an entity.
     *
     * @param Doctrine_Record $record   record to be updated
     * @return boolean                  whether or not the update was successful
     * @todo Move to Doctrine_Table (which will become Doctrine_Mapper).
     */
    protected function _update(Doctrine_Record $record)
    {
        $record->preUpdate();
        $this->notifyEntityListeners($record, 'preUpdate', Doctrine_Event::RECORD_UPDATE);
        
        $table = $this->_classMetadata;
        $this->_mappingStrategy->doUpdate($record);
        
        $record->postUpdate();
        $this->notifyEntityListeners($record, 'postUpdate', Doctrine_Event::RECORD_UPDATE);

        return true;
    }
    
    /**
     * Inserts an entity.
     *
     * @param Doctrine_Record $record   record to be inserted
     * @return boolean
     */
    protected function _insert(Doctrine_Record $record)
    {
        $record->preInsert();
        $this->notifyEntityListeners($record, 'preInsert', Doctrine_Event::RECORD_INSERT);

        $this->_mappingStrategy->doInsert($record);
        $this->addRecord($record);
        
        $record->postInsert();
        $this->notifyEntityListeners($record, 'postInsert', Doctrine_Event::RECORD_INSERT);
        
        return true;
    }
    
    /**
     * Deletes given entity and all it's related entities.
     *
     * Triggered Events: onPreDelete, onDelete.
     *
     * @return boolean      true on success, false on failure
     * @throws Doctrine_Mapper_Exception
     */
    public function delete(Doctrine_Record $record, Doctrine_Connection $conn = null)
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

        $state = $record->state();
        $record->state(Doctrine_Record::STATE_LOCKED);
        
        $this->_mappingStrategy->doDelete($record);
        
        $record->postDelete();
        $this->notifyEntityListeners($record, 'postDelete', Doctrine_Event::RECORD_DELETE);

        return true;
    }
    
    public function hasAttribute($key)
    {
        switch ($key) {
            case Doctrine::ATTR_LOAD_REFERENCES:
            case Doctrine::ATTR_QUERY_LIMIT:
            case Doctrine::ATTR_COLL_KEY:
            case Doctrine::ATTR_VALIDATE:
                return true;
            default:
                return false;
        }
    }
    
    public function executeQuery(Doctrine_Query $query)
    {
        
    }
    
    public function getTable()
    {
        return $this->_classMetadata;
    }
    
    public function getClassMetadata()
    {
        return $this->_classMetadata;
    }
    
    public function getIdentityMap()
    {
        return $this->_identityMap;
    }
    
    public function dump()
    {
        var_dump($this->_invokedMethods);
    }
    
    public function free()
    {
        $this->_mappingStrategy = null;
    }
    
    public function getMapping()
    {
        return $this->_mappingStrategy;
    }
    
    
    
    public function getFieldName($columnName)
    {
        return $this->_mappingStrategy->getFieldName($columnName);
    }
    
    public function getFieldNames()
    {
        return $this->_mappingStrategy->getFieldNames();
    }
    
    public function getOwningClass($fieldName)
    {
        return $this->_mappingStrategy->getOwningClass($fieldName);
    }
    
    /* Hooks used during SQL query construction to manipulate the query. */
    
    /**
     * Callback that is invoked during the SQL construction process.
     */
    public function getCustomJoins()
    {
        return $this->_mappingStrategy->getCustomJoins();
    }
    
    /**
     * Callback that is invoked during the SQL construction process.
     */
    public function getCustomFields()
    {
        return $this->_mappingStrategy->getCustomFields();
    }
}
