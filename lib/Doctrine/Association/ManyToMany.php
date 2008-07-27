<?php

#namespace Doctrine::ORM::Mappings;

/**
 * A many-to-many mapping describes the mapping between two collections of
 * entities.
 *
 * @since 2.0
 * @todo Rename to ManyToManyMapping
 */
class Doctrine_Association_ManyToMany extends Doctrine_Association
{
    /**
     * Whether the mapping uses an association class.
     *
     * @var boolean
     */
    private $_usesAssociationClass;
    
    /**
     * The name of the association class (if an association class is used).
     *
     * @var string
     */
    private $_associationClassName;
    
    /**
     * The name of the intermediate table.
     *
     * @var string
     */
    private $_relationTableName;
    
    /** The field in the source table that corresponds to the key in the relation table */
    protected $_sourceKeyColumns;

    /**  The field in the target table that corresponds to the key in the relation table */
    protected $_targetKeyColumns;

    /** The field in the intermediate table that corresponds to the key in the source table */
    protected $_sourceRelationKeyColumns;

    /** The field in the intermediate table that corresponds to the key in the target table */
    protected $_targetRelationKeyColumns;
    
    
    /**
     * Whether the mapping uses an association class for the intermediary
     * table.
     *
     * @return boolean
     */
    public function usesAssociationClass()
    {
        
    }
    
    /**
     * Gets the name of the intermediate table.
     *
     * @return string
     */
    public function getRelationTableName()
    {
        return $this->_relationTableName;
    }
    
    /**
     * Gets the name of the association class.
     *
     * @return string
     */
    public function getAssociationClassName()
    {
        return $this->_associationClassName;
    }
}

?>