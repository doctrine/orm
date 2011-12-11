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

use Doctrine\ORM\PersistentCollection,
    Doctrine\ORM\UnitOfWork;

/**
 * Persister for many-to-many collections.
 *
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
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
        $mapping = $coll->getMapping();
        $class   = $this->_em->getClassMetadata(get_class($coll->getOwner()));
        
        return 'DELETE FROM ' . $class->getQuotedJoinTableName($mapping, $this->_conn->getDatabasePlatform()) 
             . ' WHERE ' . implode(' = ? AND ', $mapping['joinTableColumns']) . ' = ?';
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
        $mapping = $coll->getMapping();
        $columns = $mapping['joinTableColumns'];
        $class   = $this->_em->getClassMetadata(get_class($coll->getOwner()));
        
        $joinTable = $class->getQuotedJoinTableName($mapping, $this->_conn->getDatabasePlatform());
        
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
        $class   = $this->_em->getClassMetadata(get_class($coll->getOwner()));
        $mapping = $coll->getMapping();
        
        return 'DELETE FROM ' . $class->getQuotedJoinTableName($mapping, $this->_conn->getDatabasePlatform()) 
             . ' WHERE ' . implode(' = ? AND ', array_keys($mapping['relationToSourceKeyColumns'])) . ' = ?';
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
        $identifier = $this->_uow->getEntityIdentifier($coll->getOwner());
        $mapping    = $coll->getMapping();
        $params     = array();
        
        // Optimization for single column identifier
        if (count($mapping['relationToSourceKeyColumns']) === 1) {
            $params[] = array_pop($identifier);
            
            return $params;
        }
        
        // Composite identifier
        $sourceClass = $this->_em->getClassMetadata(get_class($mapping->getOwner()));
        
        foreach ($mapping['relationToSourceKeyColumns'] as $relColumn => $srcColumn) {
            $params[] = $identifier[$sourceClass->fieldNames[$srcColumn]];
        }
        
        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function count(PersistentCollection $coll)
    {
        $mapping = $coll->getMapping();
        $class   = $this->_em->getClassMetadata($mapping['sourceEntity']);
        $id      = $this->_em->getUnitOfWork()->getEntityIdentifier($coll->getOwner());

        if ($mapping['isOwningSide']) {
            $joinColumns = $mapping['relationToSourceKeyColumns'];
        } else {
            $mapping = $this->_em->getClassMetadata($mapping['targetEntity'])->associationMappings[$mapping['mappedBy']];
            $joinColumns = $mapping['relationToTargetKeyColumns'];
        }

        $whereClauses = array();
        $params  = array();
        
        foreach ($mapping['joinTableColumns'] as $joinTableColumn) {
            if ( ! isset($joinColumns[$joinTableColumn])) {
                continue;
            }
            
            $whereClauses[] = $joinTableColumn . ' = ?';

            $params[] = ($class->containsForeignIdentifier)
                ? $id[$class->getFieldForColumn($joinColumns[$joinTableColumn])]
                : $id[$class->fieldNames[$joinColumns[$joinTableColumn]]];
        }
        
        $sql = 'SELECT COUNT(*)'
             . ' FROM ' . $class->getQuotedJoinTableName($mapping, $this->_conn->getDatabasePlatform()) 
             . ' WHERE ' . implode(' AND ', $whereClauses);

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

        // shortcut for new entities
        if ($uow->getEntityState($element, UnitOfWork::STATE_NEW) == UnitOfWork::STATE_NEW) {
            return false;
        }

        list($quotedJoinTable, $whereClauses, $params) = $this->getJoinTableRestrictions($coll, $element);
        
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
        if ($uow->getEntityState($element, UnitOfWork::STATE_NEW) == UnitOfWork::STATE_NEW) {
            return false;
        }

        list($quotedJoinTable, $whereClauses, $params) = $this->getJoinTableRestrictions($coll, $element);

        $sql = 'DELETE FROM ' . $quotedJoinTable . ' WHERE ' . implode(' AND ', $whereClauses);
             
        return (bool) $this->_conn->executeUpdate($sql, $params);
    }
    
    /**
     * @param \Doctrine\ORM\PersistentCollection $coll
     * @param object $element
     * @return array
     */
    private function getJoinTableRestrictions(PersistentCollection $coll, $element)
    {
        $uow     = $this->_em->getUnitOfWork();
        $mapping = $coll->getMapping();
        
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
        
        $quotedJoinTable = $sourceClass->getQuotedJoinTableName($mapping, $this->_conn->getDatabasePlatform());
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
        
        return array($quotedJoinTable, $whereClauses, $params);
    }
}