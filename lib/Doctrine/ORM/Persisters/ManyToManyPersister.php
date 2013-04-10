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

use Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\PersistentCollection,
    Doctrine\ORM\UnitOfWork;

/**
 * Persister for many-to-many collections.
 *
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Alexander <iam.asm89@gmail.com>
 * @since   2.0
 */
class ManyToManyPersister extends AbstractCollectionPersister
{
    /**
     * {@inheritdoc}
     *
     * @override
     */
    protected function _getDeleteRowSQL(PersistentCollection $coll)
    {
        $columns = array();
        $mapping = $coll->getMapping();
        $class   = $this->_em->getClassMetadata(get_class($coll->getOwner()));

        foreach ($mapping['joinTable']['joinColumns'] as $joinColumn) {
            $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
        }

        foreach ($mapping['joinTable']['inverseJoinColumns'] as $joinColumn) {
            $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
        }

        return 'DELETE FROM ' . $this->quoteStrategy->getJoinTableName($mapping, $class, $this->platform)
             . ' WHERE ' . implode(' = ? AND ', $columns) . ' = ?';
    }

    /**
     * {@inheritdoc}
     *
     * @override
     * @internal Order of the parameters must be the same as the order of the columns in
     *           _getDeleteRowSql.
     */
    protected function _getDeleteRowSQLParameters(PersistentCollection $coll, $element)
    {
        return $this->_collectJoinTableColumnParameters($coll, $element);
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    protected function _getUpdateRowSQL(PersistentCollection $coll)
    {}

    /**
     * {@inheritdoc}
     *
     * @override
     * @internal Order of the parameters must be the same as the order of the columns in
     *           _getInsertRowSql.
     */
    protected function _getInsertRowSQL(PersistentCollection $coll)
    {
        $columns    = array();
        $mapping    = $coll->getMapping();
        $class      = $this->_em->getClassMetadata(get_class($coll->getOwner()));
        $joinTable  = $this->quoteStrategy->getJoinTableName($mapping, $class, $this->platform);

        foreach ($mapping['joinTable']['joinColumns'] as $joinColumn) {
            $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
        }

        foreach ($mapping['joinTable']['inverseJoinColumns'] as $joinColumn) {
            $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
        }

        return 'INSERT INTO ' . $joinTable . ' (' . implode(', ', $columns) . ')'
             . ' VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')';
    }

    /**
     * {@inheritdoc}
     *
     * @override
     * @internal Order of the parameters must be the same as the order of the columns in
     *           _getInsertRowSql.
     */
    protected function _getInsertRowSQLParameters(PersistentCollection $coll, $element)
    {
        return $this->_collectJoinTableColumnParameters($coll, $element);
    }

