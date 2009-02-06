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
 * Persister for one-to-many collections.
 * 
 * This persister is only used for uni-directional one-to-many mappings.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
class OneToManyPersister extends AbstractCollectionPersister
{
    /**
     * {@inheritdoc}
     *
     * @param <type> $coll
     * @return <type>
     * @override
     */
    protected function _getDeleteRowSql(PersistentCollection $coll)
    {
        $mapping = $coll->getMapping();
        $targetClass = $this->_em->getClassMetadata($mapping->getTargetEntityName());
        $table = $targetClass->getTableName();

        $ownerMapping = $targetClass->getAssociationMapping($mapping->getMappedByFieldName());

        $setClause = '';
        foreach ($ownerMapping->getSourceToTargetKeyColumns() as $sourceCol => $targetCol) {
            if ($setClause != '') $setClause .= ', ';
            $setClause .= "$sourceCol = NULL";
        }

        $whereClause = '';
        foreach ($targetClass->getIdentifierColumnNames() as $idColumn) {
            if ($whereClause != '') $whereClause .= ' AND ';
            $whereClause .= "$idColumn = ?";
        }

        return array("UPDATE $table SET $setClause WHERE $whereClause", $this->_uow->getEntityIdentifier($element));
    }

    protected function _getInsertRowSql()
    {
        return "UPDATE xxx SET foreign_key = yyy WHERE foreign_key = zzz";
    }

    /* Not used for OneToManyPersister */
    protected function _getUpdateRowSql()
    {
        return;
    }
    
}

