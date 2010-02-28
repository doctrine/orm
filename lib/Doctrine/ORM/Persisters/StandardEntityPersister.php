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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\ORMException,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\DBAL\Connection,
    Doctrine\DBAL\Types\Type,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Query,
    Doctrine\ORM\PersistentCollection,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Events;

/**
 * Base class for all EntityPersisters. An EntityPersister is a class that knows
 * how to persist and load entities of a specific type.
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @author      Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 3406 $
 * @link        www.doctrine-project.org
 * @since       2.0
 * @todo Rename: BasicEntityPersister
 */
class StandardEntityPersister
{
    /**
     * Metadata object that describes the mapping of the mapped entity class.
     *
     * @var Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $_class;

    /**
     * The underlying Connection of the used EntityManager.
     *
     * @var Doctrine\DBAL\Connection $conn
     */
    protected $_conn;
    
    /**
     * The database platform.
     * 
     * @var AbstractPlatform
     */
    protected $_platform;

    /**
     * The EntityManager instance.
     *
     * @var Doctrine\ORM\EntityManager
     */
    protected $_em;
    
    /**
     * The SqlLogger to use, if any.
     * 
     * @var Doctrine\DBAL\Logging\SqlLogger
     */
    protected $_sqlLogger;

    /**
     * Queued inserts.
     *
     * @var array
     */
    protected $_queuedInserts = array();
    
    /**
     * Mappings of column names as they appear in an SQL result set to
     * column names as they are defined in the mapping.
     * 
     * @var array
     */
    protected $_resultColumnNames = array();
    
    /**
     * The INSERT SQL statement used for entities handled by this persister.
     * 
     * @var string
     */
    private $_insertSql;

    /**
     * Initializes a new <tt>StandardEntityPersister</tt> that uses the given EntityManager
     * and persists instances of the class described by the given class metadata descriptor.
     * 
     * @param EntityManager $em
     * @param ClassMetadata $class
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        $this->_em = $em;
        $this->_class = $class;
        $this->_conn = $em->getConnection();
        $this->_sqlLogger = $this->_conn->getConfiguration()->getSqlLogger();
        $this->_platform = $this->_conn->getDatabasePlatform();
    }

    /**
     * Adds an entity to the queued insertions.
     * The entity remains queued until {@link executeInserts()} is invoked.
     *
     * @param object $entity The entitiy to queue for insertion.
     */
    public function addInsert($entity)
    {
        $this->_queuedInserts[spl_object_hash($entity)] = $entity;
    }

    /**
     * Executes all queued inserts.
     *
     * @return array An array of any generated post-insert IDs.
     */
    public function executeInserts()
    {
        if ( ! $this->_queuedInserts) {
            return;
        }

        $isVersioned = $this->_class->isVersioned;

        $postInsertIds = array();
        $idGen = $this->_class->idGenerator;
        $isPostInsertId = $idGen->isPostInsertGenerator();

        $stmt = $this->_conn->prepare($this->getInsertSql());
        $primaryTableName = $this->_class->primaryTable['name'];

        foreach ($this->_queuedInserts as $entity) {
            $insertData = array();
            $this->_prepareData($entity, $insertData, true);

            if (isset($insertData[$primaryTableName])) {
                $paramIndex = 1;
                if ($this->_sqlLogger !== null) {
                    $params = array();
                    foreach ($insertData[$primaryTableName] as $value) {
                        $params[$paramIndex] = $value;
                        $stmt->bindValue($paramIndex++, $value);
                    }
                    $this->_sqlLogger->logSql($this->getInsertSql(), $params);
                } else {
                    foreach ($insertData[$primaryTableName] as $value) {
                        $stmt->bindValue($paramIndex++, $value);
                    }
                }
            } else if ($this->_sqlLogger !== null) {
                $this->_sqlLogger->logSql($this->getInsertSql());
            }
            
            $stmt->execute();

            if ($isPostInsertId) {
                $id = $idGen->generate($this->_em, $entity);
                $postInsertIds[$id] = $entity;
            } else {
                $id = $this->_class->getIdentifierValues($entity);
            }

            if ($isVersioned) {
                $this->_assignDefaultVersionValue($this->_class, $entity, $id);
            }
        }
        
        $stmt->closeCursor();
        $this->_queuedInserts = array();

        return $postInsertIds;
    }

