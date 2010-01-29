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
class OneToOneMapping extends AssociationMapping
{
    /**
     * Maps the source foreign/primary key columns to the target primary/foreign key columns.
     * i.e. source.id (pk) => target.user_id (fk).
     * Reverse mapping of _targetToSourceKeyColumns.
     */
    public $sourceToTargetKeyColumns = array();

    /**
     * Maps the target primary/foreign key columns to the source foreign/primary key columns.
     * i.e. target.user_id (fk) => source.id (pk).
     * Reverse mapping of _sourceToTargetKeyColumns.
     */
    public $targetToSourceKeyColumns = array();
    
    /**
     * Whether to delete orphaned elements (when nulled out, i.e. $foo->other = null)
     * 
     * @var boolean
     */
    public $orphanRemoval = false;
    
    /**
     * Whether the association is optional (0..1) or not (1..1).
     * By default all associations are optional.
     *
     * @var boolean
     */
    public $isOptional = true;

    /**
     * The join column definitions.
     *
     * @var array
     */
    public $joinColumns = array();
    
    /**
     * A map of join column names to field names that are used in cases
     * when the join columns are fetched as part of the query result.
     * 
     * @var array
     */
    public $joinColumnFieldNames = array();
    
    /**
     * Creates a new OneToOneMapping.
     *
     * @param array $mapping The mapping info.
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
        
        if (isset($mapping['joinColumns']) && $mapping['joinColumns']) {
            $this->isOwningSide = true;
        }
        
        if ($this->isOwningSide) {
            if ( ! isset($mapping['joinColumns'])) {
                throw MappingException::invalidMapping($this->sourceFieldName);
            }
            foreach ($mapping['joinColumns'] as &$joinColumn) {
                if ($joinColumn['name'][0] == '`') {
                    $joinColumn['name'] = trim($joinColumn['name'], '`');
                    $joinColumn['quoted'] = true;
                }
                $this->sourceToTargetKeyColumns[$joinColumn['name']] = $joinColumn['referencedColumnName'];
                $this->joinColumnFieldNames[$joinColumn['name']] = isset($joinColumn['fieldName'])
                        ? $joinColumn['fieldName'] : $joinColumn['name'];
            }
            $this->joinColumns = $mapping['joinColumns'];
            $this->targetToSourceKeyColumns = array_flip($this->sourceToTargetKeyColumns);
        }
        
        $this->isOptional = isset($mapping['optional']) ?
                (bool)$mapping['optional'] : true;
        $this->orphanRemoval = isset($mapping['orphanRemoval']) ?
                (bool) $mapping['orphanRemoval'] : false;
        
        /*if ($this->isOptional) {
            $this->fetchMode = self::FETCH_EAGER;
        }*/
        
