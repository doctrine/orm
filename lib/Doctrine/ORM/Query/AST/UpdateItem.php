<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * UpdateItem ::= [IdentificationVariable "."] {StateField | SingleValuedAssociationField} "=" NewValue
 *
 * @author robo
 */
class UpdateItem extends Node
{
    private $_identificationVariable;
    private $_field;
    private $_newValue;

    public function __construct($field, $newValue)
    {
        $this->_field = $field;
        $this->_newValue = $newValue;
    }

    public function setIdentificationVariable($identVar)
    {
        $this->_identificationVariable = $identVar;
    }

    public function getIdentificationVariable()
    {
        return $this->_identificationVariable;
    }

    public function getField()
    {
        return $this->_field;
    }

    public function getNewValue()
    {
        return $this->_newValue;
    }
}

