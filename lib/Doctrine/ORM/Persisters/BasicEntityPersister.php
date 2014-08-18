<?php
/*
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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Persisters;

use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;

use Doctrine\ORM\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Query;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\ClassMetadata;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;

/**
 * A BasicEntityPersister maps an entity to a single table in a relational database.
 *
 * A persister is always responsible for a single entity type.
 *
 * EntityPersisters are used during a UnitOfWork to apply any changes to the persistent
 * state of entities onto a relational database when the UnitOfWork is committed,
 * as well as for basic querying of entities and their associations (not DQL).
 *
 * The persisting operations that are invoked during a commit of a UnitOfWork to
 * persist the persistent entity state are:
 *
 *   - {@link addInsert} : To schedule an entity for insertion.
 *   - {@link executeInserts} : To execute all scheduled insertions.
 *   - {@link update} : To update the persistent state of an entity.
 *   - {@link delete} : To delete the persistent state of an entity.
 *
 * As can be seen from the above list, insertions are batched and executed all at once
 * for increased efficiency.
 *
 * The querying operations invoked during a UnitOfWork, either through direct find
 * requests or lazy-loading, are the following:
 *
 *   - {@link load} : Loads (the state of) a single, managed entity.
 *   - {@link loadAll} : Loads multiple, managed entities.
 *   - {@link loadOneToOneEntity} : Loads a one/many-to-one entity association (lazy-loading).
 *   - {@link loadOneToManyCollection} : Loads a one-to-many entity association (lazy-loading).
 *   - {@link loadManyToManyCollection} : Loads a many-to-many entity association (lazy-loading).
 *
 * The BasicEntityPersister implementation provides the default behavior for
 * persisting and querying entities that are mapped to a single database table.
 *
 * Subclasses can be created to provide custom persisting and querying strategies,
 * i.e. spanning multiple tables.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Alexander <iam.asm89@gmail.com>
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since 2.0
 */
class BasicEntityPersister implements EntityPersister
{
    /**
     * @var array
     */
    static private $comparisonMap = array(
        Comparison::EQ       => '= %s',
        Comparison::IS       => '= %s',
        Comparison::NEQ      => '!= %s',
        Comparison::GT       => '> %s',
        Comparison::GTE      => '>= %s',
        Comparison::LT       => '< %s',
        Comparison::LTE      => '<= %s',
        Comparison::IN       => 'IN (%s)',
        Comparison::NIN      => 'NOT IN (%s)',
        Comparison::CONTAINS => 'LIKE %s',
    );

    /**
     * Metadata object that describes the mapping of the mapped entity class.
     *
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $class;

    /**
     * The underlying DBAL Connection of the used EntityManager.
     *
     * @var \Doctrine\DBAL\Connection $conn
     */
    protected $conn;

    /**
     * The database platform.
     *
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected $platform;

    /**
     * The EntityManager instance.
     *
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * Queued inserts.
     *
     * @var array
     */
    protected $queuedInserts = array();

    /**
     * ResultSetMapping that is used for all queries. Is generated lazily once per request.
     *
     * TODO: Evaluate Caching in combination with the other cached SQL snippets.
     *
     * @var Query\ResultSetMapping
     */
    protected $rsm;

    /**
     * The map of column names to DBAL mapping types of all prepared columns used
     * when INSERTing or UPDATEing an entity.
     *
     * @var array
     *
     * @see prepareInsertData($entity)
     * @see prepareUpdateData($entity)
     */
    protected $columnTypes = array();

    /**
     * The map of quoted column names.
     *
     * @var array
     *
     * @see prepareInsertData($entity)
     * @see prepareUpdateData($entity)
     */
    protected $quotedColumns = array();

    /**
     * The INSERT SQL statement used for entities handled by this persister.
     * This SQL is only generated once per request, if at all.
     *
     * @var string
     */
    private $insertSql;

    /**
     * The SELECT column list SQL fragment used for querying entities by this persister.
     * This SQL fragment is only generated once per request, if at all.
     *
     * @var string
     */
    protected $selectColumnListSql;

    /**
     * The JOIN SQL fragment used to eagerly load all many-to-one and one-to-one
     * associations configured as FETCH_EAGER, as well as all inverse one-to-one associations.
     *
     * @var string
     */
    protected $selectJoinSql;

    /**
     * Counter for creating unique SQL table and column aliases.
     *
     * @var integer
     */
    protected $sqlAliasCounter = 0;

    /**
     * Map from class names (FQCN) to the corresponding generated SQL table aliases.
     *
     * @var array
     */
    protected $sqlTableAliases = array();

    /**
     * The quote strategy.
     *
     * @var \Doctrine\ORM\Mapping\QuoteStrategy
     */
    protected $quoteStrategy;

    /**
     * Initializes a new <tt>BasicEntityPersister</tt> that uses the given EntityManager
     * and persists instances of the class described by the given ClassMetadata descriptor.
     *
     * @param \Doctrine\ORM\EntityManager         $em
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        $this->em               = $em;
        $this->class            = $class;
        $this->conn             = $em->getConnection();
        $this->platform         = $this->conn->getDatabasePlatform();
        $this->quoteStrategy    = $em->getConfiguration()->getQuoteStrategy();
    }

    /**
     * {@inheritdoc}
     */
    public function getClassMetadata()
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function getResultSetMapping()
    {
        return $this->rsm;
    }

