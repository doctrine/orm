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
 * Instances of this class are stored serialized in the metadata cache together with the
 * owning <tt>ClassMetadata</tt> instance.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 */
class ManyToManyMapping extends AssociationMapping
{
    /**
     * Maps the columns in the relational table to the columns in the source table.
     */
    public $relationToSourceKeyColumns = array();

    /**
     * Maps the columns in the relation table to the columns in the target table.
     */
    public $relationToTargetKeyColumns = array();

    /**
     * List of aggregated column names on the join table.
     */
    public $joinTableColumns = array();
    
    /** FUTURE: The key column mapping, if any. The key column holds the keys of the Collection. */
    //public $keyColumn;

    /**
     * Order this collection by the given DQL snippet.
     * 
     * Only simple unqualified field names and ASC|DESC are allowed
     *
     * @var array
     */
    public $orderBy = null;
    
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
            if ( ! isset($mapping['joinTable']) || ! $mapping['joinTable']) {
                // Apply default join table
                $sourceShortName = substr($this->sourceEntityName, strrpos($this->sourceEntityName, '\\') + 1);
                $targetShortName = substr($this->targetEntityName, strrpos($this->targetEntityName, '\\') + 1);
                $mapping['joinTable'] = array(
                    'name' => $sourceShortName .'_' . $targetShortName,
                    'joinColumns' => array(
                        array(
                            'name' => $sourceShortName . '_id',
                            'referencedColumnName' => 'id'
                        )
                    ),
                    'inverseJoinColumns' => array(
                        array(
                            'name' => $targetShortName . '_id',
                            'referencedColumnName' => 'id'
                        )
                    )
                );
                $this->joinTable = $mapping['joinTable'];
            }
            // owning side MUST specify joinColumns
            else if ( ! isset($mapping['joinTable']['joinColumns'])) {
                throw MappingException::missingRequiredOption(
                    $this->sourceFieldName, 'joinColumns', 
                    'Did you think of case sensitivity / plural s?'
                );
            }
            // owning side MUST specify inverseJoinColumns
            else if ( ! isset($mapping['joinTable']['inverseJoinColumns'])) {
                throw MappingException::missingRequiredOption(
                    $this->sourceFieldName, 'inverseJoinColumns', 
                    'Did you think of case sensitivity / plural s?'
                );
            }
            
            foreach ($mapping['joinTable']['joinColumns'] as &$joinColumn) {
                if ($joinColumn['name'][0] == '`') {
                    $joinColumn['name'] = trim($joinColumn['name'], '`');
                    $joinColumn['quoted'] = true;
                }
                $this->relationToSourceKeyColumns[$joinColumn['name']] = $joinColumn['referencedColumnName'];
                $this->joinTableColumns[] = $joinColumn['name'];
            }
            
            foreach ($mapping['joinTable']['inverseJoinColumns'] as &$inverseJoinColumn) {
                if ($inverseJoinColumn['name'][0] == '`') {
                    $inverseJoinColumn['name'] = trim($inverseJoinColumn['name'], '`');
                    $inverseJoinColumn['quoted'] = true;
                }
                $this->relationToTargetKeyColumns[$inverseJoinColumn['name']] = $inverseJoinColumn['referencedColumnName'];
                $this->joinTableColumns[] = $inverseJoinColumn['name'];
            }
        }

        if (isset($mapping['orderBy'])) {
            $parts = explode(",", $mapping['orderBy']);
            $orderByGroup = array();
            foreach ($parts AS $part) {
                $orderByItem = explode(" ", trim($part));
                if (count($orderByItem) == 1) {
                    $orderByGroup[$orderByItem[0]] = "ASC";
                } else {
                    $orderByGroup[$orderByItem[0]] = array_pop($orderByItem);
                }
            }

            $this->orderBy = $orderByGroup;
        }
    }

    public function getJoinTableColumnNames()
    {
        return $this->joinTableColumns;
        //return array_merge(array_keys($this->relationToSourceKeyColumns), array_keys($this->relationToTargetKeyColumns));
    }
    
    public function getRelationToSourceKeyColumns()
    {
        return $this->relationToSourceKeyColumns;
    }

    public function getRelationToTargetKeyColumns()
    {
        return $this->relationToTargetKeyColumns;
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
            foreach ($this->relationToSourceKeyColumns as $relationKeyColumn => $sourceKeyColumn) {
                // getting id
                if (isset($sourceClass->fieldNames[$sourceKeyColumn])) {
                    $joinTableConditions[$relationKeyColumn] = $sourceClass->reflFields[$sourceClass->fieldNames[$sourceKeyColumn]]->getValue($sourceEntity);
                } else {
                    throw MappingException::joinColumnMustPointToMappedField(
                        $sourceClass->name, $sourceKeyColumn
                    );
                }
            }
        } else {
            $owningAssoc = $em->getClassMetadata($this->targetEntityName)->associationMappings[$this->mappedByFieldName];
            // TRICKY: since the association is inverted source and target are flipped
            foreach ($owningAssoc->relationToTargetKeyColumns as $relationKeyColumn => $sourceKeyColumn) {
                // getting id
                if (isset($sourceClass->fieldNames[$sourceKeyColumn])) {
                    $joinTableConditions[$relationKeyColumn] = $sourceClass->reflFields[$sourceClass->fieldNames[$sourceKeyColumn]]->getValue($sourceEntity);
                } else {
                    throw MappingException::joinColumnMustPointToMappedField(
                        $sourceClass->name, $sourceKeyColumn
                    );
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
