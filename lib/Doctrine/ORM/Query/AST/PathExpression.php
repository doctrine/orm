<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PathExpression
 *
 * @author robo
 */
class Doctrine_ORM_Query_AST_PathExpression
{
    private $_parts;
    // Information that is attached during semantical analysis.
    private $_isSimpleStateFieldPathExpression = false;
    private $_isSimpleStateFieldAssociationPathExpression = false;
    private $_embeddedClassFields = array();
    private $_singleValuedAssociationFields = array();

    public function __construct(array $parts)
    {
        $this->_parts = $parts;
    }

    public function getParts() {
        return $this->_parts;
    }

    /**
     * Gets whether the path expression represents a state field that is reached
     * either directly (u.name) or  by navigating over optionally many embedded class instances
     * (u.address.zip).
     *
     * @return boolean
     */
    public function isSimpleStateFieldPathExpression()
    {
        return $this->_isSimpleStateFieldPathExpression;
    }

    /**
     * Gets whether the path expression represents a state field that is reached
     * by navigating over at least one single-valued association and optionally
     * many embedded class instances. (u.Group.address.zip, u.Group.address, ...)
     *
     * @return boolean
     */
    public function isSimpleStateFieldAssociationPathExpression()
    {
        return $this->_isSimpleStateFieldAssociationPathExpression;
    }

    public function isPartEmbeddedClassField($part)
    {
        return isset($this->_embeddedClassFields[$part]);
    }

    public function isPartSingleValuedAssociationField($part)
    {
        return isset($this->_singleValuedAssociationFields[$part]);
    }

    /* Setters to attach semantical information during semantical analysis. */

    public function setIsSimpleStateFieldPathExpression($bool)
    {
        $this->_isSimpleStateFieldPathExpression = $bool;
    }

    public function setIsSimpleStateFieldAssociationPathExpression($bool)
    {
        $this->_isSimpleStateFieldAssociationPathExpression = $bool;
    }

    public function setIsEmbeddedClassPart($part)
    {
        $this->_embeddedClassFields[$part] = true;
    }

    public function setIsSingleValuedAssociationPart($part)
    {
        $this->_singleValuedAssociationFields[$part] = true;
    }
}

