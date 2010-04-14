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

use PDO,
    Doctrine\ORM\ORMException,
    Doctrine\ORM\OptimisticLockException,
    Doctrine\DBAL\Types\Type,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Query,
    Doctrine\ORM\PersistentCollection,
    Doctrine\ORM\Mapping\ClassMetadata;

/**
 * A basic entity persister that maps an entity with no (mapped) inheritance to a single table
 * in the relational database.
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @author      Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
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
     * Queued inserts.
     *
     * @var array
     */
    protected $_queuedInserts = array();

    /**
     * Case-sensitive mappings of column names as they appear in an SQL result set
     * to column names as they are defined in the mapping. This is necessary because different
     * RDBMS vendors return column names in result sets in different casings.
     * 
     * @var array
     */
    protected $_resultColumnNames = array();

    /**
     * The map of column names to DBAL mapping types of all prepared columns used when INSERTing
     * or UPDATEing an entity.
     * 
     * @var array
     * @see _prepareInsertData($entity)
     * @see _prepareUpdateData($entity)
     */
    protected $_columnTypes = array();

    /**
     * The INSERT SQL statement used for entities handled by this persister.
     * This SQL is only generated once per request, if at all.
     * 
     * @var string
     */
    private $_insertSql;

    /**
     * The SELECT column list SQL fragment used for querying entities by this persister.
     * This SQL fragment is only generated once per request, if at all.
     * 
     * @var string
     */
    protected $_selectColumnListSql;

    /**
     * Counter for creating unique SQL table and column aliases.
     * 
     * @var integer
     */
    protected $_sqlAliasCounter = 0;

    /**
     * Map from class names (FQCN) to the corresponding generated SQL table aliases.
     * 
     * @var array
     */
    protected $_sqlTableAliases = array();

    /**
     * Initializes a new <tt>StandardEntityPersister</tt> that uses the given EntityManager
     * and persists instances of the class described by the given ClassMetadata descriptor.
     * 
     * @param Doctrine\ORM\EntityManager $em
     * @param Doctrine\ORM\Mapping\ClassMetadata $class
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        $this->_em = $em;
        $this->_class = $class;
        $this->_conn = $em->getConnection();
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
     * Executes all queued entity insertions and returns any generated post-insert
     * identifiers that were created as a result of the insertions.
     * 
     * If no inserts are queued, invoking this method is a NOOP.
     *
     * @return array An array of any generated post-insert IDs. This will be an empty array
     *               if the entity class does not use the IDENTITY generation strategy.
     */
    public function executeInserts()
    {
        if ( ! $this->_queuedInserts) {
            return;
        }

        $postInsertIds = array();
        $idGen = $this->_class->idGenerator;
        $isPostInsertId = $idGen->isPostInsertGenerator();

        $stmt = $this->_conn->prepare($this->getInsertSQL());
        $tableName = $this->_class->table['name'];

        foreach ($this->_queuedInserts as $entity) {
            $insertData = $this->_prepareInsertData($entity);

            if (isset($insertData[$tableName])) {
                $paramIndex = 1;
                foreach ($insertData[$tableName] as $column => $value) {
                    $stmt->bindValue($paramIndex++, $value, $this->_columnTypes[$column]);
                }
            }

            $stmt->execute();

            if ($isPostInsertId) {
                $id = $idGen->generate($this->_em, $entity);
                $postInsertIds[$id] = $entity;
            } else {
                $id = $this->_class->getIdentifierValues($entity);
            }

            if ($this->_class->isVersioned) {
                $this->_assignDefaultVersionValue($this->_class, $entity, $id);
            }
        }

        $stmt->closeCursor();
        $this->_queuedInserts = array();

        return $postInsertIds;
    }

    /**
     * Retrieves the default version value which was created
     * by the preceding INSERT statement and assigns it back in to the 
     * entities version field.
     *
     * @param $class
     * @param $entity
     * @param $id
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
        $updateData = $this->_prepareUpdateData($entity);
        $tableName = $this->_class->table['name'];
        if (isset($updateData[$tableName]) && $updateData[$tableName]) {
            $this->_updateTable($entity, $tableName, $updateData[$tableName], $this->_class->isVersioned);
        }
    }

    /**
     * Performs an UPDATE statement for an entity on a specific table.
     * The UPDATE can be optionally versioned, which requires the entity to have a version field.
     *
     * @param object $entity The entity object being updated.
     * @param string $tableName The name of the table to apply the UPDATE on.
     * @param array $updateData The map of columns to update (column => value).
     * @param boolean $versioned Whether the UPDATE should be versioned.
     */
    protected function _updateTable($entity, $tableName, $updateData, $versioned = false)
    {
        $set = $params = $types = array();

        foreach ($updateData as $columnName => $value) {
            if (isset($this->_class->fieldNames[$columnName])) {
                $set[] = $this->_class->getQuotedColumnName($this->_class->fieldNames[$columnName], $this->_platform) . ' = ?';
            } else {
                $set[] = $columnName . ' = ?';
            }
            $params[] = $value;
            $types[] = $this->_columnTypes[$columnName];
        }
        
        $where = array();
        $id = $this->_em->getUnitOfWork()->getEntityIdentifier($entity);
        foreach ($this->_class->identifier as $idField) {
            $where[] = $this->_class->getQuotedColumnName($idField, $this->_platform);
            $params[] = $id[$idField];
            $types[] = $this->_class->fieldMappings[$idField]['type'];
        }

        if ($versioned) {
            $versionField = $this->_class->versionField;
            $versionFieldType = $this->_class->getTypeOfField($versionField);
            $versionColumn = $this->_class->getQuotedColumnName($versionField, $this->_platform);
            if ($versionFieldType == Type::INTEGER) {
                $set[] = $versionColumn . ' = ' . $versionColumn . ' + 1';
            } else if ($versionFieldType == Type::DATETIME) {
                $set[] = $versionColumn . ' = CURRENT_TIMESTAMP';
            }
            $where[] = $versionColumn;
            $params[] = $this->_class->reflFields[$versionField]->getValue($entity);
            $types[] = $this->_class->fieldMappings[$versionField]['type'];
        }

        $sql = 'UPDATE ' . $tableName . ' SET ' . implode(', ', $set)
            . ' WHERE ' . implode(' = ? AND ', $where) . ' = ?';

        $result = $this->_conn->executeUpdate($sql, $params, $types);

        if ($this->_class->isVersioned && ! $result) {
            throw OptimisticLockException::lockFailed();
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
        $this->_conn->delete($this->_class->table['name'], $id);
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
     * Prepares the data changeset of an entity for database insertion.
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
     * @return array The prepared data.
     */
    protected function _prepareUpdateData($entity)
    {
        $result = array();
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
                    if ($newVal === null) {
                        $result[$owningTable][$sourceColumn] = null;
                    } else {
                        $result[$owningTable][$sourceColumn] = $newValId[$targetClass->fieldNames[$targetColumn]];
                    }
                    $this->_columnTypes[$sourceColumn] = $targetClass->getTypeOfColumn($targetColumn);
                }
            } else {
                $columnName = $this->_class->columnNames[$field];
                $this->_columnTypes[$columnName] = $this->_class->fieldMappings[$field]['type'];
                $result[$this->getOwningTable($field)][$columnName] = $newVal;
            }
        }
        return $result;
    }

    protected function _prepareInsertData($entity)
    {
        return $this->_prepareUpdateData($entity);
    }

    /**
     * Gets the name of the table that owns the column the given field is mapped to.
     *
     * @param string $fieldName
     * @return string
     */
    public function getOwningTable($fieldName)
    {
        return $this->_class->table['name'];
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
        $sql = $this->_getSelectEntitiesSQL($criteria, $assoc);
        $params = array_values($criteria);
        $stmt = $this->_conn->executeQuery($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $this->_createEntity($result, $entity, $hints);
    }

    /**
     * Refreshes an entity.
     * 
     * @param array $id The identifier of the entity as an associative array from column names to values.
     * @param object $entity The entity to refresh.
     */
    public function refresh(array $id, $entity)
    {
        $sql = $this->_getSelectEntitiesSQL($id);
        $params = array_values($id);

        $stmt = $this->_conn->executeQuery($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $metaColumns = array();
        $newData = array();

        // Refresh simple state
        foreach ($result as $column => $value) {
            $column = $this->_resultColumnNames[$column];
            if (isset($this->_class->fieldNames[$column])) {
                $fieldName = $this->_class->fieldNames[$column];
                $newValue = $this->_conn->convertToPHPValue($value, $this->_class->fieldMappings[$fieldName]['type']);
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
                            if ($assoc->inversedBy) {
                                $inverseAssoc = $targetClass->associationMappings[$assoc->inversedBy];
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
     * Loads a list of entities by a list of field criteria.
     * 
     * @param array $criteria
     * @return array
     */
    public function loadAll(array $criteria = array())
    {
        $entities = array();
        
        $sql = $this->_getSelectEntitiesSQL($criteria);
        $params = array_values($criteria);
        $stmt = $this->_conn->executeQuery($sql, $params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $owningAssoc = $this->_class->associationMappings[$coll->getMapping()->mappedBy];
        $sql = $this->_getSelectEntitiesSQL($criteria, $owningAssoc, $assoc->orderBy);
        $params = array_values($criteria);
        $stmt = $this->_conn->executeQuery($sql, $params);
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
        $sql = $this->_getSelectManyToManyEntityCollectionSQL($assoc, $criteria);
        $params = array_values($criteria);
        $stmt = $this->_conn->executeQuery($sql, $params);
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
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

        list($entityName, $data) = $this->_processSQLResult($result);

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
     *               the second value the prepared data of the entity.
     */
    protected function _processSQLResult(array $sqlResult)
    {
        $data = array();
        foreach ($sqlResult as $column => $value) {
            $column = $this->_resultColumnNames[$column];
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
    protected function _getSelectEntitiesSQL(array &$criteria, $assoc = null, $orderBy = null)
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
                $conditionSql .= $field;
            } else {
                throw ORMException::unrecognizedField($field);
            }
            $conditionSql .= ' = ?';
        }

        $orderBySql = '';
        if ($orderBy !== null) {
            $orderBySql = $this->_getCollectionOrderBySQL(
                $orderBy, $this->_getSQLTableAlias($this->_class)
            );
        }

        return 'SELECT ' . $this->_getSelectColumnListSQL() 
             . ' FROM ' . $this->_class->getQuotedTableName($this->_platform) . ' '
             . $this->_getSQLTableAlias($this->_class)
             . ($conditionSql ? ' WHERE ' . $conditionSql : '') . $orderBySql;
    }

    /**
     * Generate ORDER BY SQL snippet for ordered collections.
     * 
     * @param array $orderBy
     * @param string $baseTableAlias
     * @return string
     */
    protected function _getCollectionOrderBySQL(array $orderBy, $baseTableAlias)
    {
        $orderBySql = '';
        foreach ($orderBy as $fieldName => $orientation) {
            if ( ! isset($this->_class->fieldMappings[$fieldName])) {
                ORMException::unrecognizedField($fieldName);
            }

            $tableAlias = isset($this->_class->fieldMappings[$fieldName]['inherited']) ?
                    $this->_getSQLTableAlias($this->_em->getClassMetadata($this->_class->fieldMappings[$fieldName]['inherited']))
                    : $baseTableAlias;

            $columnName = $this->_class->getQuotedColumnName($fieldName, $this->_platform);
            if ($orderBySql != '') {
                $orderBySql .= ', ';
            } else {
                $orderBySql = ' ORDER BY ';
            }
            $orderBySql .= $tableAlias . '.' . $columnName . ' ' . $orientation;
        }

        return $orderBySql;
    }
    
    /**
     * Gets the SQL fragment with the list of columns to select when querying for
     * an entity within this persister.
     * 
     * @return string The SQL fragment.
     * @todo Rename: _getSelectColumnListSQL()
     */
    protected function _getSelectColumnListSQL()
    {
        if ($this->_selectColumnListSql !== null) {
            return $this->_selectColumnListSql;
        }

        $columnList = '';

        // Add regular columns to select list
        foreach ($this->_class->fieldNames as $field) {
            if ($columnList != '') $columnList .= ', ';
            $columnList .= $this->_getSelectColumnSQL($field, $this->_class);
        }

        $this->_selectColumnListSql = $columnList . $this->_getSelectJoinColumnsSQL($this->_class);

        return $this->_selectColumnListSql;
    }
    
    /**
     * Gets the SQL to select a collection of entities in a many-many association.
     *
     * @param ManyToManyMapping $manyToMany
     * @param array $criteria
     * @return string
     */
    protected function _getSelectManyToManyEntityCollectionSQL($manyToMany, array &$criteria)
    {
        if ($manyToMany->isOwningSide) {
            $owningAssoc = $manyToMany;
            $joinClauses = $manyToMany->relationToTargetKeyColumns;
        } else {
            $owningAssoc = $this->_em->getClassMetadata($manyToMany->targetEntityName)->associationMappings[$manyToMany->mappedBy];
            $joinClauses = $owningAssoc->relationToSourceKeyColumns;
        }
        
        $joinTableName = $owningAssoc->getQuotedJoinTableName($this->_platform);
        
        $joinSql = '';
        foreach ($joinClauses as $joinTableColumn => $sourceColumn) {
            if ($joinSql != '') $joinSql .= ' AND ';
            $joinSql .= $this->_getSQLTableAlias($this->_class) .
                    '.' . $this->_class->getQuotedColumnName($this->_class->fieldNames[$sourceColumn], $this->_platform) . ' = '
                    . $joinTableName . '.' . $joinTableColumn;
        }

        $joinSql = ' INNER JOIN ' . $joinTableName . ' ON ' . $joinSql;

        $conditionSql = '';
        foreach ($criteria as $joinColumn => $value) {
            if ($conditionSql != '') $conditionSql .= ' AND ';
            $columnName = $joinTableName . '.' . $joinColumn;
            $conditionSql .= $columnName . ' = ?';
        }

        $orderBySql = '';
        if ($manyToMany->orderBy !== null) {
            $orderBySql = $this->_getCollectionOrderBySQL(
                $manyToMany->orderBy, $this->_getSQLTableAlias($this->_class)
            );
        }

        return 'SELECT ' . $this->_getSelectColumnListSQL()
             . ' FROM ' . $this->_class->getQuotedTableName($this->_platform) . ' '
             . $this->_getSQLTableAlias($this->_class)
             . $joinSql
             . ' WHERE ' . $conditionSql . $orderBySql;
    }

    /**
     * Gets the INSERT SQL used by the persister to persist a new entity.
     * 
     * @return string
     */
    public function getInsertSQL()
    {
        if ($this->_insertSql === null) {
            $this->_insertSql = $this->_generateInsertSQL();
        }
        return $this->_insertSql;
    }

    /**
     * Gets the list of columns to put in the INSERT SQL statement.
     * 
     * @return array The list of columns.
     * @internal INSERT SQL is cached by getInsertSQL() per request.
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
                        $columns[] = $sourceCol;
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
     * @internal Result is cached by getInsertSQL() per request.
     */
    protected function _generateInsertSQL()
    {
        $insertSql = '';
        $columns = $this->_getInsertColumnList();
        if (empty($columns)) {
            $insertSql = $this->_platform->getEmptyIdentityInsertSQL(
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

    /**
     * Gets the SQL snippet of a qualified column name for the given field name.
     *
     * @param string $field The field name.
     * @param ClassMetadata $class The class that declares this field. The table this class is
     *                             mapped to must own the column for the given field.
     */
    protected function _getSelectColumnSQL($field, ClassMetadata $class)
    {
        $columnName = $class->columnNames[$field];
        $sql = $this->_getSQLTableAlias($class) . '.' . $class->getQuotedColumnName($field, $this->_platform);
        $columnAlias = $this->_platform->getSQLResultCasing($columnName . $this->_sqlAliasCounter++);
        if ( ! isset($this->_resultColumnNames[$columnAlias])) {
            $this->_resultColumnNames[$columnAlias] = $columnName;
        }

        return "$sql AS $columnAlias";
    }

    /**
     * Gets the SQL snippet for all join columns of the given class that are to be
     * placed in an SQL SELECT statement.
     * 
     * @return string
     */
    protected function _getSelectJoinColumnsSQL(ClassMetadata $class)
    {
        $sql = '';
        foreach ($class->associationMappings as $assoc) {
            if ($assoc->isOwningSide && $assoc->isOneToOne()) {
                foreach ($assoc->targetToSourceKeyColumns as $srcColumn) {
                    $columnAlias = $srcColumn . $this->_sqlAliasCounter++;
                    $sql .= ', ' . $this->_getSQLTableAlias($this->_class) . ".$srcColumn AS $columnAlias";
                    $resultColumnName = $this->_platform->getSQLResultCasing($columnAlias);
                    if ( ! isset($this->_resultColumnNames[$resultColumnName])) {
                        $this->_resultColumnNames[$resultColumnName] = $srcColumn;
                    }
                }
            }
        }

        return $sql;
    }

    /**
     * Gets the SQL table alias for the given class.
     * 
     * @param ClassMetadata $class
     * @return string The SQL table alias.
     */
    protected function _getSQLTableAlias(ClassMetadata $class)
    {
        if (isset($this->_sqlTableAliases[$class->name])) {
            return $this->_sqlTableAliases[$class->name];
        }
        $tableAlias = $class->table['name'][0] . $this->_sqlAliasCounter++;
        $this->_sqlTableAliases[$class->name] = $tableAlias;

        return $tableAlias;
    }
}