    /**
     * This function retrieves the default version value which was created
     * by the DBMS INSERT statement. The value is assigned back in to the 
     * $entity versionField property.
     *
     * @return void
     */
    protected function _assignDefaultVersionValue($class, $entity, $id)
    {
        $versionField = $this->_class->versionField;
        $identifier = $this->_class->getIdentifierColumnNames();
        $versionFieldColumnName = $this->_class->getColumnName($versionField);
        //FIXME: Order with composite keys might not be correct
        $sql = "SELECT " . $versionFieldColumnName . " FROM " . $class->getQuotedTableName($this->_platform)
               . " WHERE " . implode(' = ? AND ', $identifier) . " = ?";
        $value = $this->_conn->fetchColumn($sql, array_values((array)$id));
        $this->_class->setFieldValue($entity, $versionField, $value);
    }

    /**
     * Updates an entity.
     *
     * @param object $entity The entity to update.
     */
    public function update($entity)
    {
        $updateData = array();
        $this->_prepareData($entity, $updateData);
        $id = array_combine(
            $this->_class->getIdentifierColumnNames(),
            $this->_em->getUnitOfWork()->getEntityIdentifier($entity)
        );
        $tableName = $this->_class->primaryTable['name'];

        if (isset($updateData[$tableName]) && $updateData[$tableName]) {
            $this->_doUpdate($entity, $tableName, $updateData[$tableName], $id);
        }
    }

    /**
     * Perform UPDATE statement for an entity. This function has support for
     * optimistic locking if the entities ClassMetadata has versioning enabled.
     *
     * @param object $entity        The entity object being updated
     * @param string $tableName     The name of the table being updated
     * @param array $data           The array of data to set
     * @param array $where          The condition used to update
     * @return void
     */
    protected function _doUpdate($entity, $tableName, $data, $where)
    {
        // Note: $tableName and column names in $data are already quoted for SQL.
        
        $set = array();
        foreach ($data as $columnName => $value) {
            $set[] = $columnName . ' = ?';
        }

        if ($isVersioned = $this->_class->isVersioned) {
            $versionField = $this->_class->versionField;
            $versionFieldType = $this->_class->getTypeOfField($versionField);
            $where[$versionField] = Type::getType($versionFieldType)
                    ->convertToDatabaseValue($this->_class->reflFields[$versionField]->getValue($entity), $this->_platform);
            $versionFieldColumnName = $this->_class->getQuotedColumnName($versionField, $this->_platform);
            if ($versionFieldType == 'integer') {
                $set[] = $versionFieldColumnName . ' = ' . $versionFieldColumnName . ' + 1';
            } else if ($versionFieldType == 'datetime') {
                $set[] = $versionFieldColumnName . ' = CURRENT_TIMESTAMP';
            }
        }

        $params = array_merge(array_values($data), array_values($where));

        $sql  = 'UPDATE ' . $tableName
                . ' SET ' . implode(', ', $set)
                . ' WHERE ' . implode(' = ? AND ', array_keys($where)) . ' = ?';

        $result = $this->_conn->executeUpdate($sql, $params);

        if ($isVersioned && ! $result) {
            throw \Doctrine\ORM\OptimisticLockException::lockFailed();
        }
    }

    /**
     * Deletes an entity.
     *
     * @param object $entity The entity to delete.
     */
    public function delete($entity)
    {
        $id = array_combine(
            $this->_class->getIdentifierColumnNames(),
            $this->_em->getUnitOfWork()->getEntityIdentifier($entity)
        );
        $this->_conn->delete($this->_class->primaryTable['name'], $id);
    }

