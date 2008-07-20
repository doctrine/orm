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

#namespace Doctrine::ORM::Mapping;

/**
 * Base class for association mappings.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 * @todo Rename to AssociationMapping.
 */
class Doctrine_Association implements Serializable
{
    const FETCH_MANUAL = 1;
    const FETCH_LAZY = 2;
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
        'refresh'
    );
    
    protected $_cascades = array();
    protected $_isCascadeDelete;
    protected $_isCascadeSave;
    protected $_isCascadeRefresh;
    
    /**
     * The fetch mode used for the association.
     *
     * @var integer
     */
    protected $_fetchMode = self::FETCH_MANUAL;
    
    /**
     * Flag that indicates whether the class that defines this mapping is
     * the owning side of the association.
     *
     * @var boolean
     */
    protected $_isOwningSide = true;
    
    /**
     * The name of the source Entity (the Entity that defines this mapping).
     *
     * @var string
     */
    protected $_sourceEntityName;
    
    /**
     * The name of the target Entity (the Enitity that is the target of the
     * association).
     *
     * @var unknown_type
     */
    protected $_targetEntityName;
    
    /**
     * Identifies the field on the source class (the class this AssociationMapping
     * belongs to) that represents the association.
     *
     * @var string
     */
    protected $_sourceFieldName;
    
    /**
     * Identifies the field on the owning side that has the mapping for the
     * association.
     *
     * @var string
     */
    protected $_mappedByFieldName;
    
    /**
     * Constructor.
     * Creates a new AssociationMapping.
     *
     * @param array $mapping  The mapping definition.
     */
    public function __construct(array $mapping)
    {
        $this->_validateMapping($mapping);
        if ($this->_isOwningSide) {
            $this->_sourceEntityName = $mapping['sourceEntity'];
            $this->_targetEntityName = $mapping['targetEntity'];
            $this->_sourceFieldName = $mapping['fieldName'];
        } else {
            $this->_mappedByFieldName = $mapping['mappedBy'];
        }        
    }
    
    /**
     * Validates & completes the mapping. Mapping defaults are applied here.
     *
     * @param array $mapping
     * @return array  The validated & completed mapping.
     */
    protected function _validateMapping(array $mapping)
    {
        if (isset($mapping['mappedBy'])) {
            $this->_isOwningSide = false;
        }
        
        if ($this->_isOwningSide) {
            if ( ! isset($mapping['targetEntity'])) {
                throw Doctrine_MappingException::missingTargetEntity();
            } else if ( ! isset($mapping['fieldName'])) {
                throw Doctrine_MappingException::missingFieldName();
            }
        }

        return $mapping;
    }
    
    public function isCascadeDelete()
    {
        if (is_null($this->_isCascadeDelete)) {
            $this->_isCascadeDelete = in_array('delete', $this->_cascades);
        }
        return $this->_isCascadeDelete;
    }
    
    public function isCascadeSave()
    {
        if (is_null($this->_isCascadeSave)) {
            $this->_isCascadeSave = in_array('save', $this->_cascades);
        }
        return $this->_isCascadeSave;
    }
    
    public function isCascadeRefresh()
    {
        if (is_null($this->_isCascadeRefresh)) {
            $this->_isCascadeRefresh = in_array('refresh', $this->_cascades);
        }
        return $this->_isCascadeRefresh;
    }
    
    public function isEagerlyFetched()
    {
        return $this->_fetchMode == self::FETCH_EAGER;
    }
    
    public function isLazilyFetched()
    {
        return $this->_fetchMode == self::FETCH_LAZY;
    }
    
    public function isManuallyFetched()
    {
        return $this->_fetchMode == self::FETCH_MANUAL;
    }
    
    public function isOwningSide()
    {
        return $this->_isOwningSide;
    }
    
    public function isInverseSide()
    {
        return ! $this->_isOwningSide;
    }
    
    public function getSourceEntityName()
    {
        return $this->_sourceEntityName;
    }
    
    public function getTargetEntityName()
    {
        return $this->_targetEntityName;
    }
    
    /**
     * Get the name of the field the association is mapped into.
     *
     */
    public function getSourceFieldName()
    {
        return $this->_sourceFieldName;
    }
    
    public function getMappedByFieldName()
    {
        return $this->_mappedByFieldName;
    }
    
    /* Serializable implementation */
    
    public function serialize()
    {
        return "";
    }
    
    public function unserialize($serialized)
    {
        return true;
    }
}

?>