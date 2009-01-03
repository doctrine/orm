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

#namespace Doctrine\ORM\Mapping;

/**
 * Represents a one-to-many mapping.
 * 
 * NOTE: One-to-many mappings can currently not be uni-directional (one -> many).
 * They must either be bidirectional (one <-> many) or unidirectional (many -> one).
 * In other words, the many-side MUST be the owning side and the one-side MUST be
 * the inverse side.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 * @todo Rename to OneToManyMapping
 */
class Doctrine_ORM_Mapping_OneToManyMapping extends Doctrine_ORM_Mapping_AssociationMapping
{
    /** The target foreign key columns that reference the sourceKeyColumns. */
    /* NOTE: Currently not used because uni-directional one-many not supported atm.  */
    //protected $_targetForeignKeyColumns;

    /** The (typically primary) source key columns that are referenced by the targetForeignKeyColumns. */
    /* NOTE: Currently not used because uni-directional one-many not supported atm.  */
    //protected $_sourceKeyColumns;

    /** This maps the target foreign key columns to the corresponding (primary) source key columns. */
    /* NOTE: Currently not used because uni-directional one-many not supported atm.  */
    //protected $_targetForeignKeysToSourceKeys;
    
    /** This maps the (primary) source key columns to the corresponding target foreign key columns. */
    /* NOTE: Currently not used because uni-directional one-many not supported atm.  */
    //protected $_sourceKeysToTargetForeignKeys;
    
    /** Whether to delete orphaned elements (removed from the collection) */
    protected $_deleteOrphans = false;
    
    /**
     * Constructor.
     * Creates a new OneToManyMapping.
     *
     * @param array $mapping  The mapping info.
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
            throw Doctrine_MappingException::oneToManyRequiresMappedBy($mapping['fieldName']);
        }
        
        $this->_deleteOrphans = isset($mapping['deleteOrphans']) ?
                (bool)$mapping['deleteOrphans'] : false;
    }
    
    /**
     * Whether orphaned elements (removed from the collection) should be deleted.
     *
     * @return boolean TRUE if orphaned elements should be deleted, FALSE otherwise.
     */
    public function shouldDeleteOrphans()
    {
        return $this->_deleteOrphans;
    }
    
    /**
     * Whether the association is one-to-many.
     *
     * @return boolean TRUE if the association is one-to-many, FALSE otherwise.
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
    public function lazyLoadFor($entity)
    {

    }
    
}

?>