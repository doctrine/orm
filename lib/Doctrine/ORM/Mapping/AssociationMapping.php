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
 * Base class for association mappings.
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
abstract class AssociationMapping
{
    /**
     * Specifies that an association is to be fetched when it is first accessed.
     * 
     * @var integer
     */
    const FETCH_LAZY = 2;
    /**
     * Specifies that an association is to be fetched when the owner of the
     * association is fetched. 
     *
     * @var integer
     */
    const FETCH_EAGER = 3;
    
    /**
     * Cascade types enumeration.
     *
     * @var array
     */
    protected static $_cascadeTypes = array(
        'all',
        'none',
        'save',
        'delete',
        'refresh',
        'merge'
    );
    
    public $cascades = array();
    public $isCascadeRemove;
    public $isCascadePersist;
    public $isCascadeRefresh;
    public $isCascadeMerge;
    public $isCascadeDetach;
    
    /**
     * The fetch mode used for the association.
     *
     * @var integer
     */
    public $fetchMode = self::FETCH_LAZY;
    
    /**
     * Flag that indicates whether the class that defines this mapping is
     * the owning side of the association.
     *
     * @var boolean
     */
    public $isOwningSide = true;
    
    /**
     * Whether the association is optional (0..X) or not (1..X).
     * By default all associations are optional.
     *
     * @var boolean
     */
    public $isOptional = true;
    
    /**
     * The name of the source Entity (the Entity that defines this mapping).
     *
     * @var string
     */
    public $sourceEntityName;
    
    /**
     * The name of the target Entity (the Enitity that is the target of the
     * association).
     *
     * @var string
     */
    public $targetEntityName;
    
    /**
     * Identifies the field on the source class (the class this AssociationMapping
     * belongs to) that represents the association and stores the reference to the
     * other entity/entities.
     *
     * @var string
     */
    public $sourceFieldName;
    
    /**
     * Identifies the field on the owning side that controls the mapping for the
     * association. This is only set on the inverse side of an association.
     *
     * @var string
     */
    public $mappedByFieldName;
    
    /**
     * The join table definition, if any.
     *
     * @var array
     */
    public $joinTable = array();

    //protected $_joinTableInsertSql;
    
    /**
     * Initializes a new instance of a class derived from AssociationMapping.
     *
     * @param array $mapping  The mapping definition.
     */
    public function __construct(array $mapping)
    {
        $this->_validateAndCompleteMapping($mapping);
    }
    
    /**
     * Validates & completes the mapping. Mapping defaults are applied here.
     *
     * @param array $mapping
     */
    protected function _validateAndCompleteMapping(array $mapping)
    {        
        // Mandatory attributes for both sides
        if ( ! isset($mapping['fieldName'])) {
            throw MappingException::missingFieldName();
        }
        $this->sourceFieldName = $mapping['fieldName'];
        
        if ( ! isset($mapping['sourceEntity'])) {
            throw MappingException::missingSourceEntity($mapping['fieldName']);
        }
        $this->sourceEntityName = $mapping['sourceEntity'];
        
        if ( ! isset($mapping['targetEntity'])) {
            throw MappingException::missingTargetEntity($mapping['fieldName']);
        }
        $this->targetEntityName = $mapping['targetEntity'];
        
        // Mandatory and optional attributes for either side
        if ( ! isset($mapping['mappedBy'])) {            
            // Optional
            if (isset($mapping['joinTable'])) {
                $this->joinTable = $mapping['joinTable'];   
            }
        } else {
            $this->isOwningSide = false;
            $this->mappedByFieldName = $mapping['mappedBy'];
        }
        
        // Optional attributes for both sides
        $this->isOptional = isset($mapping['optional']) ?
                (bool)$mapping['optional'] : true;
        $this->cascades = isset($mapping['cascade']) ?
                (array)$mapping['cascade'] : array();
        $this->isCascadeRemove = in_array('remove', $this->cascades);
        $this->isCascadePersist = in_array('persist', $this->cascades);
        $this->isCascadeRefresh = in_array('refresh', $this->cascades);
        $this->isCascadeMerge = in_array('merge', $this->cascades);
        $this->isCascadeDetach = in_array('detach', $this->cascades);
    }
    
