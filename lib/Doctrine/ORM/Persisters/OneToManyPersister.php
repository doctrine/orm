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

use Doctrine\ORM\PersistentCollection,
    Doctrine\ORM\UnitOfWork;

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
     * Generates the SQL UPDATE that updates a particular row's foreign
     * key to null.
     *
     * @param PersistentCollection $coll
     * @return string
     * @override
     */
    protected function _getDeleteRowSQL(PersistentCollection $coll)
    {
        $mapping = $coll->getMapping();
        $class   = $this->_em->getClassMetadata($mapping['targetEntity']);

        return 'DELETE FROM ' . $this->quoteStrategy->getTableName($class, $this->platform)
             . ' WHERE ' . implode('= ? AND ', $class->getIdentifierColumnNames()) . ' = ?';
    }

    /**
     * {@inheritdoc}
     *
     */
    protected function _getDeleteRowSQLParameters(PersistentCollection $coll, $element)
    {
        return array_values($this->_uow->getEntityIdentifier($element));
    }

    protected function _getInsertRowSQL(PersistentCollection $coll)
    {
        return "UPDATE xxx SET foreign_key = yyy WHERE foreign_key = zzz";
    }

    /**
     * Gets the SQL parameters for the corresponding SQL statement to insert the given
     * element of the given collection into the database.
     *
     * @param PersistentCollection $coll
     * @param mixed $element
     */
    protected function _getInsertRowSQLParameters(PersistentCollection $coll, $element)
    {}

    /* Not used for OneToManyPersister */
    protected function _getUpdateRowSQL(PersistentCollection $coll)
    {
        return;
    }

    /**
     * Generates the SQL UPDATE that updates all the foreign keys to null.
     *
     * @param PersistentCollection $coll
     */
    protected function _getDeleteSQL(PersistentCollection $coll)
    {

    }

    /**
     * Gets the SQL parameters for the corresponding SQL statement to delete
     * the given collection.
     *
     * @param PersistentCollection $coll
     */
    protected function _getDeleteSQLParameters(PersistentCollection $coll)
    {}

    /**
     * {@inheritdoc}
     */
    public function count(PersistentCollection $coll)
    {
        $mapping     = $coll->getMapping();
        $targetClass = $this->_em->getClassMetadata($mapping['targetEntity']);
        $sourceClass = $this->_em->getClassMetadata($mapping['sourceEntity']);
        $id          = $this->_em->getUnitOfWork()->getEntityIdentifier($coll->getOwner());

        $whereClauses = array();
        $params       = array();

        foreach ($targetClass->associationMappings[$mapping['mappedBy']]['joinColumns'] as $joinColumn) {
            $whereClauses[] = $joinColumn['name'] . ' = ?';

            $params[] = ($targetClass->containsForeignIdentifier)
                ? $id[$sourceClass->getFieldForColumn($joinColumn['referencedColumnName'])]
                : $id[$sourceClass->fieldNames[$joinColumn['referencedColumnName']]];
        }

        $filterTargetClass = $this->_em->getClassMetadata($targetClass->rootEntityName);
        foreach ($this->_em->getFilters()->getEnabledFilters() as $filter) {
            if ($filterExpr = $filter->addFilterConstraint($filterTargetClass, 't')) {
                $whereClauses[] = '(' . $filterExpr . ')';
            }
        }

        $sql = 'SELECT count(*)'
             . ' FROM ' . $this->quoteStrategy->getTableName($targetClass, $this->platform) . ' t'
             . ' WHERE ' . implode(' AND ', $whereClauses);

        return $this->_conn->fetchColumn($sql, $params);
    }

    /**
     * @param PersistentCollection $coll
     * @param int $offset
     * @param int $length
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function slice(PersistentCollection $coll, $offset, $length = null)
    {
        $mapping   = $coll->getMapping();
        $uow       = $this->_em->getUnitOfWork();
        $persister = $uow->getEntityPersister($mapping['targetEntity']);

        return $persister->getOneToManyCollection($mapping, $coll->getOwner(), $offset, $length);
    }

    /**
     * @param PersistentCollection $coll
     * @param object $element
     * @return boolean
     */
    public function contains(PersistentCollection $coll, $element)
    {
        $mapping = $coll->getMapping();
        $uow     = $this->_em->getUnitOfWork();

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
        $id = current( $uow->getEntityIdentifier($coll->getOwner()));

        return $persister->exists($element, array($mapping['mappedBy'] => $id));
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

        $mapping = $coll->getMapping();
        $class   = $this->_em->getClassMetadata($mapping['targetEntity']);
        $sql     = 'DELETE FROM ' . $this->quoteStrategy->getTableName($class, $this->platform)
                 . ' WHERE ' . implode('= ? AND ', $class->getIdentifierColumnNames()) . ' = ?';

        return (bool) $this->_conn->executeUpdate($sql, $this->_getDeleteRowSQLParameters($coll, $element));
    }
}
