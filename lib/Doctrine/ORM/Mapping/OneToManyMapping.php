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
 * 1) To allow fast, internal READ access.
 * 2) To drastically reduce the size of a serialized instance (private/protected members
 *    get the whole class name, namespace inclusive, prepended to every property in
 *    the serialized representation).
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class OneToManyMapping extends AssociationMapping
{
    /** Whether to delete orphaned elements (removed from the collection) */
    public $deleteOrphans = false;
    /** FUTURE: The key column mapping, if any. The key column holds the keys of the Collection. */
    //public $keyColumn;
    
    /**
     * Initializes a new OneToManyMapping.
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
     * @param array $mapping The mapping to validate and complete.
     * @return array The validated and completed mapping.
     * @override
     */
    protected function _validateAndCompleteMapping(array $mapping)
    {
        parent::_validateAndCompleteMapping($mapping);
        
        // one-side MUST be inverse (must have mappedBy)
        if ( ! isset($mapping['mappedBy'])) {
            throw MappingException::oneToManyRequiresMappedBy($mapping['fieldName']);
        }
        
        $this->deleteOrphans = isset($mapping['deleteOrphans']) ?
                (bool)$mapping['deleteOrphans'] : false;
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
     *
     * @override
     */
    public function isOneToMany()
    {
        return true;
    }
    
    /**
     *
     * @param <type> $entity 
     * @override
     */
    public function lazyLoadFor($entity, $entityManager)
    {

    }
}