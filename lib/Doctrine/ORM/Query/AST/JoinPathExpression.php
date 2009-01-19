<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JoinCollectionValuedPathExpression
 *
 * @author robo
 */
class Doctrine_ORM_Query_AST_JoinPathExpression extends Doctrine_ORM_Query_AST
{
    private $_identificationVariable;
    private $_assocField;

    public function __construct($identificationVariable, $assocField)
    {
        $this->_identificationVariable = $identificationVariable;
        $this->_assocField = $assocField;
    }

    public function getIdentificationVariable()
    {
        return $this->_identificationVariable;
    }
    
    public function getAssociationField()
    {
        return $this->_assocField;
    }
}

