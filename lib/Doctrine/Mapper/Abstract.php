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
abstract class Doctrine_Mapper_Abstract extends Doctrine_Configurable implements Countable
{
    /**
     * @var Doctrine_Table  Metadata container that represents the database table this
     *                      mapper is mapping objects to.
     */
    protected $_classMetadata;
    
    /**
     * The name of the domain class this mapper is used for.
     */
    protected $_domainClassName;
    
    /**
     * The names of all the fields that are available on entities created by this mapper. 
     */
    protected $_fieldNames = array();

    /**
     * The Doctrine_Connection object that the database connection of this mapper.
     *
     * @var Doctrine_Connection $conn
     */
    protected $_conn;

    /**
     * @var array $identityMap                          first level cache
     * @todo Proper identity map implementation & move elsewhere?
     */
    protected $_identityMap = array();

    /**
     * @var Doctrine_Table_Repository $repository       record repository
     * @todo Needed? What is it used for? Does the identity map not suffice?
     */
    protected $_repository;

    /**
     * @var array $_invokedMethods              method invoker cache
     */
    protected $_invokedMethods = array();


    /**
     * Constructs a new mapper.
     *
     * @param string $name                    The name of the domain class this mapper is used for.
     * @param Doctrine_Table $table           The table object used for the mapping procedure.
     * @throws Doctrine_Connection_Exception  if there are no opened connections
     */
    public function __construct($name, Doctrine_ClassMetadata $metadata)
    {
        $this->_domainClassName = $name;
        $this->_conn = $metadata->getConnection();
        $this->_classMetadata = $metadata;
        $this->setParent($this->_conn);
        $this->_repository = new Doctrine_Table_Repository($this);  
    }

    public function getMethodOwner($method)
    {
        return (isset($this->_invokedMethods[$method])) ?
                      $this->_invokedMethods[$method] : false;
    }
    
    public function setMethodOwner($method, $class)
    {
        $this->_invokedMethods[$method] = $class;
    }

    /**
     * export
     * exports this table to database based on column and option definitions
     *
     * @throws Doctrine_Connection_Exception    if some error other than Doctrine::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation
     * @return boolean                          whether or not the export operation was successful
     *                                          false if table already existed in the database
     * @deprecated
     * @todo Remove
     */
    /*public function export()
    {
        $this->_conn->export->exportTable($this->_table);
    }*/

    /**
     * getExportableFormat
     * returns exportable presentation of this object
     *
     * @return array
     * @todo move to Table
     */
    /*public function getExportableFormat($parseForeignKeys = true)
    {
        return $this->_table->getExportableFormat($parseForeignKeys);
    }*/

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
     * getRepository
     *
     * @return Doctrine_Table_Repository
     */
    public function getRepository()
    {
        return $this->_repository;
    }

