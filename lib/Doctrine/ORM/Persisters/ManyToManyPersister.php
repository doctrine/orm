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

use Doctrine\ORM\PersistentCollection;

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
    protected function _getDeleteRowSql(PersistentCollection $coll)
    {
        $mapping = $coll->getMapping();
        $joinTable = $mapping->getJoinTable();
        $columns = $mapping->getJoinTableColumnNames();
        return 'DELETE FROM ' . $joinTable['name'] . ' WHERE ' . implode(' = ? AND ', $columns) . ' = ?';
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    protected function _getDeleteRowSqlParameters(PersistentCollection $coll, $element)
    {
        $params = array_merge(
                $this->_uow->getEntityIdentifier($coll->getOwner()),
                $this->_uow->getEntityIdentifier($element)
                );
        //var_dump($params);
        return $params;
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    protected function _getUpdateRowSql(PersistentCollection $coll)
    {}

    /**
     * {@inheritdoc}
     *
     * @override
     */
    protected function _getInsertRowSql(PersistentCollection $coll)
    {
        $mapping = $coll->getMapping();
        $joinTable = $mapping->getJoinTable();
        $columns = $mapping->getJoinTableColumnNames();
        return 'INSERT INTO ' . $joinTable['name'] . ' (' . implode(', ', $columns) . ')'
                . ' VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')';
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    protected function _getInsertRowSqlParameters(PersistentCollection $coll, $element)
    {
        // FIXME: This is still problematic for composite keys because we silently
        // rely on a specific ordering of the columns.
        $params = array_merge(
                $this->_uow->getEntityIdentifier($coll->getOwner()),
                $this->_uow->getEntityIdentifier($element)
                );
        //var_dump($params);
        return $params;
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    protected function _getDeleteSql(PersistentCollection $coll)
    {
        $mapping = $coll->getMapping();
        $joinTable = $mapping->getJoinTable();
        $whereClause = '';
        foreach ($mapping->sourceToRelationKeyColumns as $relationColumn) {
            if ($whereClause !== '') $whereClause .= ' AND ';
            $whereClause .= "$relationColumn = ?";
        }
        return 'DELETE FROM ' . $joinTable['name'] . ' WHERE ' . $whereClause;
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    protected function _getDeleteSqlParameters(PersistentCollection $coll)
    {
        //FIXME: This is still problematic for composite keys because we silently
        // rely on a specific ordering of the columns.
        return $this->_uow->getEntityIdentifier($coll->getOwner());
    }
}