    /**
     * Gets the ClassMetadata instance of the entity class this persister is used for.
     *
     * @return Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->_class;
    }

    /**
     * Prepares the data changeset of an entity for database insertion (INSERT/UPDATE).
     * 
     * During this preparation the array that is passed as the second parameter is filled with
     * <columnName> => <value> pairs, grouped by table name.
     *
     * Example:
     * <code>
     * array(
     *    'foo_table' => array('column1' => 'value1', 'column2' => 'value2', ...),
     *    'bar_table' => array('columnX' => 'valueX', 'columnY' => 'valueY', ...),
     *    ...
     * )
     * </code>
     *
     * Notes to inheritors: Be sure to call <code>parent::_prepareData($entity, $result, $isInsert);</code>
     *
     * @param object $entity The entity for which to prepare the data.
     * @param array $result The reference to the data array.
     * @param boolean $isInsert Whether the preparation is for an INSERT (or UPDATE, if FALSE).
     */
    protected function _prepareData($entity, array &$result, $isInsert = false)
    {
        $uow = $this->_em->getUnitOfWork();

        if ($versioned = $this->_class->isVersioned) {
            $versionField = $this->_class->versionField;
        }

        foreach ($uow->getEntityChangeSet($entity) as $field => $change) {
            if ($versioned && $versionField == $field) {
                continue;
            }
            
            $oldVal = $change[0];
            $newVal = $change[1];

            if (isset($this->_class->associationMappings[$field])) {
                $assocMapping = $this->_class->associationMappings[$field];
                // Only owning side of x-1 associations can have a FK column.
                if ( ! $assocMapping->isOwningSide || ! $assocMapping->isOneToOne()) {
                    continue;
                }

                if ($newVal !== null) {
                    $oid = spl_object_hash($newVal);
                    if (isset($this->_queuedInserts[$oid]) || $uow->isScheduledForInsert($newVal)) {
                        // The associated entity $newVal is not yet persisted, so we must
                        // set $newVal = null, in order to insert a null value and schedule an
                        // extra update on the UnitOfWork.
                        $uow->scheduleExtraUpdate($entity, array(
                            $field => array(null, $newVal)
                        ));
                        $newVal = null;
                    }
                }
                
                if ($newVal !== null) {
                    $newValId = $uow->getEntityIdentifier($newVal);
                }
                
                $targetClass = $this->_em->getClassMetadata($assocMapping->targetEntityName);
                $owningTable = $this->getOwningTable($field);
                
                foreach ($assocMapping->sourceToTargetKeyColumns as $sourceColumn => $targetColumn) {
                    $quotedSourceColumn = $assocMapping->getQuotedJoinColumnName($sourceColumn, $this->_platform);
                    if ($newVal === null) {
                        $result[$owningTable][$quotedSourceColumn] = null;
                    } else {
                        $result[$owningTable][$quotedSourceColumn] = $newValId[$targetClass->fieldNames[$targetColumn]];
                    }
                }
            } else if ($newVal === null) {
                $columnName = $this->_class->getQuotedColumnName($field, $this->_platform);
                $result[$this->getOwningTable($field)][$columnName] = null;
            } else {
                $columnName = $this->_class->getQuotedColumnName($field, $this->_platform);
                $result[$this->getOwningTable($field)][$columnName] = Type::getType(
                        $this->_class->fieldMappings[$field]['type'])
                        ->convertToDatabaseValue($newVal, $this->_platform);
            }
        }
    }

    /**
     * Gets the name of the table that owns the column the given field is mapped to.
     *
     * @param string $fieldName
     * @return string
     */
    public function getOwningTable($fieldName)
    {
        return $this->_class->getQuotedTableName($this->_platform);
    }

    /**
     * Loads an entity by a list of field criteria.
     *
     * @param array $criteria The criteria by which to load the entity.
     * @param object $entity The entity to load the data into. If not specified,
     *        a new entity is created.
     * @param $assoc The association that connects the entity to load to another entity, if any.
     * @param array $hints Hints for entity creation.
     * @return The loaded entity instance or NULL if the entity/the data can not be found.
     */
    public function load(array $criteria, $entity = null, $assoc = null, array $hints = array())
    {
        $sql = $this->_getSelectEntitiesSql($criteria, $assoc);
        $params = array_values($criteria);
        
        if ($this->_sqlLogger !== null) {
            $this->_sqlLogger->logSql($sql, $params);
        }
        
        $stmt = $this->_conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(Connection::FETCH_ASSOC);
        $stmt->closeCursor();
        
        return $this->_createEntity($result, $entity, $hints);
    }
    
