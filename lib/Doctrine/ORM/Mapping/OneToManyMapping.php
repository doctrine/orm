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
 * Represents a one-to-many mapping.
 * 
 * NOTE: One-to-many mappings can currently not be uni-directional (one -> many).
 * They must either be bidirectional (one <-> many) or unidirectional (many -> one).
 * In other words, the many-side MUST be the owning side and the one-side MUST be
 * the inverse side.
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
 * @author Roman Borschel <roman@code-factory.org>
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @since 2.0
 * @todo Potentially remove if assoc mapping objects get replaced by simple arrays.
 */
class OneToManyMapping extends AssociationMapping
{
    /**
     * READ-ONLY: Whether to delete orphaned elements (removed from the collection)
     *
     * @var boolean
     */
    public $orphanRemoval = false;

    /** FUTURE: The key column mapping, if any. The key column holds the keys of the Collection. */
    //public $keyColumn;

    /**
     * READ-ONLY: Order this collection by the given SQL snippet.
     */
    public $orderBy;

    /**
     * Validates and completes the mapping.
     *
     * @param array $mapping The mapping to validate and complete.
     * @return array The validated and completed mapping.
     * @override
     */
    protected function _validateAndCompleteMapping(array $mapping)
    {
        parent::_validateAndCompleteMapping($mapping);

        // OneToMany-side MUST be inverse (must have mappedBy)
        if ( ! isset($mapping['mappedBy'])) {
            throw MappingException::oneToManyRequiresMappedBy($mapping['fieldName']);
        }
        
        //TODO: if orphanRemoval, cascade=remove is implicit!
        $this->orphanRemoval = isset($mapping['orphanRemoval']) ?
                (bool) $mapping['orphanRemoval'] : false;

        if (isset($mapping['orderBy'])) {
            if (!is_array($mapping['orderBy'])) {
                throw new \InvalidArgumentException("'orderBy' is expected to be an array, not ".gettype($mapping['orderBy']));
            }
            $this->orderBy = $mapping['orderBy'];
        }
    }
    
    /**
     * Whether orphaned elements (removed from the collection) should be deleted.
     *
     * @return boolean TRUE if orphaned elements should be deleted, FALSE otherwise.
     */
    public function shouldDeleteOrphans()
    {
        return $this->deleteOrphans;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isOneToMany()
    {
        return true;
    }
    
    /**
     * Loads a one-to-many collection.
     * 
     * @param $sourceEntity The entity that owns the collection.
     * @param $targetCollection The collection to load/fill.
     * @param $em The EntityManager to use.
     * @param $joinColumnValues 
     * @return void
     * @todo Remove
     */
    public function load($sourceEntity, $targetCollection, $em, array $joinColumnValues = array())
    {
        $em->getUnitOfWork()->getEntityPersister($this->targetEntityName)->loadOneToManyCollection($this, $sourceEntity, $targetCollection);
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
        if ($this->orderBy) {
            $serialized[] = 'orderBy';
        }
        if ($this->orphanRemoval) {
            $serialized[] = 'orphanRemoval';
        }
        return $serialized;
    }
}