    /**
     * sets the connection for this class
     *
     * @params Doctrine_Connection      a connection object 
     * @return Doctrine_Table           this object
     */
    public function setConnection(Doctrine_Connection $conn)
    {
        $this->_conn = $conn;
        $this->setParent($this->_conn);
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

    /**
     * finds a record by its identifier
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
     * findAll
     * returns a collection of records
     *
     * @param int $hydrationMode        Doctrine::FETCH_ARRAY or Doctrine::FETCH_RECORD
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
     * execute
     * fetches data using the provided queryKey and 
     * the associated query in the query registry
     *
     * if no query for given queryKey is being found a 
     * Doctrine_Query_Registry exception is being thrown
     *
     * @param string $queryKey      the query key
     * @param array $params         prepared statement params (if any)
     * @return mixed                the fetched data
     */
    public function execute($queryKey, $params = array(), $hydrationMode = Doctrine::HYDRATE_RECORD)
    {
        return Doctrine_Manager::getInstance()
                ->getQueryRegistry()
                ->get($queryKey, $this->getComponentName())
                ->execute($params, $hydrationMode);
    }
    
    public function executeNamedQuery($queryName, $params = array(), $hydrationMode = Doctrine::HYDRATE_RECORD)
    {
        return $this->execute($queryName, $params, $hydrationMode);        
    }

    /**
     * executeOne
     * fetches data using the provided queryKey and 
     * the associated query in the query registry
     *
     * if no query for given queryKey is being found a 
     * Doctrine_Query_Registry exception is being thrown
     *
     * @param string $queryKey      the query key
     * @param array $params         prepared statement params (if any)
     * @return mixed                the fetched data
     */
    public function executeOne($queryKey, $params = array(), $hydrationMode = Doctrine::HYDRATE_RECORD)
    {
        return Doctrine_Manager::getInstance()
                ->getQueryRegistry()
                ->get($queryKey, $this->getComponentName())
                ->fetchOne($params, $hydrationMode);
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
     * first checks if record exists in identityMap, if not
     * returns a new record
     *
     * @return Doctrine_Record
     */
    public function getRecord(array $data)
    {
        if ( ! empty($data)) {
            $identifierFieldNames = $this->_classMetadata->getIdentifier();

            if ( ! is_array($identifierFieldNames)) {
                $identifierFieldNames = array($identifierFieldNames);
            }

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
     * @throws Doctrine_Find_Exception
     */
    final public function getProxy($id = null)
    {
        if ($id !== null) {
            $identifierColumnNames = $this->_classMetadata->getIdentifierColumnNames();
            $query = 'SELECT ' . implode(', ', $identifierColumnNames)
                . ' FROM ' . $this->_classMetadata->getTableName()
                . ' WHERE ' . implode(' = ? && ', $identifierColumnNames) . ' = ?';
            $query = $this->applyInheritance($query);

            $params = array_merge(array($id), array_values($this->getDiscriminatorColumn()));

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
     * count
     *
     * @return integer
     */
    public function count()
    {
        $a = $this->_conn->execute('SELECT COUNT(1) FROM ' . $this->_classMetadata->getOption('tableName'))->fetch(Doctrine::FETCH_NUM);
        return current($a);
    }

    /**
     * @return Doctrine_Query  a Doctrine_Query object
     */
    public function getQueryObject()
    {
        $graph = new Doctrine_Query($this->getConnection());
        $graph->load($this->getComponentName());
        return $graph;
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
     * @return mixed            prepared value
     */
    public function prepareValue($fieldName, $value)
    {
        if ($value === self::$_null) {
            return self::$_null;
        } else if ($value === null) {
            return null;
        } else {
            $type = $this->_classMetadata->getTypeOf($fieldName);

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
     */
    public function getComponentName()
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
        return $hydrationMode === Doctrine::HYDRATE_ARRAY ? $results[0] : $results->getFirst();
    }
    
    /**
     * __call
     *
     * Adds support for magic finders.
     * findByColumnName, findByRelationAlias
     * findById, findByContactId, etc.
     *
     * @return void
     */
    public function __call($method, $arguments)
    {
        if (substr($method, 0, 6) == 'findBy') {
            $by = substr($method, 6, strlen($method));
            $method = 'findBy';
        } else if (substr($method, 0, 9) == 'findOneBy') {
            $by = substr($method, 9, strlen($method));
            $method = 'findOneBy';
        }/* else {
            throw new Doctrine_Mapper_Exception("Unknown method '$method'.");
        }*/
        
        if (isset($by)) {
            if ( ! isset($arguments[0])) {
                throw new Doctrine_Mapper_Exception('You must specify the value to findBy');
            }
            
            $fieldName = Doctrine::tableize($by);
            $hydrationMode = isset($arguments[1]) ? $arguments[1]:null;
            
            if ($this->_classMetadata->hasField($fieldName)) {
                return $this->$method($fieldName, $arguments[0], $hydrationMode);
            } else if ($this->_classMetadata->hasRelation($by)) {
                $relation = $this->_classMetadata->getRelation($by);
                
                if ($relation['type'] === Doctrine_Relation::MANY) {
                    throw new Doctrine_Table_Exception('Cannot findBy many relationship.');
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
     * @throws Doctrine_Mapper_Exception If the mapper is unable to save the given record.
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
            //echo "num savelater:" . count($saveLater) . "<br />";

            $record->state($state);

            if ($record->isValid()) {
                $event = new Doctrine_Event($record, Doctrine_Event::RECORD_SAVE);
                $record->preSave($event);
                $this->getRecordListener()->preSave($event);
                
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

                $this->getRecordListener()->postSave($event);
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
            // reset state
            $record->state($state);
            $conn->commit();
        } catch (Exception $e) {
            // save() calls can be nested recursively and exceptions bubble up, so check
            // if there are any internal transactions left that need to be rolled back 
            // before doing so.
            if ($conn->getInternalTransactionLevel() > 0) {
                $conn->rollback();
            } 
            throw $e;
        }
        
        return true;
    }
    
    /**
     * Inserts a single entity into the database, without any related entities.
     *
     * @param Doctrine_Record $record   The entity to insert.
     */
    protected function insertSingleRecord(Doctrine_Record $record)
    {
        $fields = $record->getPrepared();
        if (empty($fields)) {
            return false;
        }
        
        $table = $record->getTable();
        $identifier = (array) $table->getIdentifier();

        $seq = $table->getOption('sequenceName');
        if ( ! empty($seq)) {
            $id = $this->_conn->sequence->nextId($seq);
            $seqName = $table->getIdentifier();
            $fields[$seqName] = $id;
            $record->assignIdentifier($id);
        }

        $this->_conn->insert($table, $fields);

        if (empty($seq) && count($identifier) == 1 && $identifier[0] == $table->getIdentifier() &&
                $table->getIdentifierType() != Doctrine::IDENTIFIER_NATURAL) {
            if (strtolower($this->_conn->getName()) == 'pgsql') {
                $seq = $table->getTableName() . '_' . $identifier[0];
            }

            $id = $this->_conn->sequence->lastInsertId($seq);

            if ( ! $id) {
                throw new Doctrine_Connection_Exception("Couldn't get last insert identifier.");
            }

            $record->assignIdentifier($id);
        } else {
            $record->assignIdentifier(true);
        }
    }
    
    protected function _fireEvent($type, $callback, $invoker)
    {
        
    }
    
    /**
     * saves the given record
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function saveSingleRecord(Doctrine_Record $record)
    {
        //$this->_fireEvent(Doctrine_Event::RECORD_SAVE, 'preSave', $record);
        $event = new Doctrine_Event($record, Doctrine_Event::RECORD_SAVE);
        $record->preSave($event);
        $this->getRecordListener()->preSave($event);

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

        $this->getRecordListener()->postSave($event);
        $record->postSave($event);
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

                foreach ($relatedObject->getInsertDiff() as $r)  {
                    $assocRecord = $this->_conn->getMapper($assocTable->getComponentName())->create();
                    $assocRecord->set($assocTable->getFieldName($rel->getForeign()), $r);
                    $assocRecord->set($assocTable->getFieldName($rel->getLocal()), $record);

                    $this->saveSingleRecord($assocRecord);
                }
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
    protected function update(Doctrine_Record $record)
    {
        $event = new Doctrine_Event($record, Doctrine_Event::RECORD_UPDATE);
        $record->preUpdate($event);
        $table = $this->_classMetadata;
        $this->getRecordListener()->preUpdate($event);

        if ( ! $event->skipOperation) {
            $this->_doUpdate($record);
        }
        
        $this->getRecordListener()->postUpdate($event);
        $record->postUpdate($event);

        return true;
    }
    
    protected function _doUpdate(Doctrine_Record $record)
    {
        $identifier = $record->identifier();
        $array = $record->getPrepared();
        $this->_conn->update($this->_classMetadata, $array, $identifier);
        $record->assignIdentifier(true);
    }
    
    /**
     * inserts a record into database
     *
     * @param Doctrine_Record $record   record to be inserted
     * @return boolean
     */
    protected function insert(Doctrine_Record $record)
    {        
        // trigger event
        $event = new Doctrine_Event($record, Doctrine_Event::RECORD_INSERT);
        $record->preInsert($event);
        $this->getRecordListener()->preInsert($event);

        if ( ! $event->skipOperation) {
            $this->_doInsert($record);
        }
        
        // trigger event
        $this->addRecord($record);
        $this->getRecordListener()->postInsert($event);
        $record->postInsert($event);
        
        return true;
    }
    
    protected function _doInsert(Doctrine_Record $record)
    {
        $this->insertSingleRecord($record);
    }
    
    /**
     * deletes given record and all the related composites
     * this operation is isolated by a transaction
     *
     * this event can be listened by the onPreDelete and onDelete listeners
     *
     * @return boolean      true on success, false on failure
     * @todo Move to Doctrine_Table (which will become Doctrine_Mapper).
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

        $event = new Doctrine_Event($record, Doctrine_Event::RECORD_DELETE);
        $record->preDelete($event);
        $this->getRecordListener()->preDelete($event);
        
        $table = $this->_classMetadata;

        $state = $record->state();
        $record->state(Doctrine_Record::STATE_LOCKED);
        
        if ( ! $event->skipOperation) {
            $this->_doDelete($record, $conn);
        } else {
            // return to original state   
            $record->state($state);
        }
        
        $this->getRecordListener()->postDelete($event);
        $record->postDelete($event);

        return true;
    }
    
    protected function _doDelete(Doctrine_Record $record, Doctrine_Connection $conn)
    {
        try {
            $conn->beginInternalTransaction();
            $this->deleteComposites($record);

            $record->state(Doctrine_Record::STATE_TDIRTY);

            $conn->delete($this->_classMetadata, $record->identifier());
            $record->state(Doctrine_Record::STATE_TCLEAN);

            $this->removeRecord($record);
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }        
        
    }
    
    /**
     * deletes all related composites
     * this method is always called internally when a record is deleted
     *
     * @throws PDOException         if something went wrong at database level
     * @return void
     */
    protected function deleteComposites(Doctrine_Record $record)
    {
        foreach ($this->_classMetadata->getRelations() as $fk) {
            if ($fk->isComposite()) {
                $obj = $record->get($fk->getAlias());
                if ($obj instanceof Doctrine_Record && 
                        $obj->state() != Doctrine_Record::STATE_LOCKED)  {
                    $obj->delete($this->_conn);
                }
            }
        }
    }
    
    public function executeQuery(Doctrine_Query $query)
    {
        
    }
    
    public function getTable()
    {
        return $this->_classMetadata;
    }

    public function getFieldName($columnName)
    {
        return $this->_classMetadata->getFieldName($columnName);
    }
    
    public function getFieldNames()
    {
        if ($this->_fieldNames) {
            return $this->_fieldNames;
        }
        $this->_fieldNames = $this->_classMetadata->getFieldNames();
        return $this->_fieldNames;
    }
    
    public function getOwningTable($fieldName)
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
    
    
    /*public function addToWhere($componentAlias, array &$sqlWhereParts, Doctrine_Query $query)
    {
        
    }
    
    public function addToFrom($sqlString, Doctrine_Query $query)
    {
        
    }*/
    
    /* Hooks used during SQL query construction to manipulate the query. */
    
    public function getCustomJoins()
    {
        return array();
    }
    
    public function getCustomFields()
    {
        return array();
    }
    
    public function getDiscriminatorColumn()
    {
        return array();
    }
}