    /**
     * Refreshes an entity.
     * 
     * @param array $id The identifier of the entity as an associative array from column names to values.
     * @param object $entity The entity to refresh.
     */
    final public function refresh(array $id, $entity)
    {
        $sql = $this->_getSelectEntitiesSql($id);
        $params = array_values($id);
        
        if ($this->_sqlLogger !== null) {
            $this->_sqlLogger->logSql($sql, $params);
        }
        
        $stmt = $this->_conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(Connection::FETCH_ASSOC);
        $stmt->closeCursor();
        
        $metaColumns = array();
        $newData = array();
        
        // Refresh simple state
        foreach ($result as $column => $value) {
            $column = isset($this->_resultColumnNames[$column]) ? $this->_resultColumnNames[$column] : $column;
            if (isset($this->_class->fieldNames[$column])) {
                $fieldName = $this->_class->fieldNames[$column];
                $type = Type::getType($this->_class->fieldMappings[$fieldName]['type']);
                $newValue = $type->convertToPHPValue($value, $this->_platform);
                $this->_class->reflFields[$fieldName]->setValue($entity, $newValue);
                $newData[$fieldName] = $newValue;
            } else {
                $metaColumns[$column] = $value;
            }
        }
        
        // Refresh associations
        foreach ($this->_class->associationMappings as $field => $assoc) {
            $value = $this->_class->reflFields[$field]->getValue($entity);
            if ($assoc->isOneToOne()) {
                if ($value instanceof Proxy && ! $value->__isInitialized__) {
                    continue; // skip uninitialized proxies
                }
                
                if ($assoc->isOwningSide) {
                    $joinColumnValues = array();
                    foreach ($assoc->targetToSourceKeyColumns as $targetColumn => $srcColumn) {
                        if ($metaColumns[$srcColumn] !== null) {
                            $joinColumnValues[$targetColumn] = $metaColumns[$srcColumn];
                        }
                    }
                    if ( ! $joinColumnValues && $value !== null) {
                        $this->_class->reflFields[$field]->setValue($entity, null);
                        $newData[$field] = null;
                    } else if ($value !== null) {
                        // Check identity map first, if the entity is not there,
                        // place a proxy in there instead.
                        $targetClass = $this->_em->getClassMetadata($assoc->targetEntityName);
                        if ($found = $this->_em->getUnitOfWork()->tryGetById($joinColumnValues, $targetClass->rootEntityName)) {
                            $this->_class->reflFields[$field]->setValue($entity, $found);
                            // Complete inverse side, if necessary.
                            if (isset($targetClass->inverseMappings[$this->_class->name][$field])) {
                                $inverseAssoc = $targetClass->inverseMappings[$this->_class->name][$field];
                                $targetClass->reflFields[$inverseAssoc->sourceFieldName]->setValue($found, $entity);
                            }
                            $newData[$field] = $found;
                        } else {
                            // FIXME: What is happening with subClassees here?
                            $proxy = $this->_em->getProxyFactory()->getProxy($assoc->targetEntityName, $joinColumnValues);
                            $this->_class->reflFields[$field]->setValue($entity, $proxy);
                            $newData[$field] = $proxy;
                            $this->_em->getUnitOfWork()->registerManaged($proxy, $joinColumnValues, array());
                        }
                    }
                } else {
                    // Inverse side of 1-1/1-x can never be lazy.
                    $newData[$field] = $assoc->load($entity, null, $this->_em);
                }
            } else if ($value instanceof PersistentCollection && $value->isInitialized()) {
                $value->setInitialized(false);
                $newData[$field] = $value;
            }
        }
        
        $this->_em->getUnitOfWork()->setOriginalEntityData($entity, $newData);
    }
    
    /**
     * Loads all entities by a list of field criteria.
     * 
     * @param array $criteria
     * @return array
     */
    public function loadAll(array $criteria = array())
    {
        $entities = array();
        
        $sql = $this->_getSelectEntitiesSql($criteria);
        $params = array_values($criteria);
        
        if ($this->_sqlLogger !== null) {
            $this->_sqlLogger->logSql($sql, $params);
        }
        
        $stmt = $this->_conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(Connection::FETCH_ASSOC);
        $stmt->closeCursor();
        
        foreach ($result as $row) {
            $entities[] = $this->_createEntity($row);
        }
        
        return $entities;
    }
    
    /**
     * Loads a collection of entities in a one-to-many association.
     *
     * @param OneToManyMapping $assoc
     * @param array $criteria The criteria by which to select the entities.
     * @param PersistentCollection The collection to fill.
     */
    public function loadOneToManyCollection($assoc, array $criteria, PersistentCollection $coll)
    {
        $owningAssoc = $this->_class->associationMappings[$coll->getMapping()->mappedByFieldName];
        
        $sql = $this->_getSelectEntitiesSql($criteria, $owningAssoc, $assoc->orderBy);

        $params = array_values($criteria);
        
        if ($this->_sqlLogger !== null) {
            $this->_sqlLogger->logSql($sql, $params);
        }
        
        $stmt = $this->_conn->prepare($sql);
        $stmt->execute($params);
        while ($result = $stmt->fetch(Connection::FETCH_ASSOC)) {
            $coll->hydrateAdd($this->_createEntity($result));
        }
        $stmt->closeCursor();
    }
    