    /**
     * {@inheritdoc}
     */
    public function addInsert($entity)
    {
        $this->queuedInserts[spl_object_hash($entity)] = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getInserts()
    {
        return $this->queuedInserts;
    }

    /**
     * {@inheritdoc}
     */
    public function executeInserts()
    {
        if ( ! $this->queuedInserts) {
            return array();
        }

        $postInsertIds  = array();
        $idGenerator    = $this->class->idGenerator;
        $isPostInsertId = $idGenerator->isPostInsertGenerator();

        $stmt       = $this->conn->prepare($this->getInsertSQL());
        $tableName  = $this->class->getTableName();

        foreach ($this->queuedInserts as $entity) {
            $insertData = $this->prepareInsertData($entity);

            if (isset($insertData[$tableName])) {
                $paramIndex = 1;

                foreach ($insertData[$tableName] as $column => $value) {
                    $stmt->bindValue($paramIndex++, $value, $this->columnTypes[$column]);
                }
            }

            $stmt->execute();

            if ($isPostInsertId) {
                $id = $idGenerator->generate($this->em, $entity);
                $postInsertIds[$id] = $entity;
            } else {
                $id = $this->class->getIdentifierValues($entity);
            }

            if ($this->class->isVersioned) {
                $this->assignDefaultVersionValue($entity, $id);
            }
        }

        $stmt->closeCursor();
        $this->queuedInserts = array();

        return $postInsertIds;
    }

    /**
     * Retrieves the default version value which was created
     * by the preceding INSERT statement and assigns it back in to the
     * entities version field.
     *
     * @param object $entity
     * @param mixed  $id
     *
     * @return void
     */
    protected function assignDefaultVersionValue($entity, $id)
    {
        $value = $this->fetchVersionValue($this->class, $id);

        $this->class->setFieldValue($entity, $this->class->versionField, $value);
    }

    /**
     * Fetches the current version value of a versioned entity.
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $versionedClass
     * @param mixed                               $id
     *
     * @return mixed
     */
    protected function fetchVersionValue($versionedClass, $id)
    {
        $versionField   = $versionedClass->versionField;
        $tableName      = $this->quoteStrategy->getTableName($versionedClass, $this->platform);
        $identifier     = $this->quoteStrategy->getIdentifierColumnNames($versionedClass, $this->platform);
        $columnName     = $this->quoteStrategy->getColumnName($versionField, $versionedClass, $this->platform);

        // FIXME: Order with composite keys might not be correct
        $sql = 'SELECT ' . $columnName
             . ' FROM '  . $tableName
             . ' WHERE ' . implode(' = ? AND ', $identifier) . ' = ?';

        $value = $this->conn->fetchColumn($sql, array_values((array) $id));

        return Type::getType($versionedClass->fieldMappings[$versionField]['type'])->convertToPHPValue($value, $this->platform);
    }

    /**
     * {@inheritdoc}
     */
    public function update($entity)
    {
        $tableName  = $this->class->getTableName();
        $updateData = $this->prepareUpdateData($entity);

        if ( ! isset($updateData[$tableName]) || ! ($data = $updateData[$tableName])) {
            return;
        }

        $isVersioned     = $this->class->isVersioned;
        $quotedTableName = $this->quoteStrategy->getTableName($this->class, $this->platform);

        $this->updateTable($entity, $quotedTableName, $data, $isVersioned);

        if ($isVersioned) {
            $id = $this->em->getUnitOfWork()->getEntityIdentifier($entity);

            $this->assignDefaultVersionValue($entity, $id);
        }
    }

    /**
     * Performs an UPDATE statement for an entity on a specific table.
     * The UPDATE can optionally be versioned, which requires the entity to have a version field.
     *
     * @param object  $entity          The entity object being updated.
     * @param string  $quotedTableName The quoted name of the table to apply the UPDATE on.
     * @param array   $updateData      The map of columns to update (column => value).
     * @param boolean $versioned       Whether the UPDATE should be versioned.
     *
     * @return void
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected final function updateTable($entity, $quotedTableName, array $updateData, $versioned = false)
    {
        $set    = array();
        $types  = array();
        $params = array();

        foreach ($updateData as $columnName => $value) {
            $placeholder = '?';
            $column      = $columnName;

            switch (true) {
                case isset($this->class->fieldNames[$columnName]):
                    $fieldName  = $this->class->fieldNames[$columnName];
                    $column     = $this->quoteStrategy->getColumnName($fieldName, $this->class, $this->platform);

                    if (isset($this->class->fieldMappings[$fieldName]['requireSQLConversion'])) {
                        $type        = Type::getType($this->columnTypes[$columnName]);
                        $placeholder = $type->convertToDatabaseValueSQL('?', $this->platform);
                    }

                    break;

                case isset($this->quotedColumns[$columnName]):
                    $column = $this->quotedColumns[$columnName];

                    break;
            }

            $params[]   = $value;
            $set[]      = $column . ' = ' . $placeholder;
            $types[]    = $this->columnTypes[$columnName];
        }

        $where      = array();
        $identifier = $this->em->getUnitOfWork()->getEntityIdentifier($entity);

        foreach ($this->class->identifier as $idField) {
            if ( ! isset($this->class->associationMappings[$idField])) {

                $params[]   = $identifier[$idField];
                $types[]    = $this->class->fieldMappings[$idField]['type'];
                $where[]    = $this->quoteStrategy->getColumnName($idField, $this->class, $this->platform);

                continue;
            }

            $params[]       = $identifier[$idField];
            $where[]        = $this->class->associationMappings[$idField]['joinColumns'][0]['name'];
            $targetMapping  = $this->em->getClassMetadata($this->class->associationMappings[$idField]['targetEntity']);

            switch (true) {
                case (isset($targetMapping->fieldMappings[$targetMapping->identifier[0]])):
                    $types[] = $targetMapping->fieldMappings[$targetMapping->identifier[0]]['type'];
                    break;

                case (isset($targetMapping->associationMappings[$targetMapping->identifier[0]])):
                    $types[] = $targetMapping->associationMappings[$targetMapping->identifier[0]]['type'];
                    break;

                default:
                    throw ORMException::unrecognizedField($targetMapping->identifier[0]);
            }

        }

        if ($versioned) {
            $versionField       = $this->class->versionField;
            $versionFieldType   = $this->class->fieldMappings[$versionField]['type'];
            $versionColumn      = $this->quoteStrategy->getColumnName($versionField, $this->class, $this->platform);

            $where[]    = $versionColumn;
            $types[]    = $this->class->fieldMappings[$versionField]['type'];
            $params[]   = $this->class->reflFields[$versionField]->getValue($entity);

            switch ($versionFieldType) {
                case Type::SMALLINT:
                case Type::INTEGER:
                case Type::BIGINT:
                    $set[] = $versionColumn . ' = ' . $versionColumn . ' + 1';
                    break;

                case Type::DATETIME:
                    $set[] = $versionColumn . ' = CURRENT_TIMESTAMP';
                    break;
            }
        }

        $sql = 'UPDATE ' . $quotedTableName
             . ' SET ' . implode(', ', $set)
             . ' WHERE ' . implode(' = ? AND ', $where) . ' = ?';

        $result = $this->conn->executeUpdate($sql, $params, $types);

        if ($versioned && ! $result) {
            throw OptimisticLockException::lockFailed($entity);
        }
    }

    /**
     * @todo Add check for platform if it supports foreign keys/cascading.
     *
     * @param array $identifier
     *
     * @return void
     */
    protected function deleteJoinTableRecords($identifier)
    {
        foreach ($this->class->associationMappings as $mapping) {
            if ($mapping['type'] !== ClassMetadata::MANY_TO_MANY) {
                continue;
            }

            // @Todo this only covers scenarios with no inheritance or of the same level. Is there something
            // like self-referential relationship between different levels of an inheritance hierarchy? I hope not!
            $selfReferential = ($mapping['targetEntity'] == $mapping['sourceEntity']);
            $class           = $this->class;
            $association     = $mapping;
            $otherColumns    = array();
            $otherKeys       = array();
            $keys            = array();

            if ( ! $mapping['isOwningSide']) {
                $class       = $this->em->getClassMetadata($mapping['targetEntity']);
                $association = $class->associationMappings[$mapping['mappedBy']];
            }

            $joinColumns = $mapping['isOwningSide']
                ? $association['joinTable']['joinColumns']
                : $association['joinTable']['inverseJoinColumns'];


            if ($selfReferential) {
                $otherColumns = (! $mapping['isOwningSide'])
                    ? $association['joinTable']['joinColumns']
                    : $association['joinTable']['inverseJoinColumns'];
            }

            foreach ($joinColumns as $joinColumn) {
                $keys[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
            }

            foreach ($otherColumns as $joinColumn) {
                $otherKeys[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
            }

            if (isset($mapping['isOnDeleteCascade'])) {
                continue;
            }

            $joinTableName = $this->quoteStrategy->getJoinTableName($association, $this->class, $this->platform);

            $this->conn->delete($joinTableName, array_combine($keys, $identifier));

            if ($selfReferential) {
                $this->conn->delete($joinTableName, array_combine($otherKeys, $identifier));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity)
    {
        $class      = $this->class;
        $em         = $this->em;

        $identifier = $this->em->getUnitOfWork()->getEntityIdentifier($entity);
        $tableName  = $this->quoteStrategy->getTableName($class, $this->platform);
        $idColumns  = $this->quoteStrategy->getIdentifierColumnNames($class, $this->platform);
        $id         = array_combine($idColumns, $identifier);
        $types      = array_map(function ($identifier) use ($class, $em) {

            if (isset($class->fieldMappings[$identifier])) {
                return $class->fieldMappings[$identifier]['type'];
            }

            $targetMapping = $em->getClassMetadata($class->associationMappings[$identifier]['targetEntity']);

            if (isset($targetMapping->fieldMappings[$targetMapping->identifier[0]])) {
                return $targetMapping->fieldMappings[$targetMapping->identifier[0]]['type'];
            }

            if (isset($targetMapping->associationMappings[$targetMapping->identifier[0]])) {
                return $targetMapping->associationMappings[$targetMapping->identifier[0]]['type'];
            }

            throw ORMException::unrecognizedField($targetMapping->identifier[0]);

        }, $class->identifier);

        $this->deleteJoinTableRecords($identifier);
        $this->conn->delete($tableName, $id, $types);
    }

    /**
     * Prepares the changeset of an entity for database insertion (UPDATE).
     *
     * The changeset is obtained from the currently running UnitOfWork.
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
     * @param object $entity The entity for which to prepare the data.
     *
     * @return array The prepared data.
     */
    protected function prepareUpdateData($entity)
    {
        $result = array();
        $uow    = $this->em->getUnitOfWork();

        if (($versioned = $this->class->isVersioned) != false) {
            $versionField = $this->class->versionField;
        }

        foreach ($uow->getEntityChangeSet($entity) as $field => $change) {
            if ($versioned && $versionField == $field) {
                continue;
            }

            $newVal = $change[1];

            if ( ! isset($this->class->associationMappings[$field])) {

                $columnName = $this->class->columnNames[$field];
                $this->columnTypes[$columnName] = $this->class->fieldMappings[$field]['type'];
                $result[$this->getOwningTable($field)][$columnName] = $newVal;

                continue;
            }

            $assoc = $this->class->associationMappings[$field];

            // Only owning side of x-1 associations can have a FK column.
            if ( ! $assoc['isOwningSide'] || ! ($assoc['type'] & ClassMetadata::TO_ONE)) {
                continue;
            }

            if ($newVal !== null) {
                $oid = spl_object_hash($newVal);

                if (isset($this->queuedInserts[$oid]) || $uow->isScheduledForInsert($newVal)) {
                    // The associated entity $newVal is not yet persisted, so we must
                    // set $newVal = null, in order to insert a null value and schedule an
                    // extra update on the UnitOfWork.
                    $uow->scheduleExtraUpdate($entity, array($field => array(null, $newVal)));

                    $newVal = null;
                }
            }

            if ($newVal !== null) {
                $newValId = $uow->getEntityIdentifier($newVal);
            }

            $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);
            $owningTable = $this->getOwningTable($field);

            foreach ($assoc['joinColumns'] as $joinColumn) {
                $sourceColumn = $joinColumn['name'];
                $targetColumn = $joinColumn['referencedColumnName'];
                $quotedColumn = $this->quoteStrategy->getJoinColumnName($joinColumn, $this->class, $this->platform);

                $this->quotedColumns[$sourceColumn] = $quotedColumn;
                $this->columnTypes[$sourceColumn]   = $targetClass->getTypeOfColumn($targetColumn);

                switch (true) {
                    case $newVal === null:
                        $value = null;
                        break;

                    case $targetClass->containsForeignIdentifier:
                        $value = $newValId[$targetClass->getFieldForColumn($targetColumn)];
                        break;

                    default:
                        $value = $newValId[$targetClass->fieldNames[$targetColumn]];
                        break;
                }

                $result[$owningTable][$sourceColumn] = $value;
            }
        }

        return $result;
    }

    /**
     * Prepares the data changeset of a managed entity for database insertion (initial INSERT).
     * The changeset of the entity is obtained from the currently running UnitOfWork.
     *
     * The default insert data preparation is the same as for updates.
     *
     * @param object $entity The entity for which to prepare the data.
     *
     * @return array The prepared data for the tables to update.
     *
     * @see prepareUpdateData
     */
    protected function prepareInsertData($entity)
    {
        return $this->prepareUpdateData($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getOwningTable($fieldName)
    {
        return $this->class->getTableName();
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $criteria, $entity = null, $assoc = null, array $hints = array(), $lockMode = null, $limit = null, array $orderBy = null)
    {
        $sql = $this->getSelectSQL($criteria, $assoc, $lockMode, $limit, null, $orderBy);
        list($params, $types) = $this->expandParameters($criteria);
        $stmt = $this->conn->executeQuery($sql, $params, $types);

        if ($entity !== null) {
            $hints[Query::HINT_REFRESH]         = true;
            $hints[Query::HINT_REFRESH_ENTITY]  = $entity;
        }

        $hydrator = $this->em->newHydrator($this->selectJoinSql ? Query::HYDRATE_OBJECT : Query::HYDRATE_SIMPLEOBJECT);
        $entities = $hydrator->hydrateAll($stmt, $this->rsm, $hints);

        return $entities ? $entities[0] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function loadById(array $identifier, $entity = null)
    {
        return $this->load($identifier, $entity);
    }

    /**
     * {@inheritdoc}
     */
    public function loadOneToOneEntity(array $assoc, $sourceEntity, array $identifier = array())
    {
        if (($foundEntity = $this->em->getUnitOfWork()->tryGetById($identifier, $assoc['targetEntity'])) != false) {
            return $foundEntity;
        }

        $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

        if ($assoc['isOwningSide']) {
            $isInverseSingleValued = $assoc['inversedBy'] && ! $targetClass->isCollectionValuedAssociation($assoc['inversedBy']);

            // Mark inverse side as fetched in the hints, otherwise the UoW would
            // try to load it in a separate query (remember: to-one inverse sides can not be lazy).
            $hints = array();

            if ($isInverseSingleValued) {
                $hints['fetched']["r"][$assoc['inversedBy']] = true;
            }

            /* cascade read-only status
            if ($this->em->getUnitOfWork()->isReadOnly($sourceEntity)) {
                $hints[Query::HINT_READ_ONLY] = true;
            }
            */

            $targetEntity = $this->load($identifier, null, $assoc, $hints);

            // Complete bidirectional association, if necessary
            if ($targetEntity !== null && $isInverseSingleValued) {
                $targetClass->reflFields[$assoc['inversedBy']]->setValue($targetEntity, $sourceEntity);
            }

            return $targetEntity;
        }

        $sourceClass = $this->em->getClassMetadata($assoc['sourceEntity']);
        $owningAssoc = $targetClass->getAssociationMapping($assoc['mappedBy']);

        // TRICKY: since the association is specular source and target are flipped
        foreach ($owningAssoc['targetToSourceKeyColumns'] as $sourceKeyColumn => $targetKeyColumn) {
            if ( ! isset($sourceClass->fieldNames[$sourceKeyColumn])) {
                throw MappingException::joinColumnMustPointToMappedField(
                    $sourceClass->name, $sourceKeyColumn
                );
            }

            // unset the old value and set the new sql aliased value here. By definition
            // unset($identifier[$targetKeyColumn] works here with how UnitOfWork::createEntity() calls this method.
            $identifier[$this->getSQLTableAlias($targetClass->name) . "." . $targetKeyColumn] =
                $sourceClass->reflFields[$sourceClass->fieldNames[$sourceKeyColumn]]->getValue($sourceEntity);

            unset($identifier[$targetKeyColumn]);
        }

        $targetEntity = $this->load($identifier, null, $assoc);

        if ($targetEntity !== null) {
            $targetClass->setFieldValue($targetEntity, $assoc['mappedBy'], $sourceEntity);
        }

        return $targetEntity;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh(array $id, $entity, $lockMode = null)
    {
        $sql = $this->getSelectSQL($id, null, $lockMode);
        list($params, $types) = $this->expandParameters($id);
        $stmt = $this->conn->executeQuery($sql, $params, $types);

        $hydrator = $this->em->newHydrator(Query::HYDRATE_OBJECT);
        $hydrator->hydrateAll($stmt, $this->rsm, array(Query::HINT_REFRESH => true));
    }

    /**
     * {@inheritDoc}
     */
    public function count($criteria = array())
    {
        $sql = $this->getCountSQL($criteria);

        list($params, $types) = ($criteria instanceof Criteria)
            ? $this->expandCriteriaParameters($criteria)
            : $this->expandParameters($criteria);

        return $this->conn->executeQuery($sql, $params, $types)->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function loadCriteria(Criteria $criteria)
    {
        $orderBy = $criteria->getOrderings();
        $limit   = $criteria->getMaxResults();
        $offset  = $criteria->getFirstResult();
        $query   = $this->getSelectSQL($criteria, null, null, $limit, $offset, $orderBy);

        list($params, $types) = $this->expandCriteriaParameters($criteria);

        $stmt       = $this->conn->executeQuery($query, $params, $types);
        $hydrator   = $this->em->newHydrator(($this->selectJoinSql) ? Query::HYDRATE_OBJECT : Query::HYDRATE_SIMPLEOBJECT);

        return $hydrator->hydrateAll($stmt, $this->rsm, array(UnitOfWork::HINT_DEFEREAGERLOAD => true));
    }

    /**
     * {@inheritdoc}
     */
    public function expandCriteriaParameters(Criteria $criteria)
    {
        $expression = $criteria->getWhereExpression();
        $sqlParams  = array();
        $sqlTypes   = array();

        if ($expression === null) {
            return array($sqlParams, $sqlTypes);
        }

        $valueVisitor = new SqlValueVisitor();

        $valueVisitor->dispatch($expression);

        list($params, $types) = $valueVisitor->getParamsAndTypes();

        foreach ($params as $param) {
            $sqlParams = array_merge($sqlParams, $this->getValues($param));
        }

        foreach ($types as $type) {
            list($field, $value) = $type;
            $sqlTypes = array_merge($sqlTypes, $this->getTypes($field, $value));
        }

        return array($sqlParams, $sqlTypes);
    }

    /**
     * {@inheritdoc}
     */
    public function loadAll(array $criteria = array(), array $orderBy = null, $limit = null, $offset = null)
    {
        $sql = $this->getSelectSQL($criteria, null, null, $limit, $offset, $orderBy);
        list($params, $types) = $this->expandParameters($criteria);
        $stmt = $this->conn->executeQuery($sql, $params, $types);

        $hydrator = $this->em->newHydrator(($this->selectJoinSql) ? Query::HYDRATE_OBJECT : Query::HYDRATE_SIMPLEOBJECT);

        return $hydrator->hydrateAll($stmt, $this->rsm, array(UnitOfWork::HINT_DEFEREAGERLOAD => true));
    }

    /**
     * {@inheritdoc}
     */
    public function getManyToManyCollection(array $assoc, $sourceEntity, $offset = null, $limit = null)
    {
        $stmt = $this->getManyToManyStatement($assoc, $sourceEntity, $offset, $limit);

        return $this->loadArrayFromStatement($assoc, $stmt);
    }

    /**
     * Loads an array of entities from a given DBAL statement.
     *
     * @param array                    $assoc
     * @param \Doctrine\DBAL\Statement $stmt
     *
     * @return array
     */
    private function loadArrayFromStatement($assoc, $stmt)
    {
        $rsm    = $this->rsm;
        $hints  = array(UnitOfWork::HINT_DEFEREAGERLOAD => true);

        if (isset($assoc['indexBy'])) {
            $rsm = clone ($this->rsm); // this is necessary because the "default rsm" should be changed.
            $rsm->addIndexBy('r', $assoc['indexBy']);
        }

        return $this->em->newHydrator(Query::HYDRATE_OBJECT)->hydrateAll($stmt, $rsm, $hints);
    }

    /**
     * Hydrates a collection from a given DBAL statement.
     *
     * @param array                    $assoc
     * @param \Doctrine\DBAL\Statement $stmt
     * @param PersistentCollection     $coll
     *
     * @return array
     */
    private function loadCollectionFromStatement($assoc, $stmt, $coll)
    {
        $rsm   = $this->rsm;
        $hints = array(
            UnitOfWork::HINT_DEFEREAGERLOAD => true,
            'collection' => $coll
        );

        if (isset($assoc['indexBy'])) {
            $rsm = clone ($this->rsm); // this is necessary because the "default rsm" should be changed.
            $rsm->addIndexBy('r', $assoc['indexBy']);
        }

        return $this->em->newHydrator(Query::HYDRATE_OBJECT)->hydrateAll($stmt, $rsm, $hints);
    }

    /**
     * {@inheritdoc}
     */
    public function loadManyToManyCollection(array $assoc, $sourceEntity, PersistentCollection $coll)
    {
        $stmt = $this->getManyToManyStatement($assoc, $sourceEntity);

        return $this->loadCollectionFromStatement($assoc, $stmt, $coll);
    }

    /**
     * @param array    $assoc
     * @param object   $sourceEntity
     * @param int|null $offset
     * @param int|null $limit
     *
     * @return \Doctrine\DBAL\Driver\Statement
     *
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    private function getManyToManyStatement(array $assoc, $sourceEntity, $offset = null, $limit = null)
    {
        $sourceClass    = $this->em->getClassMetadata($assoc['sourceEntity']);
        $class          = $sourceClass;
        $association    = $assoc;
        $criteria       = array();


        if ( ! $assoc['isOwningSide']) {
            $class       = $this->em->getClassMetadata($assoc['targetEntity']);
            $association = $class->associationMappings[$assoc['mappedBy']];
        }

        $joinColumns = $assoc['isOwningSide']
            ? $association['joinTable']['joinColumns']
            : $association['joinTable']['inverseJoinColumns'];

        $quotedJoinTable = $this->quoteStrategy->getJoinTableName($association, $class, $this->platform);

        foreach ($joinColumns as $joinColumn) {

            $sourceKeyColumn    = $joinColumn['referencedColumnName'];
            $quotedKeyColumn    = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);

            switch (true) {
                case $sourceClass->containsForeignIdentifier:
                    $field = $sourceClass->getFieldForColumn($sourceKeyColumn);
                    $value = $sourceClass->reflFields[$field]->getValue($sourceEntity);

                    if (isset($sourceClass->associationMappings[$field])) {
                        $value = $this->em->getUnitOfWork()->getEntityIdentifier($value);
                        $value = $value[$this->em->getClassMetadata($sourceClass->associationMappings[$field]['targetEntity'])->identifier[0]];
                    }

                    break;

                case isset($sourceClass->fieldNames[$sourceKeyColumn]):
                    $field = $sourceClass->fieldNames[$sourceKeyColumn];
                    $value = $sourceClass->reflFields[$field]->getValue($sourceEntity);

                    break;

                default:
                    throw MappingException::joinColumnMustPointToMappedField(
                        $sourceClass->name, $sourceKeyColumn
                    );
            }

            $criteria[$quotedJoinTable . '.' . $quotedKeyColumn] = $value;
        }

        $sql = $this->getSelectSQL($criteria, $assoc, null, $limit, $offset);
        list($params, $types) = $this->expandParameters($criteria);

        return $this->conn->executeQuery($sql, $params, $types);
    }

    /**
     * {@inheritdoc}
     */
    public function getSelectSQL($criteria, $assoc = null, $lockMode = null, $limit = null, $offset = null, array $orderBy = null)
    {
        $lockSql    = '';
        $joinSql    = '';
        $orderBySql = '';

        if ($assoc != null && $assoc['type'] == ClassMetadata::MANY_TO_MANY) {
            $joinSql = $this->getSelectManyToManyJoinSQL($assoc);
        }

        if (isset($assoc['orderBy'])) {
            $orderBy = $assoc['orderBy'];
        }

        if ($orderBy) {
            $orderBySql = $this->getOrderBySQL($orderBy, $this->getSQLTableAlias($this->class->name));
        }

        $conditionSql = ($criteria instanceof Criteria)
            ? $this->getSelectConditionCriteriaSQL($criteria)
            : $this->getSelectConditionSQL($criteria, $assoc);

        switch ($lockMode) {
            case LockMode::PESSIMISTIC_READ:
                $lockSql = ' ' . $this->platform->getReadLockSql();
                break;

            case LockMode::PESSIMISTIC_WRITE:
                $lockSql = ' ' . $this->platform->getWriteLockSql();
                break;
        }

        $columnList = $this->getSelectColumnsSQL();
        $tableAlias = $this->getSQLTableAlias($this->class->name);
        $filterSql  = $this->generateFilterConditionSQL($this->class, $tableAlias);
        $tableName  = $this->quoteStrategy->getTableName($this->class, $this->platform);

        if ('' !== $filterSql) {
            $conditionSql = $conditionSql
                ? $conditionSql . ' AND ' . $filterSql
                : $filterSql;
        }

        $select = 'SELECT ' . $columnList;
        $from   = ' FROM ' . $tableName . ' '. $tableAlias;
        $join   = $this->selectJoinSql . $joinSql;
        $where  = ($conditionSql ? ' WHERE ' . $conditionSql : '');
        $lock   = $this->platform->appendLockHint($from, $lockMode);
        $query  = $select
            . $lock
            . $join
            . $where
            . $orderBySql;

        return $this->platform->modifyLimitQuery($query, $limit, $offset) . $lockSql;
    }

    /**
     * {@inheritDoc}
     */
    public function getCountSQL($criteria = array())
    {
        $tableName  = $this->quoteStrategy->getTableName($this->class, $this->platform);
        $tableAlias = $this->getSQLTableAlias($this->class->name);

        $conditionSql = ($criteria instanceof Criteria)
            ? $this->getSelectConditionCriteriaSQL($criteria)
            : $this->getSelectConditionSQL($criteria);

        $filterSql = $this->generateFilterConditionSQL($this->class, $tableAlias);

        if ('' !== $filterSql) {
            $conditionSql = $conditionSql
                ? $conditionSql . ' AND ' . $filterSql
                : $filterSql;
        }

        $sql = 'SELECT COUNT(*) '
            . 'FROM ' . $tableName . ' ' . $tableAlias
            . (empty($conditionSql) ? '' : ' WHERE ' . $conditionSql);

        return $sql;
    }

    /**
     * Gets the ORDER BY SQL snippet for ordered collections.
     *
     * @param array  $orderBy
     * @param string $baseTableAlias
     *
     * @return string
     *
     * @throws \Doctrine\ORM\ORMException
     */
    protected final function getOrderBySQL(array $orderBy, $baseTableAlias)
    {
        $orderByList = array();

        foreach ($orderBy as $fieldName => $orientation) {

            $orientation = strtoupper(trim($orientation));

            if ($orientation != 'ASC' && $orientation != 'DESC') {
                throw ORMException::invalidOrientation($this->class->name, $fieldName);
            }

            if (isset($this->class->fieldMappings[$fieldName])) {
                $tableAlias = isset($this->class->fieldMappings[$fieldName]['inherited'])
                    ? $this->getSQLTableAlias($this->class->fieldMappings[$fieldName]['inherited'])
                    : $baseTableAlias;

                $columnName    = $this->quoteStrategy->getColumnName($fieldName, $this->class, $this->platform);
                $orderByList[] = $tableAlias . '.' . $columnName . ' ' . $orientation;

                continue;
            }

            if (isset($this->class->associationMappings[$fieldName])) {

                if ( ! $this->class->associationMappings[$fieldName]['isOwningSide']) {
                    throw ORMException::invalidFindByInverseAssociation($this->class->name, $fieldName);
                }

                $tableAlias = isset($this->class->associationMappings[$fieldName]['inherited'])
                    ? $this->getSQLTableAlias($this->class->associationMappings[$fieldName]['inherited'])
                    : $baseTableAlias;

                foreach ($this->class->associationMappings[$fieldName]['joinColumns'] as $joinColumn) {
                    $columnName    = $this->quoteStrategy->getJoinColumnName($joinColumn, $this->class, $this->platform);
                    $orderByList[] = $tableAlias . '.' . $columnName . ' ' . $orientation;
                }

                continue;
            }

            throw ORMException::unrecognizedField($fieldName);
        }

        return ' ORDER BY ' . implode(', ', $orderByList);
    }

    /**
     * Gets the SQL fragment with the list of columns to select when querying for
     * an entity in this persister.
     *
     * Subclasses should override this method to alter or change the select column
     * list SQL fragment. Note that in the implementation of BasicEntityPersister
     * the resulting SQL fragment is generated only once and cached in {@link selectColumnListSql}.
     * Subclasses may or may not do the same.
     *
     * @return string The SQL fragment.
     */
    protected function getSelectColumnsSQL()
    {
        if ($this->selectColumnListSql !== null) {
            return $this->selectColumnListSql;
        }

        $columnList = array();
        $this->rsm  = new Query\ResultSetMapping();
        $this->rsm->addEntityResult($this->class->name, 'r'); // r for root

        // Add regular columns to select list
        foreach ($this->class->fieldNames as $field) {
            $columnList[] = $this->getSelectColumnSQL($field, $this->class);
        }

        $this->selectJoinSql    = '';
        $eagerAliasCounter      = 0;

        foreach ($this->class->associationMappings as $assocField => $assoc) {
            $assocColumnSQL = $this->getSelectColumnAssociationSQL($assocField, $assoc, $this->class);

            if ($assocColumnSQL) {
                $columnList[] = $assocColumnSQL;
            }

            $isAssocToOneInverseSide = $assoc['type'] & ClassMetadata::TO_ONE && ! $assoc['isOwningSide'];
            $isAssocFromOneEager     = $assoc['type'] !== ClassMetadata::MANY_TO_MANY && $assoc['fetch'] === ClassMetadata::FETCH_EAGER;

            if ( ! ($isAssocFromOneEager || $isAssocToOneInverseSide)) {
                continue;
            }

            $eagerEntity = $this->em->getClassMetadata($assoc['targetEntity']);

            if ($eagerEntity->inheritanceType != ClassMetadata::INHERITANCE_TYPE_NONE) {
                continue; // now this is why you shouldn't use inheritance
            }

            $assocAlias = 'e' . ($eagerAliasCounter++);
            $this->rsm->addJoinedEntityResult($assoc['targetEntity'], $assocAlias, 'r', $assocField);

            foreach ($eagerEntity->fieldNames as $field) {
                $columnList[] = $this->getSelectColumnSQL($field, $eagerEntity, $assocAlias);
            }

            foreach ($eagerEntity->associationMappings as $eagerAssocField => $eagerAssoc) {
                $eagerAssocColumnSQL = $this->getSelectColumnAssociationSQL(
                    $eagerAssocField, $eagerAssoc, $eagerEntity, $assocAlias
                );

                if ($eagerAssocColumnSQL) {
                    $columnList[] = $eagerAssocColumnSQL;
                }
            }

            $association    = $assoc;
            $joinCondition  = array();

            if (isset($assoc['indexBy'])) {
                $this->rsm->addIndexBy($assocAlias, $assoc['indexBy']);
            }

            if ( ! $assoc['isOwningSide']) {
                $eagerEntity = $this->em->getClassMetadata($assoc['targetEntity']);
                $association = $eagerEntity->getAssociationMapping($assoc['mappedBy']);
            }

            $joinTableAlias = $this->getSQLTableAlias($eagerEntity->name, $assocAlias);
            $joinTableName  = $this->quoteStrategy->getTableName($eagerEntity, $this->platform);

            if ($assoc['isOwningSide']) {
                $tableAlias           = $this->getSQLTableAlias($association['targetEntity'], $assocAlias);
                $this->selectJoinSql .= ' ' . $this->getJoinSQLForJoinColumns($association['joinColumns']);

                foreach ($association['joinColumns'] as $joinColumn) {
                    $sourceCol       = $this->quoteStrategy->getJoinColumnName($joinColumn, $this->class, $this->platform);
                    $targetCol       = $this->quoteStrategy->getReferencedJoinColumnName($joinColumn, $this->class, $this->platform);
                    $joinCondition[] = $this->getSQLTableAlias($association['sourceEntity'])
                                        . '.' . $sourceCol . ' = ' . $tableAlias . '.' . $targetCol;
                }

                // Add filter SQL
                if ($filterSql = $this->generateFilterConditionSQL($eagerEntity, $tableAlias)) {
                    $joinCondition[] = $filterSql;
                }

            } else {

                $this->selectJoinSql .= ' LEFT JOIN';

                foreach ($association['joinColumns'] as $joinColumn) {
                    $sourceCol       = $this->quoteStrategy->getJoinColumnName($joinColumn, $this->class, $this->platform);
                    $targetCol       = $this->quoteStrategy->getReferencedJoinColumnName($joinColumn, $this->class, $this->platform);

                    $joinCondition[] = $this->getSQLTableAlias($association['sourceEntity'], $assocAlias) . '.' . $sourceCol . ' = '
                        . $this->getSQLTableAlias($association['targetEntity']) . '.' . $targetCol;
                }
            }

            $this->selectJoinSql .= ' ' . $joinTableName . ' ' . $joinTableAlias . ' ON ';
            $this->selectJoinSql .= implode(' AND ', $joinCondition);
        }

        $this->selectColumnListSql = implode(', ', $columnList);

        return $this->selectColumnListSql;
    }

    /**
     * Gets the SQL join fragment used when selecting entities from an association.
     *
     * @param string        $field
     * @param array         $assoc
     * @param ClassMetadata $class
     * @param string        $alias
     *
     * @return string
     */
    protected function getSelectColumnAssociationSQL($field, $assoc, ClassMetadata $class, $alias = 'r')
    {
        if ( ! ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE) ) {
            return '';
        }

        $columnList  = array();
        $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

        foreach ($assoc['joinColumns'] as $joinColumn) {
            $type             = null;
            $isIdentifier     = isset($assoc['id']) && $assoc['id'] === true;
            $quotedColumn     = $this->quoteStrategy->getJoinColumnName($joinColumn, $this->class, $this->platform);
            $resultColumnName = $this->getSQLColumnAlias($joinColumn['name']);
            $columnList[]     = $this->getSQLTableAlias($class->name, ($alias == 'r' ? '' : $alias) )
                                . '.' . $quotedColumn . ' AS ' . $resultColumnName;

            if (isset($targetClass->fieldNames[$joinColumn['referencedColumnName']])) {
                $type  = $targetClass->fieldMappings[$targetClass->fieldNames[$joinColumn['referencedColumnName']]]['type'];
            }

            $this->rsm->addMetaResult($alias, $resultColumnName, $quotedColumn, $isIdentifier, $type);
        }

        return implode(', ', $columnList);
    }

    /**
     * Gets the SQL join fragment used when selecting entities from a
     * many-to-many association.
     *
     * @param array $manyToMany
     *
     * @return string
     */
    protected function getSelectManyToManyJoinSQL(array $manyToMany)
    {
        $conditions         = array();
        $association        = $manyToMany;
        $sourceTableAlias   = $this->getSQLTableAlias($this->class->name);

        if ( ! $manyToMany['isOwningSide']) {
            $targetEntity   = $this->em->getClassMetadata($manyToMany['targetEntity']);
            $association    = $targetEntity->associationMappings[$manyToMany['mappedBy']];
        }

        $joinTableName  = $this->quoteStrategy->getJoinTableName($association, $this->class, $this->platform);
        $joinColumns    = ($manyToMany['isOwningSide'])
            ? $association['joinTable']['inverseJoinColumns']
            : $association['joinTable']['joinColumns'];

        foreach ($joinColumns as $joinColumn) {
            $quotedSourceColumn = $this->quoteStrategy->getJoinColumnName($joinColumn, $this->class, $this->platform);
            $quotedTargetColumn = $this->quoteStrategy->getReferencedJoinColumnName($joinColumn, $this->class, $this->platform);
            $conditions[]       = $sourceTableAlias . '.' . $quotedTargetColumn . ' = ' . $joinTableName . '.' . $quotedSourceColumn;
        }

        return ' INNER JOIN ' . $joinTableName . ' ON ' . implode(' AND ', $conditions);
    }

    /**
     * {@inheritdoc}
     */
    public function getInsertSQL()
    {
        if ($this->insertSql !== null) {
            return $this->insertSql;
        }

        $columns   = $this->getInsertColumnList();
        $tableName = $this->quoteStrategy->getTableName($this->class, $this->platform);

        if (empty($columns)) {
            $identityColumn  = $this->quoteStrategy->getColumnName($this->class->identifier[0], $this->class, $this->platform);
            $this->insertSql = $this->platform->getEmptyIdentityInsertSQL($tableName, $identityColumn);

            return $this->insertSql;
        }

        $values  = array();
        $columns = array_unique($columns);

        foreach ($columns as $column) {
            $placeholder = '?';

            if (isset($this->class->fieldNames[$column])
                && isset($this->columnTypes[$this->class->fieldNames[$column]])
                && isset($this->class->fieldMappings[$this->class->fieldNames[$column]]['requireSQLConversion'])) {

                $type        = Type::getType($this->columnTypes[$this->class->fieldNames[$column]]);
                $placeholder = $type->convertToDatabaseValueSQL('?', $this->platform);
            }

            $values[] = $placeholder;
        }

        $columns = implode(', ', $columns);
        $values  = implode(', ', $values);

        $this->insertSql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $tableName, $columns, $values);

        return $this->insertSql;
    }

    /**
     * Gets the list of columns to put in the INSERT SQL statement.
     *
     * Subclasses should override this method to alter or change the list of
     * columns placed in the INSERT statements used by the persister.
     *
     * @return array The list of columns.
     */
    protected function getInsertColumnList()
    {
        $columns = array();

        foreach ($this->class->reflFields as $name => $field) {
            if ($this->class->isVersioned && $this->class->versionField == $name) {
                continue;
            }

            if (isset($this->class->associationMappings[$name])) {
                $assoc = $this->class->associationMappings[$name];
                if ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE) {
                    foreach ($assoc['joinColumns'] as $joinColumn) {
                        $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $this->class, $this->platform);
                    }
                }

                continue;
            }

            if ($this->class->generatorType != ClassMetadata::GENERATOR_TYPE_IDENTITY || $this->class->identifier[0] != $name) {
                $columns[] = $this->quoteStrategy->getColumnName($name, $this->class, $this->platform);
                $this->columnTypes[$name] = $this->class->fieldMappings[$name]['type'];
            }
        }

        return $columns;
    }

    /**
     * Gets the SQL snippet of a qualified column name for the given field name.
     *
     * @param string        $field The field name.
     * @param ClassMetadata $class The class that declares this field. The table this class is
     *                             mapped to must own the column for the given field.
     * @param string        $alias
     *
     * @return string
     */
    protected function getSelectColumnSQL($field, ClassMetadata $class, $alias = 'r')
    {
        $root           = $alias == 'r' ? '' : $alias ;
        $tableAlias     = $this->getSQLTableAlias($class->name, $root);
        $columnName     = $this->quoteStrategy->getColumnName($field, $class, $this->platform);
        $sql            = $tableAlias . '.' . $columnName;
        $columnAlias    = $this->getSQLColumnAlias($class->columnNames[$field]);

        $this->rsm->addFieldResult($alias, $columnAlias, $field);

        if (isset($class->fieldMappings[$field]['requireSQLConversion'])) {
            $type   = Type::getType($class->getTypeOfField($field));
            $sql    = $type->convertToPHPValueSQL($sql, $this->platform);
        }

        return $sql . ' AS ' . $columnAlias;
    }

    /**
     * Gets the SQL table alias for the given class name.
     *
     * @param string $className
     * @param string $assocName
     *
     * @return string The SQL table alias.
     *
     * @todo Reconsider. Binding table aliases to class names is not such a good idea.
     */
    protected function getSQLTableAlias($className, $assocName = '')
    {
        if ($assocName) {
            $className .= '#' . $assocName;
        }

        if (isset($this->sqlTableAliases[$className])) {
            return $this->sqlTableAliases[$className];
        }

        $tableAlias = 't' . $this->sqlAliasCounter++;

        $this->sqlTableAliases[$className] = $tableAlias;

        return $tableAlias;
    }

    /**
     * {@inheritdoc}
     */
    public function lock(array $criteria, $lockMode)
    {
        $lockSql      = '';
        $conditionSql = $this->getSelectConditionSQL($criteria);

        switch ($lockMode) {
            case LockMode::PESSIMISTIC_READ:
                $lockSql = $this->platform->getReadLockSql();

                break;
            case LockMode::PESSIMISTIC_WRITE:

                $lockSql = $this->platform->getWriteLockSql();
                break;
        }

        $lock  = $this->getLockTablesSql($lockMode);
        $where = ($conditionSql ? ' WHERE ' . $conditionSql : '') . ' ';
        $sql = 'SELECT 1 '
             . $lock
             . $where
             . $lockSql;

        list($params, $types) = $this->expandParameters($criteria);

        $this->conn->executeQuery($sql, $params, $types);
    }

    /**
     * Gets the FROM and optionally JOIN conditions to lock the entity managed by this persister.
     *
     * @param integer $lockMode One of the Doctrine\DBAL\LockMode::* constants.
     *
     * @return string
     */
    protected function getLockTablesSql($lockMode)
    {
        return $this->platform->appendLockHint(
            'FROM '
            . $this->quoteStrategy->getTableName($this->class, $this->platform) . ' '
            . $this->getSQLTableAlias($this->class->name),
            $lockMode
        );
    }

    /**
     * Gets the Select Where Condition from a Criteria object.
     *
     * @param \Doctrine\Common\Collections\Criteria $criteria
     *
     * @return string
     */
    protected function getSelectConditionCriteriaSQL(Criteria $criteria)
    {
        $expression = $criteria->getWhereExpression();

        if ($expression === null) {
            return '';
        }

        $visitor = new SqlExpressionVisitor($this, $this->class);

        return $visitor->dispatch($expression);
    }

    /**
     * {@inheritdoc}
     */
    public function getSelectConditionStatementSQL($field, $value, $assoc = null, $comparison = null)
    {
        $selectedColumns = array();
        $columns    = $this->getSelectConditionStatementColumnSQL($field, $assoc);

        if (count($columns)>1 && $comparison === Comparison::IN){
            throw ORMException::cantUseInOperatorOnComposteKeys();
        }

        foreach ($columns as $column) {
            $placeholder  = '?';
            if (isset($this->class->fieldMappings[$field]['requireSQLConversion'])) {
                $placeholder = Type::getType($this->class->getTypeOfField($field))->convertToDatabaseValueSQL($placeholder, $this->platform);
            }

            if ($comparison !== null) {

                // special case null value handling
                if (($comparison === Comparison::EQ || $comparison === Comparison::IS) && $value === null) {
                     $selectedColumns[] = $column . ' IS NULL';
                    continue;
                } else if ($comparison === Comparison::NEQ && $value === null) {
                     $selectedColumns[] = $column . ' IS NOT NULL';
                    continue;
                }

                 $selectedColumns[] = $column . ' ' . sprintf(self::$comparisonMap[$comparison], $placeholder);
                continue;
            }

            if (is_array($value)) {
                $in = sprintf('%s IN (%s)' , $column, $placeholder);

                if (false !== array_search(null, $value, true)) {
                    $selectedColumns[] = sprintf('(%s OR %s IS NULL)' , $in, $column);
                    continue;
                }

                $selectedColumns[] = $in;
                continue;
            }
            if ($value === null) {
                $selectedColumns[] = sprintf('%s IS NULL' , $column);
                continue;
            }

            $selectedColumns[] = sprintf('%s = %s' , $column, $placeholder);
        }

        return implode(' AND ', $selectedColumns);
    }

    /**
     * Builds the left-hand-side of a where condition statement.
     *
     * @param string     $field
     * @param array|null $assoc
     *
     * @return array
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function getSelectConditionStatementColumnSQL($field, $assoc = null)
    {
        if (isset($this->class->columnNames[$field])) {
            $className = (isset($this->class->fieldMappings[$field]['inherited']))
                ? $this->class->fieldMappings[$field]['inherited']
                : $this->class->name;

            return array($this->getSQLTableAlias($className) . '.' . $this->quoteStrategy->getColumnName($field, $this->class, $this->platform));
        }

        if (isset($this->class->associationMappings[$field])) {

            if ( ! $this->class->associationMappings[$field]['isOwningSide']) {
                throw ORMException::invalidFindByInverseAssociation($this->class->name, $field);
            }
            $className  = (isset($this->class->associationMappings[$field]['inherited']))
            ? $this->class->associationMappings[$field]['inherited']
            : $this->class->name;

            $columns = array();
            foreach ($this->class->associationMappings[$field]['joinColumns'] as $joinColumn) {
                $columns[] = $this->getSQLTableAlias($className) . '.' . $this->quoteStrategy->getJoinColumnName($joinColumn, $this->class, $this->platform);
            }
            return $columns;
        }

        if ($assoc !== null && strpos($field, " ") === false && strpos($field, "(") === false) {
            // very careless developers could potentially open up this normally hidden api for userland attacks,
            // therefore checking for spaces and function calls which are not allowed.

            // found a join column condition, not really a "field"
            return array($field);
        }

        throw ORMException::unrecognizedField($field);
    }

    /**
     * Gets the conditional SQL fragment used in the WHERE clause when selecting
     * entities in this persister.
     *
     * Subclasses are supposed to override this method if they intend to change
     * or alter the criteria by which entities are selected.
     *
     * @param array      $criteria
     * @param array|null $assoc
     *
     * @return string
     */
    protected function getSelectConditionSQL(array $criteria, $assoc = null)
    {
        $conditions = array();

        foreach ($criteria as $field => $value) {
            $conditions[] = $this->getSelectConditionStatementSQL($field, $value, $assoc);
        }

        return implode(' AND ', $conditions);
    }

    /**
     * {@inheritdoc}
     */
    public function getOneToManyCollection(array $assoc, $sourceEntity, $offset = null, $limit = null)
    {
        $stmt = $this->getOneToManyStatement($assoc, $sourceEntity, $offset, $limit);

        return $this->loadArrayFromStatement($assoc, $stmt);
    }

    /**
     * {@inheritdoc}
     */
    public function loadOneToManyCollection(array $assoc, $sourceEntity, PersistentCollection $coll)
    {
        $stmt = $this->getOneToManyStatement($assoc, $sourceEntity);

        return $this->loadCollectionFromStatement($assoc, $stmt, $coll);
    }

    /**
     * Builds criteria and execute SQL statement to fetch the one to many entities from.
     *
     * @param array    $assoc
     * @param object   $sourceEntity
     * @param int|null $offset
     * @param int|null $limit
     *
     * @return \Doctrine\DBAL\Statement
     */
    private function getOneToManyStatement(array $assoc, $sourceEntity, $offset = null, $limit = null)
    {
        $criteria = array();
        $owningAssoc = $this->class->associationMappings[$assoc['mappedBy']];
        $sourceClass = $this->em->getClassMetadata($assoc['sourceEntity']);

        $tableAlias = $this->getSQLTableAlias(isset($owningAssoc['inherited']) ? $owningAssoc['inherited'] : $this->class->name);

        foreach ($owningAssoc['targetToSourceKeyColumns'] as $sourceKeyColumn => $targetKeyColumn) {
            if ($sourceClass->containsForeignIdentifier) {
                $field = $sourceClass->getFieldForColumn($sourceKeyColumn);
                $value = $sourceClass->reflFields[$field]->getValue($sourceEntity);

                if (isset($sourceClass->associationMappings[$field])) {
                    $value = $this->em->getUnitOfWork()->getEntityIdentifier($value);
                    $value = $value[$this->em->getClassMetadata($sourceClass->associationMappings[$field]['targetEntity'])->identifier[0]];
                }

                $criteria[$tableAlias . "." . $targetKeyColumn] = $value;

                continue;
            }

            $criteria[$tableAlias . "." . $targetKeyColumn] = $sourceClass->reflFields[$sourceClass->fieldNames[$sourceKeyColumn]]->getValue($sourceEntity);
        }

        $sql = $this->getSelectSQL($criteria, $assoc, null, $limit, $offset);
        list($params, $types) = $this->expandParameters($criteria);

        return $this->conn->executeQuery($sql, $params, $types);
    }

    /**
     * {@inheritdoc}
     */
    public function expandParameters($criteria)
    {
        $params = array();
        $types  = array();

        foreach ($criteria as $field => $value) {
            if ($value === null) {
                continue; // skip null values.
            }

            $types = array_merge($types, $this->getTypes($field, $value));
            $params = array_merge($params, $this->getValues($value));
        }

        return array($params, $types);
    }

    /**
     * Infers field types to be used by parameter type casting.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return array
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    private function getTypes($field, $value)
    {
        $types = array();
        switch (true) {
            case (isset($this->class->fieldMappings[$field])):
                $types[] = $this->class->fieldMappings[$field]['type'];
                break;
            case (isset($this->class->associationMappings[$field])):
                $assoc = $this->class->associationMappings[$field];

                $targetPersister = $this->em->getUnitOfWork()->getEntityPersister($assoc['targetEntity']);

                if ($assoc['isOwningSide']) {
                    $parameters = $targetPersister->expandParameters($assoc['targetToSourceKeyColumns']);
                }else{
                    $parameters = $targetPersister->expandParameters($assoc['sourceToTargetKeyColumns']);
                }
                $types = array_merge($types, $parameters[1]);
                break;
            default:
                $types[] = null;
                break;
        }

        if (is_array($value)) {
            $types = array_map(function ($type) {
                $type = Type::getType($type)->getBindingType();
                return $type + Connection::ARRAY_PARAM_OFFSET;
            }, $types);
        }
        return $types;
    }

    /**
     * Retrieves the parameters that identifies a value.
     *
     * @param mixed $value
     *
     * @return array
     */
    private function getValues($value)
    {
        if (is_array($value)) {
            $newValue = array();

            foreach ($value as $itemValue) {
                $newValue = array_merge($newValue, $this->getValues($itemValue));
            }
            return array($newValue);
        }

        if (is_object($value) && $this->em->getMetadataFactory()->hasMetadataFor(ClassUtils::getClass($value))) {
            $class = $this->em->getClassMetadata(ClassUtils::getClass($value));
            if ($class->isIdentifierComposite) {
                $newValue = array();
                foreach ($class->getIdentifierValues($value) as $innerValue) {
                    $newValue = array_merge($newValue, $this->getValues($innerValue));
                }
                return $newValue;
            }
        }

        return array($this->getIndividualValue($value));
    }

    /**
     * Retrieves an individual parameter value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function getIndividualValue($value)
    {
        if ( ! is_object($value) || ! $this->em->getMetadataFactory()->hasMetadataFor(ClassUtils::getClass($value))) {
            return $value;
        }

        return $this->em->getUnitOfWork()->getSingleIdentifierValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($entity, Criteria $extraConditions = null)
    {
        $criteria = $this->class->getIdentifierValues($entity);

        if ( ! $criteria) {
            return false;
        }

        $alias = $this->getSQLTableAlias($this->class->name);

        $sql = 'SELECT 1 '
             . $this->getLockTablesSql(null)
             . ' WHERE ' . $this->getSelectConditionSQL($criteria);

        list($params) = $this->expandParameters($criteria);

        if (null !== $extraConditions) {
            $sql                           .= ' AND ' . $this->getSelectConditionCriteriaSQL($extraConditions);
            list($criteriaParams, $values) = $this->expandCriteriaParameters($extraConditions);

            $params = array_merge($params, $criteriaParams);
        }

        if ($filterSql = $this->generateFilterConditionSQL($this->class, $alias)) {
            $sql .= ' AND ' . $filterSql;
        }

        return (bool) $this->conn->fetchColumn($sql, $params);
    }

    /**
     * Generates the appropriate join SQL for the given join column.
     *
     * @param array $joinColumns The join columns definition of an association.
     *
     * @return string LEFT JOIN if one of the columns is nullable, INNER JOIN otherwise.
     */
    protected function getJoinSQLForJoinColumns($joinColumns)
    {
        // if one of the join columns is nullable, return left join
        foreach ($joinColumns as $joinColumn) {
             if ( ! isset($joinColumn['nullable']) || $joinColumn['nullable']) {
                 return 'LEFT JOIN';
             }
        }

        return 'INNER JOIN';
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLColumnAlias($columnName)
    {
        return $this->quoteStrategy->getColumnAlias($columnName, $this->sqlAliasCounter++, $this->platform);
    }

    /**
     * Generates the filter SQL for a given entity and table alias.
     *
     * @param ClassMetadata $targetEntity     Metadata of the target entity.
     * @param string        $targetTableAlias The table alias of the joined/selected table.
     *
     * @return string The SQL query part to add to a query.
     */
    protected function generateFilterConditionSQL(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $filterClauses = array();

        foreach ($this->em->getFilters()->getEnabledFilters() as $filter) {
            if ('' !== $filterExpr = $filter->addFilterConstraint($targetEntity, $targetTableAlias)) {
                $filterClauses[] = '(' . $filterExpr . ')';
            }
        }

        $sql = implode(' AND ', $filterClauses);
        return $sql ? "(" . $sql . ")" : ""; // Wrap again to avoid "X or Y and FilterConditionSQL"
    }
}
