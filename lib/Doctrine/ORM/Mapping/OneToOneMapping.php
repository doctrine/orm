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
 * A one-to-one mapping describes a uni-directional mapping from one entity 
 * to another entity.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
class OneToOneMapping extends AssociationMapping
{
    /**
     * Maps the source foreign/primary key columns to the target primary/foreign key columns.
     * i.e. source.id (pk) => target.user_id (fk).
     * Reverse mapping of _targetToSourceKeyColumns.
     */
    private $_sourceToTargetKeyColumns = array();

    /**
     * Maps the target primary/foreign key columns to the source foreign/primary key columns.
     * i.e. target.user_id (fk) => source.id (pk).
     * Reverse mapping of _sourceToTargetKeyColumns.
     */
    private $_targetToSourceKeyColumns = array();
    
    /**
     * Whether to delete orphaned elements (when nulled out, i.e. $foo->other = null)
     * 
     * @var boolean
     */
    private $_deleteOrphans = false;

    /**
     * The join column definitions.
     *
     * @var array
     */
    private $_joinColumns = array();
    
    /**
     * Creates a new OneToOneMapping.
     *
     * @param array $mapping  The mapping info.
     */
    public function __construct(array $mapping)
    {
        parent::__construct($mapping);
    }
    
    /**
     * {@inheritdoc}
     *
     * @param array $mapping  The mapping to validate & complete.
     * @return array  The validated & completed mapping.
     * @override
     */
    protected function _validateAndCompleteMapping(array $mapping)
    {
        parent::_validateAndCompleteMapping($mapping);
        
        if ($this->isOwningSide()) {
            if ( ! isset($mapping['joinColumns'])) {
                throw MappingException::invalidMapping($this->_sourceFieldName);
            }
            $this->_joinColumns = $mapping['joinColumns'];
            foreach ($mapping['joinColumns'] as $joinColumn) {
                $this->_sourceToTargetKeyColumns[$joinColumn['name']] = $joinColumn['referencedColumnName'];
            }
            $this->_targetToSourceKeyColumns = array_flip($this->_sourceToTargetKeyColumns);
        }
        
        $this->_deleteOrphans = isset($mapping['deleteOrphans']) ?
                (bool)$mapping['deleteOrphans'] : false;
        
        return $mapping;
    }

    /**
     * Gets the join column definitions for this mapping.
     *
     * @return array
     */
    public function getJoinColumns()
    {
        return $this->_joinColumns;
    }
    
    /**
     * Gets the source-to-target key column mapping.
     *
     * @return array
     */
    public function getSourceToTargetKeyColumns()
    {
        return $this->_sourceToTargetKeyColumns;
    }
    
    /**
     * Gets the target-to-source key column mapping.
     *
     * @return array
     */
    public function getTargetToSourceKeyColumns()
    {
        return $this->_targetToSourceKeyColumns;
    }
    
    /**
     * {@inheritdoc}
     *
     * @return boolean
     * @override
     */
    public function isOneToOne()
    {
        return true;
    }
    
    /**
     * {@inheritdoc}
     *
     * @param object $owningEntity
     * @param object $targetEntity
     * @param EntityManager $em
     */
    public function load($owningEntity, $targetEntity, $em)
    {
        $sourceClass = $em->getClassMetadata($this->_sourceEntityName);
        $targetClass = $em->getClassMetadata($this->_targetEntityName);
        
        $conditions = array();

        if ($this->_isOwningSide) {
            foreach ($this->_sourceToTargetKeyColumns as $sourceKeyColumn => $targetKeyColumn) {
                $conditions[$targetKeyColumn] = $sourceClass->getReflectionProperty(
                    $sourceClass->getFieldName($sourceKeyColumn))->getValue($owningEntity);
            }
            if ($targetClass->hasInverseAssociation($this->_sourceFieldName)) {
                $targetClass->setFieldValue(
                        $targetEntity,
                        $targetClass->getInverseAssociationMapping($this->_sourceFieldName)->getSourceFieldName(),
                        $owningEntity);
            }
        } else {
            $owningAssoc = $em->getClassMetadata($this->_targetEntityName)->getAssociationMapping($this->_mappedByFieldName);
            foreach ($owningAssoc->getTargetToSourceKeyColumns() as $targetKeyColumn => $sourceKeyColumn) {
                $conditions[$sourceKeyColumn] = $sourceClass->getReflectionProperty(
                    $sourceClass->getFieldName($targetKeyColumn))->getValue($owningEntity);
            }
            $targetClass->setFieldValue($targetEntity, $this->_mappedByFieldName, $owningEntity);
        }

        $em->getUnitOfWork()->getEntityPersister($this->_targetEntityName)->load($conditions, $targetEntity);
    }
}