    /**
     * Loads a collection of entities of a many-to-many association.
     *
     * @param ManyToManyMapping $assoc
     * @param array $criteria
     * @param PersistentCollection $coll The collection to fill.
     */
    public function loadManyToManyCollection($assoc, array $criteria, PersistentCollection $coll)
    {
        $sql = $this->_getSelectManyToManyEntityCollectionSql($assoc, $criteria);
        $params = array_values($criteria);
        
        if ($this->_sqlLogger !== null) {
            $this->_sqlLogger->logSql($sql, $params);
        }
        
        $stmt = $this->_conn->prepare($sql);
        $stmt->execute($params);
        while ($result = $stmt->fetch(Connection::FETCH_ASSOC)) {
            $coll->hydrateAdd($this->_createEntity($result));
        }
        $stmt->closeCursor();
    }
    
    /**
     * Creates or fills a single entity object from an SQL result.
     * 
     * @param $result The SQL result.
     * @param object $entity The entity object to fill.
     * @param array $hints Hints for entity creation.
     * @return object The filled and managed entity object or NULL, if the SQL result is empty.
     */
    private function _createEntity($result, $entity = null, array $hints = array())
    {
        if ($result === false) {
            return null;
        }

        list($entityName, $data) = $this->_processSqlResult($result);
        
        if ($entity !== null) {
            $hints[Query::HINT_REFRESH] = true;
            $id = array();
            if ($this->_class->isIdentifierComposite) {
                foreach ($this->_class->identifier as $fieldName) {
                    $id[$fieldName] = $data[$fieldName];
                }
            } else {
                $id = array($this->_class->identifier[0] => $data[$this->_class->identifier[0]]);
            }
            $this->_em->getUnitOfWork()->registerManaged($entity, $id, $data);
        }
        
        return $this->_em->getUnitOfWork()->createEntity($entityName, $data, $hints);
    }
    
    /**
     * Processes an SQL result set row that contains data for an entity of the type
     * this persister is responsible for.
     * 
     * @param array $sqlResult The SQL result set row to process.
     * @return array A tuple where the first value is the actual type of the entity and
     *               the second value the data of the entity.
     */
    protected function _processSqlResult(array $sqlResult)
    {
        $data = array();
        foreach ($sqlResult as $column => $value) {
            $column = isset($this->_resultColumnNames[$column]) ? $this->_resultColumnNames[$column] : $column;
            if (isset($this->_class->fieldNames[$column])) {
                $field = $this->_class->fieldNames[$column];
                $data[$field] = Type::getType($this->_class->fieldMappings[$field]['type'])
                        ->convertToPHPValue($value, $this->_platform);
            } else {
                $data[$column] = $value;
            }
        }
        
        return array($this->_class->name, $data);
    }

    /**
     * Gets the SELECT SQL to select one or more entities by a set of field criteria.
     *
     * @param array $criteria
     * @param AssociationMapping $assoc
     * @param string $orderBy
     * @return string
     */
    protected function _getSelectEntitiesSql(array &$criteria, $assoc = null, $orderBy = null)
    {
        // Construct WHERE conditions
        $conditionSql = '';
        foreach ($criteria as $field => $value) {
            if ($conditionSql != '') {
                $conditionSql .= ' AND ';
            }
            
            if (isset($this->_class->columnNames[$field])) {
                $conditionSql .= $this->_class->getQuotedColumnName($field, $this->_platform);
            } else if (isset($this->_class->fieldNames[$field])) {
                $conditionSql .= $this->_class->getQuotedColumnName($this->_class->fieldNames[$field], $this->_platform);
            } else if ($assoc !== null) {
                $conditionSql .= $assoc->getQuotedJoinColumnName($field, $this->_platform);
            } else {
                throw ORMException::unrecognizedField($field);
            }
            $conditionSql .= ' = ?';
        }

        $orderBySql = '';
        if ($orderBy !== null) {
            $orderBySql = $this->_getCollectionOrderBySql(
                $orderBy, $this->_class->getQuotedTableName($this->_platform)
            );
        }

        return 'SELECT ' . $this->_getSelectColumnList() 
             . ' FROM ' . $this->_class->getQuotedTableName($this->_platform)
             . ($conditionSql ? ' WHERE ' . $conditionSql : '') . $orderBySql;
    }

