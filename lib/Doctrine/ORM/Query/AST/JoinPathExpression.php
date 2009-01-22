<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * Description of JoinCollectionValuedPathExpression
 *
 * @author robo
 */
class JoinPathExpression extends Node
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