    /**
     * Whether the association cascades delete() operations from the source entity
     * to the target entity/entities.
     *
     * @return boolean
     */
    public function isCascadeRemove()
    {
        return $this->isCascadeRemove;
    }
    
    /**
     * Whether the association cascades save() operations from the source entity
     * to the target entity/entities.
     *
     * @return boolean
     */
    public function isCascadePersist()
    {
        return $this->isCascadePersist;
    }
    
    /**
     * Whether the association cascades refresh() operations from the source entity
     * to the target entity/entities.
     *
     * @return boolean
     */
    public function isCascadeRefresh()
    {
        return $this->isCascadeRefresh;
    }

    /**
     * Whether the association cascades merge() operations from the source entity
     * to the target entity/entities.
     *
     * @return boolean
     */
    public function isCascadeMerge()
    {
        return $this->isCascadeMerge;
    }
    
    /**
     * Whether the association cascades detach() operations from the source entity
     * to the target entity/entities.
     *
     * @return boolean
     */
    public function isCascadeDetach()
    {
        return $this->isCascadeDetach;
    }
    
    /**
     * Whether the target entity/entities of the association are eagerly fetched.
     *
     * @return boolean
     */
    public function isEagerlyFetched()
    {
        return $this->fetchMode == self::FETCH_EAGER;
    }
    
    /**
     * Whether the target entity/entities of the association are lazily fetched.
     *
     * @return boolean
     */
    public function isLazilyFetched()
    {
        return $this->fetchMode == self::FETCH_LAZY;
    }
    
    /**
     * Whether the source entity of this association represents the owning side.
     *
     * @return boolean
     */
    public function isOwningSide()
    {
        return $this->isOwningSide;
    }
    
    /**
     * Whether the source entity of this association represents the inverse side.
     *
     * @return boolean
     */
    public function isInverseSide()
    {
        return ! $this->isOwningSide;
    }
    
    /**
     * Whether the association is optional (0..X), or not (1..X).
     *
     * @return boolean TRUE if the association is optional, FALSE otherwise.
     * @todo Only applicable to OneToOne. Move there.
     */
    public function isOptional()
    {
        return $this->isOptional;
    }
    
    /**
     * Gets the name of the source entity class.
     *
     * @return string
     */
    public function getSourceEntityName()
    {
        return $this->sourceEntityName;
    }
    
    /**
     * Gets the name of the target entity class.
     *
     * @return string
     */
    public function getTargetEntityName()
    {
        return $this->targetEntityName;
    }
    
    /**
     * Gets the join table definition, if any.
     *
     * @return array
     */
    public function getJoinTable()
    {
        return $this->joinTable;
    }
    
    /**
     * Get the name of the field the association is mapped into.
     *
     * @return string
     */
    public function getSourceFieldName()
    {
        return $this->sourceFieldName;
    }
    
    /**
     * Gets the field name of the owning side in a bi-directional association.
     * This is only set on the inverse side. When invoked on the owning side,
     * NULL is returned.
     *
     * @return string
     */
    public function getMappedByFieldName()
    {
        return $this->mappedByFieldName;
    }

    /**
     * Whether the association is a one-to-one association.
     *
     * @return boolean
     */
    public function isOneToOne()
    {
        return false;
    }

    /**
     * Whether the association is a one-to-many association.
     *
     * @return boolean
     */
    public function isOneToMany()
    {
        return false;
    }

    /**
     * Whether the association is a many-to-many association.
     *
     * @return boolean
     */
    public function isManyToMany()
    {
        return false;
    }

    /**
     * Whether the association uses a join table for the mapping.
     *
     * @return boolean
     */
    public function usesJoinTable()
    {
        return (bool) $this->joinTable;
    }

    /**
     * Loads data in $targetEntity domain object using this association.
     * The data comes from the association navigated from $sourceEntity
     * using $em.
     *
     * @param object $sourceEntity
     * @param object $targetEntity
     * @param EntityManager $em
     * @param array $joinColumnValues
     */
    abstract public function load($sourceEntity, $targetEntity, $em, array $joinColumnValues);
}
