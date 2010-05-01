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
 * 1) To allow fast READ access.
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
 * @todo Potentially remove if assoc mapping objects get replaced by simple arrays.
 */
class OneToOneMapping extends AssociationMapping
{
    /**
     * READ-ONLY: Maps the source foreign/primary key columns to the target primary/foreign key columns.
     * i.e. source.id (pk) => target.user_id (fk).
     * Reverse mapping of _targetToSourceKeyColumns.
     */
    public $sourceToTargetKeyColumns = array();

    /**
     * READ-ONLY: Maps the target primary/foreign key columns to the source foreign/primary key columns.
     * i.e. target.user_id (fk) => source.id (pk).
     * Reverse mapping of _sourceToTargetKeyColumns.
     */
    public $targetToSourceKeyColumns = array();
    
    /**
     * READ-ONLY: Whether to delete orphaned elements (when nulled out, i.e. $foo->other = null)
     * 
     * @var boolean
     */
    public $orphanRemoval = false;

    /**
     * READ-ONLY: The join column definitions. Only present on the owning side.
     *
     * @var array
     */
    public $joinColumns = array();
    
    /**
     * READ-ONLY: A map of join column names to field names that are used in cases
     * when the join columns are fetched as part of the query result.
     * 
     * @var array
     */
    public $joinColumnFieldNames = array();

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
            if ( ! isset($mapping['joinColumns']) || ! $mapping['joinColumns']) {
                // Apply default join column
                $mapping['joinColumns'] = array(array(
                    'name' => $this->sourceFieldName . '_id',
                    'referencedColumnName' => 'id'
                ));
            }
            foreach ($mapping['joinColumns'] as $joinColumn) {
                $this->sourceToTargetKeyColumns[$joinColumn['name']] = $joinColumn['referencedColumnName'];
                $this->joinColumnFieldNames[$joinColumn['name']] = isset($joinColumn['fieldName'])
                        ? $joinColumn['fieldName'] : $joinColumn['name'];
            }
            $this->joinColumns = $mapping['joinColumns'];
            $this->targetToSourceKeyColumns = array_flip($this->sourceToTargetKeyColumns);
        }

        //TODO: if orphanRemoval, cascade=remove is implicit!
        $this->orphanRemoval = isset($mapping['orphanRemoval']) ?
                (bool) $mapping['orphanRemoval'] : false;

        return $mapping;
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
     * @param object $sourceEntity      the entity source of this association
     * @param object $targetEntity      the entity to load data in
     * @param EntityManager $em
     * @param array $joinColumnValues  Values of the join columns of $sourceEntity.
     * @todo Remove
     */
    public function load($sourceEntity, $targetEntity, $em, array $joinColumnValues = array())
    {
        return $em->getUnitOfWork()->getEntityPersister($this->targetEntityName)->loadOneToOneEntity($this, $sourceEntity, $targetEntity, $joinColumnValues);
    }

    /**
     * Determines which fields get serialized.
     *
     * It is only serialized what is necessary for best unserialization performance.
     * That means any metadata properties that are not set or empty or simply have
     * their default value are NOT serialized.
     *
     * @return array The names of all the fields that should be serialized.
     */
    public function __sleep()
    {
        $serialized = parent::__sleep();
        $serialized[] = 'joinColumns';
        $serialized[] = 'joinColumnFieldNames';
        $serialized[] = 'sourceToTargetKeyColumns';
        $serialized[] = 'targetToSourceKeyColumns';
        if ($this->orphanRemoval) {
            $serialized[] = 'orphanRemoval';
        }
        return $serialized;
    }
}
