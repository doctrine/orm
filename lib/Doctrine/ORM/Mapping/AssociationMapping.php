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
 * @todo Potentially remove if assoc mapping objects get replaced by simple arrays.
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
     * READ-ONLY: Whether the association cascades delete() operations from the source entity
     * to the target entity/entities.
     *
     * @var boolean
     */
    public $isCascadeRemove;

    /**
     * READ-ONLY: Whether the association cascades persist() operations from the source entity
     * to the target entity/entities.
     *
     * @var boolean
     */
    public $isCascadePersist;

    /**
     * READ-ONLY: Whether the association cascades refresh() operations from the source entity
     * to the target entity/entities.
     *
     * @var boolean
     */
    public $isCascadeRefresh;

    /**
     * READ-ONLY: Whether the association cascades merge() operations from the source entity
     * to the target entity/entities.
     *
     * @var boolean
     */
    public $isCascadeMerge;

    /**
     * READ-ONLY: Whether the association cascades detach() operations from the source entity
     * to the target entity/entities.
     *
     * @var boolean
     */
    public $isCascadeDetach;

    /**
     * READ-ONLY: The fetch mode used for the association.
     *
     * @var integer
     */
    public $fetchMode;

    /**
     * READ-ONLY: Flag that indicates whether the class that defines this mapping is
     * the owning side of the association.
     *
     * @var boolean
     */
    public $isOwningSide = true;

    /**
     * READ-ONLY: The name of the source Entity (the Entity that defines this mapping).
     *
     * @var string
     */
    public $sourceEntityName;

    /**
     * READ-ONLY: The name of the target Entity (the Enitity that is the target of the
     * association).
     *
     * @var string
     */
    public $targetEntityName;

    /**
     * READ-ONLY: Identifies the field on the source class (the class this AssociationMapping
     * belongs to) that represents the association and stores the reference to the
     * other entity/entities.
     *
     * @var string
     */
    public $sourceFieldName;

    /**
     * READ-ONLY: Identifies the field on the owning side of a bidirectional association that
     * controls the mapping for the association. This is only set on the inverse side
     * of an association.
     *
     * @var string
     */
    public $mappedBy;

    /**
     * READ-ONLY: Identifies the field on the inverse side of a bidirectional association.
     * This is only set on the owning side of an association.
     *
     * @var string
     */
    public $inversedBy;

    /**
     * READ-ONLY: The join table definition, if any.
     *
     * @var array
     */
    public $joinTable;

    /**
     * READ-ONLY: The name of the entity class from which the association was
     * inherited in an inheritance hierarchy.
     *
     * @var string
     */
    public $inherited;

    /**
     * READ-ONLY: The name of the entity or mapped superclass that declares
     * the association field in an inheritance hierarchy.
     *
     * @var string
     */
    public $declared;

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
     * @throws MappingException If something is wrong with the mapping.
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
            if (isset($mapping['joinTable']) && $mapping['joinTable']) {
                if ($mapping['joinTable']['name'][0] == '`') {
                    $mapping['joinTable']['name'] = trim($mapping['joinTable']['name'], '`');
                    $mapping['joinTable']['quoted'] = true;
                }
                $this->joinTable = $mapping['joinTable'];   
            }
            if (isset($mapping['inversedBy'])) {
                $this->inversedBy = $mapping['inversedBy'];
            }
        } else {
            $this->isOwningSide = false;
            $this->mappedBy = $mapping['mappedBy'];
        }
        
        // Optional attributes for both sides
        $this->fetchMode = isset($mapping['fetch']) ? $mapping['fetch'] : self::FETCH_LAZY;
        $cascades = isset($mapping['cascade']) ? $mapping['cascade'] : array();
        
        if (in_array('all', $cascades)) {
            $cascades = array(
               'remove',
               'persist',
               'refresh',
               'merge',
               'detach'
            );
        }
        
        $this->isCascadeRemove = in_array('remove',  $cascades);
        $this->isCascadePersist = in_array('persist', $cascades);
        $this->isCascadeRefresh = in_array('refresh', $cascades);
        $this->isCascadeMerge = in_array('merge',   $cascades);
        $this->isCascadeDetach = in_array('detach',  $cascades);
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
     * Checks whether the association has any cascades configured.
     * 
     * @return boolean
     */
    public function hasCascades()
    {
        return $this->isCascadePersist ||
               $this->isCascadeRemove ||
               $this->isCascadeRefresh ||
               $this->isCascadeMerge ||
               $this->isCascadeDetach;
    }

    /**
     * Loads data in $target domain object using this association.
     * The data comes from the association navigated from $sourceEntity
     * using $em.
     *
     * @param object $sourceEntity
     * @param object $target            an entity or a collection
     * @param EntityManager $em
     * @param array $joinColumnValues   foreign keys (significative for this
     *                                  association) of $sourceEntity, if needed
     */
    abstract public function load($sourceEntity, $target, $em, array $joinColumnValues = array());
    
    /**
     * Gets the (possibly quoted) name of the join table.
     *
     * @param AbstractPlatform $platform
     * @return string
     */
    public function getQuotedJoinTableName($platform)
    {
        return isset($this->joinTable['quoted'])
            ? $platform->quoteIdentifier($this->joinTable['name'])
            : $this->joinTable['name'];
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
        $serialized = array(
            'sourceEntityName',
            'targetEntityName',
            'sourceFieldName',
            'fetchMode'
        );

        if ($this->isCascadeDetach) {
            $serialized[] = 'isCascadeDetach';
        }
        if ($this->isCascadeMerge) {
            $serialized[] = 'isCascadeMerge';
        }
        if ($this->isCascadePersist) {
            $serialized[] = 'isCascadePersist';
        }
        if ($this->isCascadeRefresh) {
            $serialized[] = 'isCascadeRefresh';
        }
        if ($this->isCascadeRemove) {
            $serialized[] = 'isCascadeRemove';
        }
        if ( ! $this->isOwningSide) {
            $serialized[] = 'isOwningSide';
        }
        if ($this->mappedBy) {
            $serialized[] = 'mappedBy';
        }
        if ($this->inversedBy) {
            $serialized[] = 'inversedBy';
        }
        if ($this->joinTable) {
            $serialized[] = 'joinTable';
        }
        if ($this->inherited) {
            $serialized[] = 'inherited';
        }
        if ($this->declared) {
            $serialized[] = 'declared';
        }
        
        return $serialized;
    }
}
