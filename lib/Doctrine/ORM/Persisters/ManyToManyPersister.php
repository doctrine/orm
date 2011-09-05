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
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
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
        $mapping   = $coll->getMapping();
        $joinTable = $mapping['joinTable'];
        $columns   = $mapping['joinTableColumns'];
        
        return 'DELETE FROM ' . $joinTable['name'] . ' WHERE ' . implode(' = ? AND ', $columns) . ' = ?';
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
        $joinTable = $mapping['joinTable'];
        $columns = $mapping['joinTableColumns'];
        return 'INSERT INTO ' . $joinTable['name'] . ' (' . implode(', ', $columns) . ')'
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
        $params = array();
        $mapping = $coll->getMapping();
        $isComposite = count($mapping['joinTableColumns']) > 2;

        $identifier1 = $this->_uow->getEntityIdentifier($coll->getOwner());
        $identifier2 = $this->_uow->getEntityIdentifier($element);

        if ($isComposite) {
            $class1 = $this->_em->getClassMetadata(get_class($coll->getOwner()));
            $class2 = $coll->getTypeClass();
        }

        foreach ($mapping['joinTableColumns'] as $joinTableColumn) {
            if (isset($mapping['relationToSourceKeyColumns'][$joinTableColumn])) {
                if ($isComposite) {
                    if ($class1->containsForeignIdentifier) {
                        $params[] = $identifier1[$class1->getFieldForColumn($mapping['relationToSourceKeyColumns'][$joinTableColumn])];
                    } else {
                        $params[] = $identifier1[$class1->fieldNames[$mapping['relationToSourceKeyColumns'][$joinTableColumn]]];
                    }
                } else {
                    $params[] = array_pop($identifier1);
                }
            } else {
                if ($isComposite) {
                    if ($class2->containsForeignIdentifier) {
                        $params[] = $identifier2[$class2->getFieldForColumn($mapping['relationToTargetKeyColumns'][$joinTableColumn])];
                    } else {
                        $params[] = $identifier2[$class2->fieldNames[$mapping['relationToTargetKeyColumns'][$joinTableColumn]]];
                    }
                } else {
                    $params[] = array_pop($identifier2);
                }
            }
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
        $mapping = $coll->getMapping();
        $joinTable = $mapping['joinTable'];
        $whereClause = '';
        foreach ($mapping['relationToSourceKeyColumns'] as $relationColumn => $srcColumn) {
            if ($whereClause !== '') $whereClause .= ' AND ';
            $whereClause .= "$relationColumn = ?";
        }
        return 'DELETE FROM ' . $joinTable['name'] . ' WHERE ' . $whereClause;
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
        $params = array();
        $mapping = $coll->getMapping();
        $identifier = $this->_uow->getEntityIdentifier($coll->getOwner());
        if (count($mapping['relationToSourceKeyColumns']) > 1) {
            $sourceClass = $this->_em->getClassMetadata(get_class($mapping->getOwner()));
            foreach ($mapping['relationToSourceKeyColumns'] as $relColumn => $srcColumn) {
                $params[] = $identifier[$sourceClass->fieldNames[$srcColumn]];
            }
        } else {
           $params[] = array_pop($identifier);
        }

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function count(PersistentCollection $coll)
    {
        $params = array();
        $mapping = $coll->getMapping();
        $class = $this->_em->getClassMetadata($mapping['sourceEntity']);
        $id = $this->_em->getUnitOfWork()->getEntityIdentifier($coll->getOwner());

        if ($mapping['isOwningSide']) {
            $joinTable = $mapping['joinTable'];
            $joinColumns = $mapping['relationToSourceKeyColumns'];
        } else {
            $mapping = $this->_em->getClassMetadata($mapping['targetEntity'])->associationMappings[$mapping['mappedBy']];
            $joinTable = $mapping['joinTable'];
            $joinColumns = $mapping['relationToTargetKeyColumns'];
        }

        $whereClause = '';
        foreach ($mapping['joinTableColumns'] as $joinTableColumn) {
            if (isset($joinColumns[$joinTableColumn])) {
                if ($whereClause !== '') {
                    $whereClause .= ' AND ';
                }
                $whereClause .= "$joinTableColumn = ?";

                if ($class->containsForeignIdentifier) {
                    $params[] = $id[$class->getFieldForColumn($joinColumns[$joinTableColumn])];
                } else {
                    $params[] = $id[$class->fieldNames[$joinColumns[$joinTableColumn]]];
                }
            }
        }
        $sql = 'SELECT count(*) FROM ' . $joinTable['name'] . ' WHERE ' . $whereClause;

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
        return $this->_em->getUnitOfWork()
                  ->getEntityPersister($mapping['targetEntity'])
                  ->getManyToManyCollection($mapping, $coll->getOwner(), $offset, $length);
    }

    /**
     * @param PersistentCollection $coll
     * @param object $element
     */
    public function contains(PersistentCollection $coll, $element)
    {
        $uow = $this->_em->getUnitOfWork();

        // shortcut for new entities
        if ($uow->getEntityState($element, UnitOfWork::STATE_NEW) == UnitOfWork::STATE_NEW) {
            return false;
        }

        $params = array();
        $mapping = $coll->getMapping();

        if (!$mapping['isOwningSide']) {
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
        $joinTable = $mapping['joinTable'];

        $whereClause = '';
        foreach ($mapping['joinTableColumns'] as $joinTableColumn) {
            if (isset($mapping['relationToTargetKeyColumns'][$joinTableColumn])) {
                if ($whereClause !== '') {
                    $whereClause .= ' AND ';
                }
                $whereClause .= "$joinTableColumn = ?";

                if ($targetClass->containsForeignIdentifier) {
                    $params[] = $targetId[$targetClass->getFieldForColumn($mapping['relationToTargetKeyColumns'][$joinTableColumn])];
                } else {
                    $params[] = $targetId[$targetClass->fieldNames[$mapping['relationToTargetKeyColumns'][$joinTableColumn]]];
                }
            } else if (isset($mapping['relationToSourceKeyColumns'][$joinTableColumn])) {
                if ($whereClause !== '') {
                    $whereClause .= ' AND ';
                }
                $whereClause .= "$joinTableColumn = ?";

                if ($sourceClass->containsForeignIdentifier) {
                    $params[] = $sourceId[$sourceClass->getFieldForColumn($mapping['relationToSourceKeyColumns'][$joinTableColumn])];
                } else {
                    $params[] = $sourceId[$sourceClass->fieldNames[$mapping['relationToSourceKeyColumns'][$joinTableColumn]]];
                }
            }
        }
        $sql = 'SELECT 1 FROM ' . $joinTable['name'] . ' WHERE ' . $whereClause;

        return (bool)$this->_conn->fetchColumn($sql, $params);
    }
}