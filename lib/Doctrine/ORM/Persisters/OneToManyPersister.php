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

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;

/**
 * Persister for one-to-many collections.
 *
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Alexander <iam.asm89@gmail.com>
 * @since   2.0
 */
class OneToManyPersister extends AbstractCollectionPersister
{
    /**
     * {@inheritdoc}
     */
    public function get(PersistentCollection $coll, $index)
    {
        $mapping   = $coll->getMapping();
        $uow       = $this->em->getUnitOfWork();
        $persister = $uow->getEntityPersister($mapping['targetEntity']);

        if (!isset($mapping['indexBy'])) {
            throw new \BadMethodCallException("Selecting a collection by index is only supported on indexed collections.");
        }

        return $persister->load(array($mapping['mappedBy'] => $coll->getOwner(), $mapping['indexBy'] => $index), null, null, array(), null, 1);
    }

    /**
     * Generates the SQL UPDATE that updates a particular row's foreign
     * key to null.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     *
     * @return string
     *
     * @override
     */
    protected function getDeleteRowSQL(PersistentCollection $coll)
    {
        $mapping    = $coll->getMapping();
        $class      = $this->em->getClassMetadata($mapping['targetEntity']);
        $tableName  = $this->quoteStrategy->getTableName($class, $this->platform);
        $idColumns  = $class->getIdentifierColumnNames();

        return 'DELETE FROM ' . $tableName
             . ' WHERE ' . implode('= ? AND ', $idColumns) . ' = ?';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDeleteRowSQLParameters(PersistentCollection $coll, $element)
    {
        return array_values($this->uow->getEntityIdentifier($element));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \BadMethodCallException Not used for OneToManyPersister.
     */
    protected function getInsertRowSQL(PersistentCollection $coll)
    {
        throw new \BadMethodCallException("Insert Row SQL is not used for OneToManyPersister");
    }

    /**
     * {@inheritdoc}
     *
     * @throws \BadMethodCallException Not used for OneToManyPersister.
     */
    protected function getInsertRowSQLParameters(PersistentCollection $coll, $element)
    {
        throw new \BadMethodCallException("Insert Row SQL is not used for OneToManyPersister");
    }

    /**
     * {@inheritdoc}
     *
     * @throws \BadMethodCallException Not used for OneToManyPersister.
     */
    protected function getUpdateRowSQL(PersistentCollection $coll)
    {
        throw new \BadMethodCallException("Update Row SQL is not used for OneToManyPersister");
    }

    /**
     * {@inheritdoc}
     *
     * @throws \BadMethodCallException Not used for OneToManyPersister.
     */
    protected function getDeleteSQL(PersistentCollection $coll)
    {
        throw new \BadMethodCallException("Update Row SQL is not used for OneToManyPersister");
    }

    /**
     * {@inheritdoc}
     *
     * @throws \BadMethodCallException Not used for OneToManyPersister.
     */
    protected function getDeleteSQLParameters(PersistentCollection $coll)
    {
        throw new \BadMethodCallException("Update Row SQL is not used for OneToManyPersister");
    }

    /**
     * {@inheritdoc}
     */
    public function count(PersistentCollection $coll)
    {
        list($quotedJoinTable, $whereClauses, $params) = $this->getJoinTableRestrictions($coll, true);

        $sql = 'SELECT count(*) FROM ' . $quotedJoinTable . ' WHERE ' . implode(' AND ', $whereClauses);

        return $this->conn->fetchColumn($sql, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function slice(PersistentCollection $coll, $offset, $length = null)
    {
        $mapping   = $coll->getMapping();
        $uow       = $this->em->getUnitOfWork();
        $persister = $uow->getEntityPersister($mapping['targetEntity']);

        return $persister->getOneToManyCollection($mapping, $coll->getOwner(), $offset, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function containsKey(PersistentCollection $coll, $key)
    {
        list($quotedJoinTable, $whereClauses, $params) = $this->getJoinTableRestrictions($coll, true);

        $mapping     = $coll->getMapping();
        $sourceClass = $this->em->getClassMetadata($mapping['sourceEntity']);

        $whereClauses[] = $sourceClass->getColumnName($mapping['indexBy']) . ' = ?';
        $params[] = $key;

        $sql = 'SELECT 1 FROM ' . $quotedJoinTable . ' WHERE ' . implode(' AND ', $whereClauses);

        return (bool) $this->conn->fetchColumn($sql, $params);
    }

    private function getJoinTableRestrictions(PersistentCollection $coll, $addFilters)
    {
        $mapping     = $coll->getMapping();
        $targetClass = $this->em->getClassMetadata($mapping['targetEntity']);
        $sourceClass = $this->em->getClassMetadata($mapping['sourceEntity']);
        $id          = $this->em->getUnitOfWork()->getEntityIdentifier($coll->getOwner());

        $whereClauses = array();
        $params       = array();

        $joinColumns = $targetClass->associationMappings[$mapping['mappedBy']]['joinColumns'];
        foreach ($joinColumns as $joinColumn) {
            $whereClauses[] = $joinColumn['name'] . ' = ?';

            $params[] = ($targetClass->containsForeignIdentifier)
                ? $id[$sourceClass->getFieldForColumn($joinColumn['referencedColumnName'])]
                : $id[$sourceClass->fieldNames[$joinColumn['referencedColumnName']]];
        }

        if ($addFilters) {
            $filterTargetClass = $this->em->getClassMetadata($targetClass->rootEntityName);
            foreach ($this->em->getFilters()->getEnabledFilters() as $filter) {
                if ($filterExpr = $filter->addFilterConstraint($filterTargetClass, 't')) {
                    $whereClauses[] = '(' . $filterExpr . ')';
                }
            }
        }

        $quotedJoinTable = $this->quoteStrategy->getTableName($targetClass, $this->platform) . ' t';

        return array($quotedJoinTable, $whereClauses, $params);
    }

     /**
     * {@inheritdoc}
     */
    public function contains(PersistentCollection $coll, $element)
    {
        $mapping = $coll->getMapping();
        $uow     = $this->em->getUnitOfWork();

        // shortcut for new entities
        $entityState = $uow->getEntityState($element, UnitOfWork::STATE_NEW);

        if ($entityState === UnitOfWork::STATE_NEW) {
            return false;
        }

        // Entity is scheduled for inclusion
        if ($entityState === UnitOfWork::STATE_MANAGED && $uow->isScheduledForInsert($element)) {
            return false;
        }

        $persister = $uow->getEntityPersister($mapping['targetEntity']);

        // only works with single id identifier entities. Will throw an
        // exception in Entity Persisters if that is not the case for the
        // 'mappedBy' field.
        $criteria = new Criteria(Criteria::expr()->eq($mapping['mappedBy'], $coll->getOwner()));

        return $persister->exists($element, $criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function removeElement(PersistentCollection $coll, $element)
    {
        $uow = $this->em->getUnitOfWork();

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

        $mapping = $coll->getMapping();
        $class   = $this->em->getClassMetadata($mapping['targetEntity']);
        $sql     = 'DELETE FROM ' . $this->quoteStrategy->getTableName($class, $this->platform)
                 . ' WHERE ' . implode('= ? AND ', $class->getIdentifierColumnNames()) . ' = ?';

        return (bool) $this->conn->executeUpdate($sql, $this->getDeleteRowSQLParameters($coll, $element));
    }
}