    /**
     * Generate ORDER BY Sql Snippet for ordered collections
     * 
     * @param array $orderBy
     * @return string
     */
    protected function _getCollectionOrderBySql(array $orderBy, $baseTableAlias, $tableAliases = array())
    {
        $orderBySql = '';
        foreach ($orderBy AS $fieldName => $orientation) {
            if (!isset($this->_class->fieldMappings[$fieldName])) {
                ORMException::unrecognizedField($fieldName);
            }

            $tableAlias = isset($this->_class->fieldMappings['inherited']) ?
                $tableAliases[$this->_class->fieldMappings['inherited']] : $baseTableAlias;
            $columnName = $this->_class->getQuotedColumnName($fieldName, $this->_platform);
            if ($orderBySql != '') {
                $orderBySql .= ', ';
            } else {
                $orderBySql = ' ORDER BY ';
            }
            $orderBySql .= $tableAlias . '.' . $columnName . ' '.$orientation;
        }
        return $orderBySql;
    }
    
    /**
     * Gets the SQL fragment with the list of columns to select when querying for
     * a entity of the type of this persister.
     * 
     * @return string The SQL fragment.
     */
    protected function _getSelectColumnList()
    {
        $columnList = '';
        $tableName = $this->_class->getQuotedTableName($this->_platform);
        $setResultColumnNames = empty($this->_resultColumnNames);
        
        // Add regular columns to select list
        foreach ($this->_class->fieldNames as $field) {
            if ($columnList != '') $columnList .= ', ';
            $columnList .= $tableName . '.' . $this->_class->getQuotedColumnName($field, $this->_platform);
            
            if ($setResultColumnNames) {
                $resultColumnName = $this->_platform->getSqlResultCasing($this->_class->columnNames[$field]);
                $this->_resultColumnNames[$resultColumnName] = $this->_class->columnNames[$field];
            }
        }
        
        // Add join columns (foreign keys) to select list
        foreach ($this->_class->associationMappings as $assoc) {
            if ($assoc->isOwningSide && $assoc->isOneToOne()) {
                foreach ($assoc->targetToSourceKeyColumns as $srcColumn) {
                    $columnList .= ', ' . $assoc->getQuotedJoinColumnName($srcColumn, $this->_platform);
                    
                    if ($setResultColumnNames) {
                        $resultColumnName = $this->_platform->getSqlResultCasing($srcColumn);
                        $this->_resultColumnNames[$resultColumnName] = $srcColumn;
                    }
                }
            }
        }
        
        return $columnList;
    }
    
    /**
     * Gets the SQL to select a collection of entities in a many-many association.
     *
     * @param ManyToManyMapping $manyToMany
     * @param array $criteria
     * @return string
     */
    protected function _getSelectManyToManyEntityCollectionSql($manyToMany, array &$criteria)
    {
        if ($manyToMany->isOwningSide) {
            $owningAssoc = $manyToMany;
            $joinClauses = $manyToMany->relationToTargetKeyColumns;
        } else {
            $owningAssoc = $this->_em->getClassMetadata($manyToMany->targetEntityName)->associationMappings[$manyToMany->mappedByFieldName];
            $joinClauses = $owningAssoc->relationToSourceKeyColumns;
        }
        
        $joinTableName = $owningAssoc->getQuotedJoinTableName($this->_platform);
        
        $joinSql = '';
        foreach ($joinClauses as $joinTableColumn => $sourceColumn) {
            if ($joinSql != '') $joinSql .= ' AND ';
            $joinSql .= $this->_class->getQuotedTableName($this->_platform) .
                    '.' . $this->_class->getQuotedColumnName($this->_class->fieldNames[$sourceColumn], $this->_platform) . ' = '
                    . $joinTableName
                    . '.' . $owningAssoc->getQuotedJoinColumnName($joinTableColumn, $this->_platform);
        }
        
        $joinSql = ' INNER JOIN ' . $joinTableName . ' ON ' . $joinSql;
        
        
        $conditionSql = '';
        foreach ($criteria as $joinColumn => $value) {
            if ($conditionSql != '') $conditionSql .= ' AND ';
            $columnName = $joinTableName . '.' . $owningAssoc->getQuotedJoinColumnName($joinColumn, $this->_platform);
            $conditionSql .= $columnName . ' = ?';
        }

        $orderBySql = '';
        if ($manyToMany->orderBy !== null) {
            $orderBySql = $this->_getCollectionOrderBySql(
                $manyToMany->orderBy, $this->_class->getQuotedTableName($this->_platform)
            );
        }
        
        return 'SELECT ' . $this->_getSelectColumnList() 
             . ' FROM ' . $this->_class->getQuotedTableName($this->_platform)
             . $joinSql
             . ' WHERE ' . $conditionSql . $orderBySql;
    }
    
