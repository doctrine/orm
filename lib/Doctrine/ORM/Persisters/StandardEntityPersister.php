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

use Doctrine\Common\DoctrineException,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\DBAL\Connection,
    Doctrine\DBAL\Types\Type,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\UnitOfWork,
    Doctrine\ORM\PersistentCollection,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Events;

/**
 * Base class for all EntityPersisters. An EntityPersister is a class that knows
 * how to persist (and to some extent how to load) entities of a specific type.
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @author      Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 3406 $
 * @link        www.doctrine-project.org
 * @since       2.0
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
     * The name of the entity the persister is used for.
     *
     * @var string
     */
    protected $_entityName;

    /**
     * The Connection instance.
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
     * Initializes a new instance of a class derived from AbstractEntityPersister
     * that uses the given EntityManager and persists instances of the class described
     * by the given class metadata descriptor.
     * 
     * @param EntityManager $em
     * @param ClassMetadata $class
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        $this->_em = $em;
        $this->_conn = $em->getConnection();
        $this->_platform = $this->_conn->getDatabasePlatform();
        $this->_entityName = $class->name;
        $this->_class = $class;
    }

    /**
     * Adds an entity to the queued insertions.
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

        $stmt = $this->_conn->prepare($this->_class->insertSql);
        $primaryTableName = $this->_class->primaryTable['name'];

        $sqlLogger = $this->_conn->getConfiguration()->getSqlLogger();

        foreach ($this->_queuedInserts as $entity) {
            $insertData = array();
            $this->_prepareData($entity, $insertData, true);

            if (isset($insertData[$primaryTableName])) {
                $paramIndex = 1;
                if ($sqlLogger) {
                    $params = array();
                    foreach ($insertData[$primaryTableName] as $value) {
                        $params[$paramIndex] = $value;
                        $stmt->bindValue($paramIndex++, $value);
                    }
                    $sqlLogger->logSql($this->_class->insertSql, $params);
                } else {
                    foreach ($insertData[$primaryTableName] as $value) {
                        $stmt->bindValue($paramIndex++, $value);
                    }
                }
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

        $sql = "SELECT " . $versionFieldColumnName . " FROM " . $class->getQuotedTableName($this->_platform) .
               " WHERE " . implode(' = ? AND ', $identifier) . " = ?";
        $value = $this->_conn->fetchColumn($sql, (array) $id);
        $this->_class->setFieldValue($entity, $versionField, $value[0]);
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
            $this->_class->identifier,
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
            $where[$this->_class->fieldNames[$versionField]] = Type::getType(
                $this->_class->fieldMappings[$versionField]['type']
            )->convertToDatabaseValue($entity->version, $this->_platform);
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
                . ' WHERE ' . implode(' = ? AND ', array_keys($where))
                . ' = ?';

        $result = $this->_conn->executeUpdate($sql, $params);

        if ($isVersioned && ! $result) {
            throw \Doctrine\ORM\OptimisticLockException::optimisticLockFailed();
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
            $this->_class->getIdentifierFieldNames(),
            $this->_em->getUnitOfWork()->getEntityIdentifier($entity)
        );
        $this->_conn->delete($this->_class->primaryTable['name'], $id);
    }

    /**
     * Adds an entity to delete.
     *
     * @param object $entity
     * @todo Impl.
     */
    public function addDelete($entity)
    {

    }

    /**
     * Executes all pending entity deletions.
     *
     * @see addDelete()
     * @todo Impl.
     */
    public function executeDeletions()
    {

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
                if ( ! $assocMapping->isOneToOne() || $assocMapping->isInverseSide()) {
                    continue;
                }

                // Special case: One-one self-referencing of the same class.                
                if ($newVal !== null && $assocMapping->sourceEntityName == $assocMapping->targetEntityName) {
                    $oid = spl_object_hash($newVal);
                    $isScheduledForInsert = $uow->isScheduledForInsert($newVal);
                    if (isset($this->_queuedInserts[$oid]) || $isScheduledForInsert) {
                        // The associated entity $newVal is not yet persisted, so we must
                        // set $newVal = null, in order to insert a null value and schedule an
                        // extra update on the UnitOfWork.
                        $uow->scheduleExtraUpdate($entity, array(
                            $field => array(null, $newVal)
                        ));
                        $newVal = null;
                    } else if ($isInsert && ! $isScheduledForInsert && $uow->getEntityState($newVal) == UnitOfWork::STATE_MANAGED) {
                        // $newVal is already fully persisted.
                        // Schedule an extra update for it, so that the foreign key(s) are properly set.
                        $uow->scheduleExtraUpdate($newVal, array(
                            $field => array(null, $entity)
                        ));
                    }
                }
                
                foreach ($assocMapping->sourceToTargetKeyColumns as $sourceColumn => $targetColumn) {
                    $quotedSourceColumn = $assocMapping->getQuotedJoinColumnName($sourceColumn, $this->_platform);
                    if ($newVal === null) {
                        $result[$this->getOwningTable($field)][$quotedSourceColumn] = null;
                    } else {
                        $otherClass = $this->_em->getClassMetadata($assocMapping->targetEntityName);
                        $result[$this->getOwningTable($field)][$quotedSourceColumn] =
                                $otherClass->reflFields[$otherClass->fieldNames[$targetColumn]]
                                ->getValue($newVal);
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
     * @return The loaded entity instance or NULL if the entity/the data can not be found.
     */
    public function load(array $criteria, $entity = null)
    {
        $stmt = $this->_conn->prepare($this->_getSelectEntitiesSql($criteria));
        $stmt->execute(array_values($criteria));
        $result = $stmt->fetch(Connection::FETCH_ASSOC);
        $stmt->closeCursor();
        
        return $this->_createEntity($result, $entity);
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
        
        $stmt = $this->_conn->prepare($this->_getSelectEntitiesSql($criteria));
        $stmt->execute(array_values($criteria));
        $result = $stmt->fetchAll(Connection::FETCH_ASSOC);
        $stmt->closeCursor();
        
        foreach ($result as $row) {
            $entities[] = $this->_createEntity($row);
        }
        
        return $entities;
    }
    
    /**
     * Loads a collection of entities into a one-to-many association.
     *
     * @param array $criteria The criteria by which to select the entities.
     * @param PersistentCollection The collection to fill.
     */
    public function loadOneToManyCollection(array $criteria, PersistentCollection $coll)
    {
        $owningAssoc = $this->_class->associationMappings[$coll->getMapping()->mappedByFieldName];
        $stmt = $this->_conn->prepare($this->_getSelectEntitiesSql($criteria, $owningAssoc));
        $stmt->execute(array_values($criteria));
        while ($result = $stmt->fetch(Connection::FETCH_ASSOC)) {
            $coll->hydrateAdd($this->_createEntity($result));
        }
        $stmt->closeCursor();
    }
    
    /**
     * Loads a collection of entities of a many-to-many association.
     *
     * @param array $criteria
     * @param PersistentCollection $coll The collection to fill.
     */
    public function loadManyToManyCollection($assoc, array $criteria, PersistentCollection $coll)
    {
        $stmt = $this->_conn->prepare($this->_getSelectManyToManyEntityCollectionSql($assoc, $criteria));
        $stmt->execute(array_values($criteria));
        while ($result = $stmt->fetch(Connection::FETCH_ASSOC)) {
            $coll->add($this->_createEntity($result));
        }
        $stmt->closeCursor();
    }
    
    /**
     * Creates or fills a single entity object from an SQL result.
     * 
     * @param $result The SQL result.
     * @param $entity The entity object to fill.
     * @return object The filled and managed entity object.
     */
    private function _createEntity($result, $entity = null)
    {
        if ($result === false) {
            return null;
        }

        $data = $joinColumnValues = array();
        $entityName = $this->_entityName;
        
        foreach ($result as $column => $value) {
            $column = $this->_class->resultColumnNames[$column];
            if (isset($this->_class->fieldNames[$column])) {
                $fieldName = $this->_class->fieldNames[$column];
                $data[$fieldName] = Type::getType($this->_class->fieldMappings[$fieldName]['type'])
                        ->convertToPHPValue($value, $this->_platform);
            } else if ($this->_class->discriminatorColumn !== null && $column == $this->_class->discriminatorColumn['name']) {
                $entityName = $this->_class->discriminatorMap[$value];
            } else {
                $joinColumnValues[$column] = $value;
            }
        }

        if ($entity === null) {
            $entity = $this->_em->getUnitOfWork()->createEntity($entityName, $data);
        } else {
            foreach ($data as $field => $value) {
                $this->_class->reflFields[$field]->setValue($entity, $value);
            }
            $id = array();
            if ($this->_class->isIdentifierComposite) {
                foreach ($this->_class->identifier as $fieldName) {
                    $id[] = $data[$fieldName];
                }
            } else {
                $id = array($data[$this->_class->identifier[0]]);
            }
            $this->_em->getUnitOfWork()->registerManaged($entity, $id, $data);
        }

        if ( ! $this->_em->getConfiguration()->getAllowPartialObjects()) {
            // Partial objects not allowed, so make sure we put in proxies and
            // empty collections respectively.
            foreach ($this->_class->associationMappings as $field => $assoc) {
                if ($assoc->isOneToOne()) {
                    if ($assoc->isLazilyFetched()) {
                        // Inject proxy
                        $proxy = $this->_em->getProxyFactory()->getAssociationProxy($entity, $assoc, $joinColumnValues);
                        $this->_class->reflFields[$field]->setValue($entity, $proxy);
                    } else {
                        // Eager load
                        //TODO: Allow more efficient and configurable batching of these loads
                        $assoc->load($entity, new $assoc->targetEntityName, $this->_em, $joinColumnValues);
                    }
                } else {
                    // Inject collection
                    $coll = new PersistentCollection(
                            $this->_em,
                            $this->_em->getClassMetadata($assoc->targetEntityName),
                            /*$this->_class->reflFields[$field]->getValue($entity) ?:*/ new ArrayCollection);
                    $coll->setOwner($entity, $assoc);
                    $this->_class->reflFields[$field]->setValue($entity, $coll);
                    if ($assoc->isLazilyFetched()) {
                        $coll->setInitialized(false);
                    } else {
                        //TODO: Allow more efficient and configurable batching of these loads
                        $assoc->load($entity, $coll, $this->_em);
                    }
                }
            }
        }

        return $entity;
    }

    /**
     * Gets the SELECT SQL to select one or more entities by a set of field criteria.
     *
     * @param array $criteria
     * @return string The SQL.
     */
    protected function _getSelectEntitiesSql(array &$criteria, $assoc = null)
    {
        $columnList = '';
        foreach ($this->_class->fieldNames as $field) {
            if ($columnList != '') $columnList .= ', ';
            $columnList .= $this->_class->getQuotedColumnName($field, $this->_platform);
        }
        
        $joinColumnNames = array();
        if ( ! $this->_em->getConfiguration()->getAllowPartialObjects()) {
            foreach ($this->_class->associationMappings as $assoc) {
                if ($assoc->isOwningSide && $assoc->isOneToOne()) {
                    foreach ($assoc->targetToSourceKeyColumns as $srcColumn) {
                        $joinColumnNames[] = $srcColumn;
                        $columnList .= ', ' . $assoc->getQuotedJoinColumnName($srcColumn, $this->_platform);
                    }
                }
            }
        }

        $joinSql = '';
        $conditionSql = '';
        foreach ($criteria as $field => $value) {
            if ($conditionSql != '') {
                $conditionSql .= ' AND ';
            }
            
            if (isset($this->_class->columnNames[$field])) {
                $conditionSql .= $this->_class->getQuotedColumnName($field, $this->_platform);
            } else if ($assoc !== null) {
                $conditionSql .= $assoc->getQuotedJoinColumnName($field, $this->_platform);
            } else {
                throw DoctrineException::unrecognizedField($field);
            }
            $conditionSql .= ' = ?';
        }

        return 'SELECT ' . $columnList 
             . ' FROM ' . $this->_class->getQuotedTableName($this->_platform)
             . $joinSql
             . ($conditionSql ? ' WHERE ' . $conditionSql : '');
    }
    
    /**
     * Gets the SQL to select a collection of entities in a many-many association.
     * 
     * @param array $criteria
     * @return string
     */
    protected function _getSelectManyToManyEntityCollectionSql($manyToMany, array &$criteria)
    {
        $columnList = '';
        foreach ($this->_class->fieldNames as $field) {
            if ($columnList != '') $columnList .= ', ';
            $columnList .= $this->_class->getQuotedColumnName($field, $this->_platform);
        }
        
        if ( ! $this->_em->getConfiguration()->getAllowPartialObjects()) {
            foreach ($this->_class->associationMappings as $assoc) {
                if ($assoc->isOwningSide && $assoc->isOneToOne()) {
                    foreach ($assoc->targetToSourceKeyColumns as $srcColumn) {
                        $columnList .= ', ' . $assoc->getQuotedJoinColumnName($srcColumn, $this->_platform);
                    }
                }
            }
        }
        
        if ($manyToMany->isOwningSide) {
            $owningAssoc = $manyToMany;
            $joinClauses = $manyToMany->targetToRelationKeyColumns;
        } else {
            $owningAssoc = $this->_em->getClassMetadata($manyToMany->targetEntityName)->associationMappings[$manyToMany->mappedByFieldName];
            $joinClauses = $owningAssoc->sourceToRelationKeyColumns;
        }
        
        $joinTableName = $owningAssoc->getQuotedJoinTableName($this->_platform);
        
        $joinSql = '';
        foreach ($joinClauses as $sourceField => $joinTableField) {
            if ($joinSql != '') $joinSql .= ' AND ';
            $joinSql .= $this->_class->getQuotedTableName($this->_platform) .
                    '.' . $this->_class->getQuotedColumnName($sourceField, $this->_platform) . ' = '
                    . $joinTableName
                    . '.' . $owningAssoc->getQuotedJoinColumnName($joinTableField, $this->_platform);
        }
        
        $joinSql = ' INNER JOIN ' . $joinTableName . ' ON ' . $joinSql;
        
        
        $conditionSql = '';
        foreach ($criteria as $joinColumn => $value) {
            if ($conditionSql != '') $conditionSql .= ' AND ';
            $columnName = $joinTableName . '.' . $owningAssoc->getQuotedJoinColumnName($joinColumn, $this->_platform);
            $conditionSql .= $columnName . ' = ?';
        }
        
        return 'SELECT ' . $columnList 
             . ' FROM ' . $this->_class->getQuotedTableName($this->_platform)
             . $joinSql
             . ' WHERE ' . $conditionSql;
    }
}