    /**
     * Collects the parameters for inserting/deleting on the join table in the order
     * of the join table columns as specified in ManyToManyMapping#joinTableColumns.
     *
     * @param $coll
     * @param $element
     * @return array
     */
    private function _collectJoinTableColumnParameters(PersistentCollection $coll, $element)
    {
        $params      = array();
        $mapping     = $coll->getMapping();
        $isComposite = count($mapping['joinTableColumns']) > 2;

        $identifier1 = $this->_uow->getEntityIdentifier($coll->getOwner());
        $identifier2 = $this->_uow->getEntityIdentifier($element);

        if ($isComposite) {
            $class1 = $this->_em->getClassMetadata(get_class($coll->getOwner()));
            $class2 = $coll->getTypeClass();
        }

        foreach ($mapping['joinTableColumns'] as $joinTableColumn) {
            $isRelationToSource = isset($mapping['relationToSourceKeyColumns'][$joinTableColumn]);

            if ( ! $isComposite) {
                $params[] = $isRelationToSource ? array_pop($identifier1) : array_pop($identifier2);

                continue;
            }

            if ($isRelationToSource) {
                $params[] = $identifier1[$class1->getFieldForColumn($mapping['relationToSourceKeyColumns'][$joinTableColumn])];

                continue;
            }

            $params[] = $identifier2[$class2->getFieldForColumn($mapping['relationToTargetKeyColumns'][$joinTableColumn])];
        }

        return $params;
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    protected function _getDeleteSQL(PersistentCollection $coll)
    {
        $columns    = array();
        $mapping    = $coll->getMapping();
        $class      = $this->_em->getClassMetadata(get_class($coll->getOwner()));
        $joinTable  = $this->quoteStrategy->getJoinTableName($mapping, $class, $this->platform);

        foreach ($mapping['joinTable']['joinColumns'] as $joinColumn) {
            $columns[] = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
        }

        return 'DELETE FROM ' . $joinTable
             . ' WHERE ' . implode(' = ? AND ', $columns) . ' = ?';
    }

    /**
     * {@inheritdoc}
     *
     * @override
     * @internal Order of the parameters must be the same as the order of the columns in
     *           _getDeleteSql.
     */
    protected function _getDeleteSQLParameters(PersistentCollection $coll)
    {
        $mapping    = $coll->getMapping();
        $identifier = $this->_uow->getEntityIdentifier($coll->getOwner());

        // Optimization for single column identifier
        if (count($mapping['relationToSourceKeyColumns']) === 1) {
            return array(reset($identifier));
        }

        // Composite identifier
        $sourceClass = $this->_em->getClassMetadata(get_class($coll->getOwner()));
        $params      = array();

        foreach ($mapping['relationToSourceKeyColumns'] as $columnName => $refColumnName) {
            $params[] = isset($sourceClass->fieldNames[$refColumnName])
                ? $identifier[$sourceClass->fieldNames[$refColumnName]]
                : $identifier[$sourceClass->getFieldForColumn($columnName)];
        }

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function count(PersistentCollection $coll)
    {
        $conditions     = array();
        $params         = array();
        $mapping        = $coll->getMapping();
        $association    = $mapping;
        $class          = $this->_em->getClassMetadata($mapping['sourceEntity']);
        $id             = $this->_em->getUnitOfWork()->getEntityIdentifier($coll->getOwner());

        if ( ! $mapping['isOwningSide']) {
            $targetEntity   = $this->_em->getClassMetadata($mapping['targetEntity']);
            $association    = $targetEntity->associationMappings[$mapping['mappedBy']];
        }

        $joinColumns = ( ! $mapping['isOwningSide'])
            ? $association['joinTable']['inverseJoinColumns']
            : $association['joinTable']['joinColumns'];

        foreach ($joinColumns as $joinColumn) {
            $columnName     = $this->quoteStrategy->getJoinColumnName($joinColumn, $class, $this->platform);
            $referencedName = $joinColumn['referencedColumnName'];
            $conditions[]   = $columnName . ' = ?';
            $params[]       = ($class->containsForeignIdentifier)
                ? $id[$class->getFieldForColumn($referencedName)]
                : $id[$class->fieldNames[$referencedName]];
        }

        $joinTableName = $this->quoteStrategy->getJoinTableName($association, $class, $this->platform);
        list($joinTargetEntitySQL, $filterSql) = $this->getFilterSql($mapping);

        if ($filterSql) {
            $conditions[] = $filterSql;
        }

        $sql = 'SELECT COUNT(*)'
            . ' FROM ' . $joinTableName . ' t'
            . $joinTargetEntitySQL
            . ' WHERE ' . implode(' AND ', $conditions);

        return $this->_conn->fetchColumn($sql, $params);
    }

    /**
     * @param PersistentCollection $coll
     * @param int $offset
     * @param int $length
     * @return array
     */
    public function slice(PersistentCollection $coll, $offset, $length = null)
    {
        $mapping = $coll->getMapping();

        return $this->_em->getUnitOfWork()->getEntityPersister($mapping['targetEntity'])->getManyToManyCollection($mapping, $coll->getOwner(), $offset, $length);
    }

    /**
     * @param PersistentCollection $coll
     * @param object $element
     * @return boolean
     */
    public function contains(PersistentCollection $coll, $element)
    {
        $uow = $this->_em->getUnitOfWork();

        // Shortcut for new entities
        $entityState = $uow->getEntityState($element, UnitOfWork::STATE_NEW);

        if ($entityState === UnitOfWork::STATE_NEW) {
            return false;
        }

        // Entity is scheduled for inclusion
        if ($entityState === UnitOfWork::STATE_MANAGED && $uow->isScheduledForInsert($element)) {
            return false;
        }

        list($quotedJoinTable, $whereClauses, $params) = $this->getJoinTableRestrictions($coll, $element, true);

        $sql = 'SELECT 1 FROM ' . $quotedJoinTable . ' WHERE ' . implode(' AND ', $whereClauses);

        return (bool) $this->_conn->fetchColumn($sql, $params);
    }

    /**
     * @param PersistentCollection $coll
     * @param object $element
     * @return boolean
     */
    public function removeElement(PersistentCollection $coll, $element)
    {
        $uow = $this->_em->getUnitOfWork();

        // shortcut for new entities
        $entityState = $uow->getEntityState($element, UnitOfWork::STATE_NEW);

        if ($entityState === UnitOfWork::STATE_NEW) {
            return false;
        }

        // If Entity is scheduled for inclusion, it is not in this collection.
        // We can assure that because it would have return true before on array check
        if ($entityState === UnitOfWork::STATE_MANAGED && $uow->isScheduledForInsert($element)) {
            return false;
        }

        list($quotedJoinTable, $whereClauses, $params) = $this->getJoinTableRestrictions($coll, $element, false);

        $sql = 'DELETE FROM ' . $quotedJoinTable . ' WHERE ' . implode(' AND ', $whereClauses);

        return (bool) $this->_conn->executeUpdate($sql, $params);
    }

    /**
     * @param \Doctrine\ORM\PersistentCollection $coll
     * @param object $element
     * @param boolean $addFilters Whether the filter SQL should be included or not.
     * @return array
     */
    private function getJoinTableRestrictions(PersistentCollection $coll, $element, $addFilters)
    {
        $uow     = $this->_em->getUnitOfWork();
        $mapping = $filterMapping = $coll->getMapping();

        if ( ! $mapping['isOwningSide']) {
            $sourceClass = $this->_em->getClassMetadata($mapping['targetEntity']);
            $targetClass = $this->_em->getClassMetadata($mapping['sourceEntity']);
            $sourceId = $uow->getEntityIdentifier($element);
            $targetId = $uow->getEntityIdentifier($coll->getOwner());

            $mapping = $sourceClass->associationMappings[$mapping['mappedBy']];
        } else {
            $sourceClass = $this->_em->getClassMetadata($mapping['sourceEntity']);
            $targetClass = $this->_em->getClassMetadata($mapping['targetEntity']);
            $sourceId = $uow->getEntityIdentifier($coll->getOwner());
            $targetId = $uow->getEntityIdentifier($element);
        }

        $quotedJoinTable = $this->quoteStrategy->getJoinTableName($mapping, $sourceClass, $this->platform);
        $whereClauses    = array();
        $params          = array();

        foreach ($mapping['joinTableColumns'] as $joinTableColumn) {
            $whereClauses[] = $joinTableColumn . ' = ?';

            if (isset($mapping['relationToTargetKeyColumns'][$joinTableColumn])) {
                $params[] = ($targetClass->containsForeignIdentifier)
                    ? $targetId[$targetClass->getFieldForColumn($mapping['relationToTargetKeyColumns'][$joinTableColumn])]
                    : $targetId[$targetClass->fieldNames[$mapping['relationToTargetKeyColumns'][$joinTableColumn]]];
                continue;
            }

            // relationToSourceKeyColumns
            $params[] = ($sourceClass->containsForeignIdentifier)
                ? $sourceId[$sourceClass->getFieldForColumn($mapping['relationToSourceKeyColumns'][$joinTableColumn])]
                : $sourceId[$sourceClass->fieldNames[$mapping['relationToSourceKeyColumns'][$joinTableColumn]]];
        }

        if ($addFilters) {
            list($joinTargetEntitySQL, $filterSql) = $this->getFilterSql($filterMapping);
            if ($filterSql) {
                $quotedJoinTable .= ' t ' . $joinTargetEntitySQL;
                $whereClauses[] = $filterSql;
            }
        }

        return array($quotedJoinTable, $whereClauses, $params);
    }

    /**
     * Generates the filter SQL for a given mapping.
     *
     * This method is not used for actually grabbing the related entities
     * but when the extra-lazy collection methods are called on a filtered
     * association. This is why besides the many to many table we also
     * have to join in the actual entities table leading to additional
     * JOIN.
     *
     * @param array $mapping Array containing mapping information.
     *
     * @return string The SQL query part to add to a query.
     */
    public function getFilterSql($mapping)
    {
        $targetClass = $this->_em->getClassMetadata($mapping['targetEntity']);

        if ($mapping['isOwningSide']) {
            $joinColumns = $mapping['relationToTargetKeyColumns'];
        } else {
            $mapping = $targetClass->associationMappings[$mapping['mappedBy']];
            $joinColumns = $mapping['relationToSourceKeyColumns'];
        }

        $targetClass = $this->_em->getClassMetadata($targetClass->rootEntityName);

        // A join is needed if there is filtering on the target entity
        $joinTargetEntitySQL = '';
        if ($filterSql = $this->generateFilterConditionSQL($targetClass, 'te')) {
            $joinTargetEntitySQL = ' JOIN '
                . $this->quoteStrategy->getTableName($targetClass, $this->platform) . ' te'
                . ' ON';

            $joinTargetEntitySQLClauses = array();
            foreach ($joinColumns as $joinTableColumn => $targetTableColumn) {
                $joinTargetEntitySQLClauses[] = ' t.' . $joinTableColumn . ' = ' . 'te.' . $targetTableColumn;
            }

            $joinTargetEntitySQL .= implode(' AND ', $joinTargetEntitySQLClauses);
        }

        return array($joinTargetEntitySQL, $filterSql);
    }

    /**
     * Generates the filter SQL for a given entity and table alias.
     *
     * @param ClassMetadata $targetEntity Metadata of the target entity.
     * @param string $targetTableAlias The table alias of the joined/selected table.
     *
     * @return string The SQL query part to add to a query.
     */
    protected function generateFilterConditionSQL(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $filterClauses = array();

        foreach ($this->_em->getFilters()->getEnabledFilters() as $filter) {
            if ($filterExpr = $filter->addFilterConstraint($targetEntity, $targetTableAlias)) {
                $filterClauses[] = '(' . $filterExpr . ')';
            }
        }

        $sql = implode(' AND ', $filterClauses);
        return $sql ? "(" . $sql . ")" : "";
    }
}
