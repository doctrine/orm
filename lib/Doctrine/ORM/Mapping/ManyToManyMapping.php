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
     * List of aggregated column names on the join table.
     */
    public $joinTableColumns = array();
    
    /** FUTURE: The key column mapping, if any. The key column holds the keys of the Collection. */
    //public $keyColumn;
    
    /**
     * Initializes a new ManyToManyMapping.
     *
     * @param array $mapping The mapping definition.
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
        if ($this->isOwningSide) {
            // owning side MUST have a join table
            if ( ! isset($mapping['joinTable'])) {
                throw MappingException::joinTableRequired($mapping['fieldName']);
            }
            // owning side MUST specify joinColumns
            if ( ! isset($mapping['joinTable']['joinColumns'])) {
                throw MappingException::invalidMapping($this->_sourceFieldName);
            }
            // owning side MUST specify inverseJoinColumns
            if ( ! isset($mapping['joinTable']['inverseJoinColumns'])) {
                throw MappingException::invalidMapping($this->_sourceFieldName);
            }
            
            foreach ($mapping['joinTable']['joinColumns'] as &$joinColumn) {
                if ($joinColumn['name'][0] == '`') {
                    $joinColumn['name'] = trim($joinColumn['name'], '`');
                    $joinColumn['quoted'] = true;
                }
                $this->sourceToRelationKeyColumns[$joinColumn['referencedColumnName']] = $joinColumn['name'];
                $this->joinTableColumns[] = $joinColumn['name'];
            }
            $this->sourceKeyColumns = array_keys($this->sourceToRelationKeyColumns);
            
            foreach ($mapping['joinTable']['inverseJoinColumns'] as &$inverseJoinColumn) {
                if ($inverseJoinColumn['name'][0] == '`') {
                    $inverseJoinColumn['name'] = trim($inverseJoinColumn['name'], '`');
                    $inverseJoinColumn['quoted'] = true;
                }
                $this->targetToRelationKeyColumns[$inverseJoinColumn['referencedColumnName']] = $inverseJoinColumn['name'];
                $this->joinTableColumns[] = $inverseJoinColumn['name'];
            }
            $this->targetKeyColumns = array_keys($this->targetToRelationKeyColumns);
        }
    }

    public function getJoinTableColumnNames()
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
     * 
     * @param object The owner of the collection.
     * @param object The collection to populate.
     * @param array
     */
    public function load($sourceEntity, $targetCollection, $em, array $joinColumnValues = array())
    {
        $sourceClass = $em->getClassMetadata($this->sourceEntityName);
        $joinTableConditions = array();
        if ($this->isOwningSide) {
            foreach ($this->sourceToRelationKeyColumns as $sourceKeyColumn => $relationKeyColumn) {
                // getting id
                if (isset($sourceClass->reflFields[$sourceKeyColumn])) {
                    $joinTableConditions[$relationKeyColumn] = $sourceClass->reflFields[$sourceClass->fieldNames[$sourceKeyColumn]]->getValue($sourceEntity);
                } else {
                    $joinTableConditions[$relationKeyColumn] = $joinColumnValues[$sourceKeyColumn];
                }
            }
        } else {
            $owningAssoc = $em->getClassMetadata($this->targetEntityName)->associationMappings[$this->mappedByFieldName];
            // TRICKY: since the association is inverted source and target are flipped
            foreach ($owningAssoc->targetToRelationKeyColumns as $sourceKeyColumn => $relationKeyColumn) {
                // getting id
                if (isset($sourceClass->reflFields[$sourceKeyColumn])) {
                    $joinTableConditions[$relationKeyColumn] = $sourceClass->reflFields[$sourceClass->fieldNames[$sourceKeyColumn]]->getValue($sourceEntity);
                } else {
                    $joinTableConditions[$relationKeyColumn] = $joinColumnValues[$sourceKeyColumn];
                }
            }
        }

        $persister = $em->getUnitOfWork()->getEntityPersister($this->targetEntityName);
        $persister->loadManyToManyCollection($this, $joinTableConditions, $targetCollection);
    }

    /**
     * {@inheritdoc}
     */
    public function isManyToMany()
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
        return isset($this->joinTable['joinColumns'][$joinColumn]['quoted']) ?
                $platform->quoteIdentifier($joinColumn) :
                $joinColumn;
    }
}
