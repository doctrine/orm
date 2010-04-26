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
 * A many-to-many mapping describes the mapping between two collections of
 * entities.
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
class ManyToManyMapping extends AssociationMapping
{
    /**
     * READ-ONLY: Maps the columns in the relational table to the columns in the source table.
     */
    public $relationToSourceKeyColumns = array();

    /**
     * READ-ONLY: Maps the columns in the relation table to the columns in the target table.
     */
    public $relationToTargetKeyColumns = array();

    /**
     * READ-ONLY: List of aggregated column names on the join table.
     */
    public $joinTableColumns = array();
    
    /** FUTURE: The key column mapping, if any. The key column holds the keys of the Collection. */
    //public $keyColumn;

    /**
     * READ-ONLY: Order this collection by the given DQL snippet.
     * 
     * Only simple unqualified field names and ASC|DESC are allowed
     *
     * @var array
     */
    public $orderBy;

    /**
     * {@inheritdoc}
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
                            'referencedColumnName' => 'id',
                            'onDelete' => 'CASCADE'
                        )
                    ),
                    'inverseJoinColumns' => array(
                        array(
                            'name' => $targetShortName . '_id',
                            'referencedColumnName' => 'id',
                            'onDelete' => 'CASCADE'
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
            
            foreach ($mapping['joinTable']['joinColumns'] as $joinColumn) {
                $this->relationToSourceKeyColumns[$joinColumn['name']] = $joinColumn['referencedColumnName'];
                $this->joinTableColumns[] = $joinColumn['name'];
            }
            
            foreach ($mapping['joinTable']['inverseJoinColumns'] as $inverseJoinColumn) {
                $this->relationToTargetKeyColumns[$inverseJoinColumn['name']] = $inverseJoinColumn['referencedColumnName'];
                $this->joinTableColumns[] = $inverseJoinColumn['name'];
            }
        }

        if (isset($mapping['orderBy'])) {
            if ( ! is_array($mapping['orderBy'])) {
                throw new \InvalidArgumentException("'orderBy' is expected to be an array, not ".gettype($mapping['orderBy']));
            }
            $this->orderBy = $mapping['orderBy'];
        }
    }

    /**
     * Loads entities in $targetCollection using $em.
     * The data of $sourceEntity are used to restrict the collection
     * via the join table.
     * 
     * @param object The owner of the collection.
     * @param object The collection to populate.
     * @param array
     * @todo Remove
     */
    public function load($sourceEntity, $targetCollection, $em, array $joinColumnValues = array())
    {
        $em->getUnitOfWork()->getEntityPersister($this->targetEntityName)->loadManyToManyCollection($this, $sourceEntity, $targetCollection);
    }

    /** {@inheritdoc} */
    public function isManyToMany()
    {
        return true;
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
        $serialized[] = 'joinTableColumns';
        $serialized[] = 'relationToSourceKeyColumns';
        $serialized[] = 'relationToTargetKeyColumns';
        if ($this->orderBy) {
            $serialized[] = 'orderBy';
        }
        return $serialized;
    }
}
