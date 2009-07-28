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

namespace Doctrine\ORM\Mapping;

/**
 * A many-to-many mapping describes the mapping between two collections of
 * entities.
 *
 * <b>IMPORTANT NOTE:</b>
 *
 * The fields of this class are only public for 2 reasons:
 * 1) To allow fast, internal READ access.
 * 2) To drastically reduce the size of a serialized instance (private/protected members
 *    get the whole class name, namespace inclusive, prepended to every property in
 *    the serialized representation).
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 */
class ManyToManyMapping extends AssociationMapping
{
    /**
     * The key columns of the source table.
     */
    public $sourceKeyColumns = array();

    /**
     * The key columns of the target table.
     */
    public $targetKeyColumns = array();

    /**
     * Maps the columns in the source table to the columns in the relation table.
     */
    public $sourceToRelationKeyColumns = array();

    /**
     * Maps the columns in the target table to the columns in the relation table.
     */
    public $targetToRelationKeyColumns = array();

    /**
     * The columns on the join table.
     */
    public $joinTableColumns = array();
    
    /** FUTURE: The key column mapping, if any. The key column holds the keys of the Collection. */
    //public $keyColumn;
    
    /**
     * Initializes a new ManyToManyMapping.
     *
     * @param array $mapping  The mapping information.
     */
    public function __construct(array $mapping)
    {
        parent::__construct($mapping);
    }
    
    /**
     * Validates and completes the mapping.
     *
     * @param array $mapping
     * @override
     */
    protected function _validateAndCompleteMapping(array $mapping)
    {
        parent::_validateAndCompleteMapping($mapping);
        if ($this->isOwningSide()) {
            // owning side MUST have a join table
            if ( ! isset($mapping['joinTable'])) {
                throw MappingException::joinTableRequired($mapping['fieldName']);
            }

            // owning side MUST specify joinColumns
            if ( ! isset($mapping['joinTable']['joinColumns'])) {
                throw MappingException::invalidMapping($this->_sourceFieldName);
            }
            foreach ($mapping['joinTable']['joinColumns'] as $joinColumn) {
                $this->sourceToRelationKeyColumns[$joinColumn['referencedColumnName']] = $joinColumn['name'];
                $this->joinTableColumns[] = $joinColumn['name'];
            }
            $this->sourceKeyColumns = array_keys($this->sourceToRelationKeyColumns);

            // owning side MUST specify inverseJoinColumns
            if ( ! isset($mapping['joinTable']['inverseJoinColumns'])) {
                throw MappingException::invalidMapping($this->_sourceFieldName);
            }
            foreach ($mapping['joinTable']['inverseJoinColumns'] as $inverseJoinColumn) {
                $this->targetToRelationKeyColumns[$inverseJoinColumn['referencedColumnName']] = $inverseJoinColumn['name'];
                $this->joinTableColumns[] = $inverseJoinColumn['name'];
            }
            $this->targetKeyColumns = array_keys($this->targetToRelationKeyColumns);
        }
    }

    public function getJoinTableColumns()
    {
        return $this->joinTableColumns;
    }

    public function getSourceToRelationKeyColumns()
    {
        return $this->sourceToRelationKeyColumns;
    }

    public function getTargetToRelationKeyColumns()
    {
        return $this->targetToRelationKeyColumns;
    }

    public function getSourceKeyColumns()
    {
        return $this->sourceKeyColumns;
    }

    public function getTargetKeyColumns()
    {
        return $this->targetKeyColumns;
    }

    /**
     * Loads entities in $targetCollection using $em.
     * The data of $sourceEntity are used to restrict the collection
     * via the join table.
     */
    public function load($sourceEntity, $targetCollection, $em, array $joinColumnValues = array())
    {
        $sourceClass = $em->getClassMetadata($this->sourceEntityName);
        $joinTableConditions = array();
        if ($this->isOwningSide()) {
            $joinTable = $this->getJoinTable();
            $joinClauses = $this->getTargetToRelationKeyColumns();
            foreach ($this->getSourceToRelationKeyColumns() as $sourceKeyColumn => $relationKeyColumn) {
                // getting id
                if (isset($sourceClass->reflFields[$sourceKeyColumn])) {
                    $joinTableConditions[$relationKeyColumn] = $this->_getPrivateValue($sourceClass, $sourceEntity, $sourceKeyColumn);
                } else {
                    $joinTableConditions[$relationKeyColumn] = $joinColumnValues[$sourceKeyColumn];
                }
            }
        } else {
            $owningAssoc = $em->getClassMetadata($this->targetEntityName)->getAssociationMapping($this->mappedByFieldName);
            $joinTable = $owningAssoc->getJoinTable();
            // TRICKY: since the association is inverted source and target are flipped
            $joinClauses = $owningAssoc->getSourceToRelationKeyColumns();
            foreach ($owningAssoc->getTargetToRelationKeyColumns() as $sourceKeyColumn => $relationKeyColumn) {
                // getting id
                if (isset($sourceClass->reflFields[$sourceKeyColumn])) {
                    $joinTableConditions[$relationKeyColumn] = $this->_getPrivateValue($sourceClass, $sourceEntity, $sourceKeyColumn);
                } else {
                    $joinTableConditions[$relationKeyColumn] = $joinColumnValues[$sourceKeyColumn];
                }
            }
        }
        $joinTableCriteria = array(
            'table' => $joinTable['name'],
            'join' => $joinClauses,
            'criteria' => $joinTableConditions
        );
        $persister = $em->getUnitOfWork()->getEntityPersister($this->targetEntityName);
        $persister->loadCollection(array($joinTableCriteria), $targetCollection);
    }

    /**
     * {@inheritdoc}
     *
     * @override
     */
    public function isManyToMany()
    {
        return true;
    }
}
