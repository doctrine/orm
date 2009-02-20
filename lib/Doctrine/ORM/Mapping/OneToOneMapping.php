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
 * <http://www.phpdoctrine.org>.
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
     * @param Doctrine\ORM\Entity $entity
     * @return void
     */
    public function lazyLoadFor($entity, $entityManager)
    {
        $sourceClass = $entityManager->getClassMetadata($this->_sourceEntityName);
        $targetClass = $entityManager->getClassMetadata($this->_targetEntityName);

        $dql = 'SELECT t FROM ' . $targetClass->getClassName() . ' t WHERE ';
        $params = array();
        foreach ($this->_sourceToTargetKeyFields as $sourceKeyField => $targetKeyField) {
            if ($params) $dql .= " AND ";
            $dql .= "t.$targetKeyField = ?";
            $params[] = $sourceClass->getReflectionProperty($sourceKeyField)->getValue($entity);
        }
        
        $otherEntity = $entityManager->query($dql, $params)->getFirst();
            
        if ( ! $otherEntity) {
            $otherEntity = null;
        }
        $sourceClass->getReflectionProperty($this->_sourceFieldName)->setValue($entity, $otherEntity);
    }
}