        return $mapping;
    }
    
    /**
     * Whether the association is optional (0..1), or not (1..1).
     *
     * @return boolean TRUE if the association is optional, FALSE otherwise.
     * @todo Only applicable to OneToOne. Move there.
     */
    public function isOptional()
    {
        return $this->isOptional;
    }

    /**
     * Gets the join column definitions for this mapping.
     *
     * @return array
     */
    public function getJoinColumns()
    {
        return $this->joinColumns;
    }
    
    /**
     * Gets the source-to-target key column mapping.
     *
     * @return array
     */
    public function getSourceToTargetKeyColumns()
    {
        return $this->sourceToTargetKeyColumns;
    }
    
    /**
     * Gets the target-to-source key column mapping.
     *
     * @return array
     */
    public function getTargetToSourceKeyColumns()
    {
        return $this->targetToSourceKeyColumns;
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
     * Gets the (possibly quoted) column name of a join column that is safe to use
     * in an SQL statement.
     * 
     * @param string $joinColumn
     * @param AbstractPlatform $platform
     * @return string
     */
    public function getQuotedJoinColumnName($joinColumn, $platform)
    {
        return isset($this->joinColumns[$joinColumn]['quoted']) ?
                $platform->quoteIdentifier($joinColumn) :
                $joinColumn;
    }
    
    /**
     * {@inheritdoc}
     *
     * @param object $sourceEntity      the entity source of this association
     * @param object $targetEntity      the entity to load data in
     * @param EntityManager $em
     * @param array $joinColumnValues  Values of the join columns of $sourceEntity.
     */
    public function load($sourceEntity, $targetEntity, $em, array $joinColumnValues = array())
    {
        $targetClass = $em->getClassMetadata($this->targetEntityName);

        if ($this->isOwningSide) {
            $inverseField = isset($targetClass->inverseMappings[$this->sourceEntityName][$this->sourceFieldName]) ?
                    $targetClass->inverseMappings[$this->sourceEntityName][$this->sourceFieldName]->sourceFieldName
                    : false;
            
            // Mark inverse side as fetched in the hints, otherwise the UoW would
            // try to load it in a separate query (remember: to-one inverse sides can not be lazy). 
            $hints = array();
            if ($inverseField) {
                $hints['fetched'][$targetClass->name][$inverseField] = true;
                if ($targetClass->subClasses) {
                    foreach ($targetClass->subClasses as $targetSubclassName) {
                        $hints['fetched'][$targetSubclassName][$inverseField] = true;
                    }
                }
            }
            /* cascade read-only status
            if ($em->getUnitOfWork()->isReadOnly($sourceEntity)) {
                $hints[Query::HINT_READ_ONLY] = true;
            }
            */

            $targetEntity = $em->getUnitOfWork()->getEntityPersister($this->targetEntityName)->load($joinColumnValues, $targetEntity, $this, $hints);
            
            if ($targetEntity !== null && $inverseField && ! $targetClass->isCollectionValuedAssociation($inverseField)) {
                $targetClass->reflFields[$inverseField]->setValue($targetEntity, $sourceEntity);
            }
        } else {
            $conditions = array();
            $sourceClass = $em->getClassMetadata($this->sourceEntityName);
            $owningAssoc = $targetClass->getAssociationMapping($this->mappedByFieldName);
            // TRICKY: since the association is specular source and target are flipped
            foreach ($owningAssoc->targetToSourceKeyColumns as $sourceKeyColumn => $targetKeyColumn) {
                if (isset($sourceClass->fieldNames[$sourceKeyColumn])) {
                    $conditions[$targetKeyColumn] = $sourceClass->reflFields[$sourceClass->fieldNames[$sourceKeyColumn]]->getValue($sourceEntity);
                } else {
                    throw MappingException::joinColumnMustPointToMappedField(
                        $sourceClass->name, $sourceKeyColumn
                    );
                }
            }
            
            $targetEntity = $em->getUnitOfWork()->getEntityPersister($this->targetEntityName)->load($conditions, $targetEntity, $this);
            
            if ($targetEntity !== null) {
                $targetClass->setFieldValue($targetEntity, $this->mappedByFieldName, $sourceEntity);
            }
            
        }
        
        return $targetEntity;
    }
    
    /**
     * @internal Experimental. For MetaModel API, Doctrine 2.1 or later.
     */
    public static function __set_state(array $state)
    {
        $assoc = new self(array());
        $assoc->isOptional = $state['isOptional'];
        $assoc->joinColumnFieldNames = $state['joinColumnFieldNames'];
        $assoc->joinColumns = $state['joinColumns'];
        $assoc->orphanRemoval = $state['orphanRemoval'];
        $assoc->sourceToTargetKeyColumns = $state['sourceToTargetKeyColumns'];
        $assoc->targetToSourceKeyColumns = $state['targetToSourceKeyColumns'];
        
        $assoc->fetchMode = $state['fetchMode'];
        $assoc->isCascadeDetach = $state['isCascadeDetach'];
        $assoc->isCascadeRefresh = $state['isCascadeRefresh'];
        $assoc->isCascadeRemove = $state['isCascadeRemove'];
        $assoc->isCascadePersist = $state['isCascadePersist'];
        $assoc->isCascadeMerge = $state['isCascadeMerge'];
        $assoc->isOwningSide = $state['isOwningSide'];
        $assoc->joinTable = $state['joinTable'];
        $assoc->mappedByFieldName = $state['mappedByFieldName'];
        $assoc->sourceEntityName = $state['sourceEntityName'];
        $assoc->targetEntityName = $state['targetEntityName'];
        $assoc->sourceFieldName = $state['sourceFieldName'];
        
        return $assoc;
    }
}
