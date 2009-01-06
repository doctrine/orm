<?php

#namespace Doctrine::ORM::Mapping;

/**
 * A many-to-many mapping describes the mapping between two collections of
 * entities.
 *
 * @since 2.0
 * @todo Rename to ManyToManyMapping
 */
class Doctrine_ORM_Mapping_ManyToManyMapping extends Doctrine_ORM_Mapping_AssociationMapping
{    
    /**
     * The name of the association class (if an association class is used).
     *
     * @var string
     */
    private $_associationClass;
    
    /** The field in the source table that corresponds to the key in the relation table */
    protected $_sourceKeyColumns;

    /**  The field in the target table that corresponds to the key in the relation table */
    protected $_targetKeyColumns;

    /** The field in the intermediate table that corresponds to the key in the source table */
    protected $_sourceRelationKeyColumns;

    /** The field in the intermediate table that corresponds to the key in the target table */
    protected $_targetRelationKeyColumns;
    
    /**
     * Constructor.
     * Creates a new ManyToManyMapping.
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
     * @param array $mapping
     * @override
     */
    protected function _validateAndCompleteMapping(array $mapping)
    {
        parent::_validateAndCompleteMapping($mapping);
        
        if ($this->isOwningSide()) {
            // many-many owning MUST have a join table
            if ( ! isset($mapping['joinTable'])) {
                throw Doctrine_MappingException::joinTableRequired($mapping['fieldName']);
            }
        
            // optional attributes for many-many owning side
            $this->_associationClass = isset($mapping['associationClass']) ?
                    $mapping['associationClass'] : null;
        }
    }
    
    /**
     * Whether the mapping uses an association class for the intermediary
     * table.
     *
     * @return boolean
     */
    public function usesAssociationClass()
    {
        return $this->_associationClass !== null;
    }
    
    /**
     * Gets the name of the association class.
     *
     * @return string
     */
    public function getAssociationClassName()
    {
        return $this->_associationClass;
    }
}