    /** @override */
    final protected function _processSqlResultInheritanceAware(array $sqlResult)
    {
        $data = array();
        $entityName = $this->_class->name;
        foreach ($sqlResult as $column => $value) {
            $column = isset($this->_resultColumnNames[$column]) ? $this->_resultColumnNames[$column] : $column;
            if (($class = $this->_findDeclaringClass($column)) !== false) {
                $field = $class->fieldNames[$column];
                $data[$field] = Type::getType($class->fieldMappings[$field]['type'])
                        ->convertToPHPValue($value, $this->_platform);
            } else if ($column == $this->_class->discriminatorColumn['name']) {
                $entityName = $this->_class->discriminatorMap[$value];
            } else {
                $data[$column] = $value;
            }
        }
        
        return array($entityName, $data);
    }
    
    /**
     * Gets the INSERT SQL used by the persister to persist entities.
     * 
     * @return string
     */
    public function getInsertSql()
    {
        if ($this->_insertSql === null) {
            $this->_insertSql = $this->_generateInsertSql();
        }
        
        return $this->_insertSql;
    }
    
    /**
     * Gets the list of columns to put in the INSERT SQL statement.
     * 
     * @return array The list of columns.
     */
    protected function _getInsertColumnList()
    {
        $columns = array();
        foreach ($this->_class->reflFields as $name => $field) {
            if ($this->_class->isVersioned && $this->_class->versionField == $name) {
                continue;
            }
            if (isset($this->_class->associationMappings[$name])) {
                $assoc = $this->_class->associationMappings[$name];
                if ($assoc->isOwningSide && $assoc->isOneToOne()) {
                    foreach ($assoc->targetToSourceKeyColumns as $sourceCol) {
                        $columns[] = $assoc->getQuotedJoinColumnName($sourceCol, $this->_platform);
                    }
                }
            } else if ($this->_class->generatorType != ClassMetadata::GENERATOR_TYPE_IDENTITY ||
                    $this->_class->identifier[0] != $name) {
                $columns[] = $this->_class->getQuotedColumnName($name, $this->_platform);
            }
        }
        
        return $columns;
    }
    
    /**
     * Generates the INSERT SQL used by the persister to persist entities.
     * 
     * @return string
     */
    protected function _generateInsertSql()
    {
        $insertSql = '';
        $columns = $this->_getInsertColumnList();
        if (empty($columns)) {
            $insertSql = $this->_platform->getEmptyIdentityInsertSql(
                $this->_class->getQuotedTableName($this->_platform),
                $this->_class->getQuotedColumnName($this->_class->identifier[0], $this->_platform)
            );
        } else {
            $columns = array_unique($columns);
            $values = array_fill(0, count($columns), '?');

            $insertSql = 'INSERT INTO ' . $this->_class->getQuotedTableName($this->_platform)
                    . ' (' . implode(', ', $columns) . ') '
                    . 'VALUES (' . implode(', ', $values) . ')';
        }
        
        return $insertSql;
    }
    
    private function _findDeclaringClass($column)
    {
        static $cache = array();
        
        if (isset($cache[$column])) {
            return $cache[$column];
        }
        
        if (isset($this->_class->fieldNames[$column])) {
            $cache[$column] = $this->_class;
            return $this->_class;
        }
        
        foreach ($this->_class->subClasses as $subClassName) {
            $subClass = $this->_em->getClassMetadata($subClassName);
            if (isset($subClass->fieldNames[$column])) {
                $cache[$column] = $subClass;
                return $subClass;
            }
        }
        
        $cache[$column] = false;
        return false;
    }
}
