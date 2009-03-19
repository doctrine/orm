<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * UpdateClause ::= "UPDATE" AbstractSchemaName [["AS"] AliasIdentificationVariable] "SET" UpdateItem {"," UpdateItem}*
 */
class UpdateClause extends Node
{
    private $_abstractSchemaName;
    private $_aliasIdentificationVariable;
    private $_updateItems = array();

    public function __construct($abstractSchemaName, array $updateItems)
    {
        $this->_abstractSchemaName = $abstractSchemaName;
        $this->_updateItems = $updateItems;
    }

    public function getAbstractSchemaName()
    {
        return $this->_abstractSchemaName;
    }

    public function getAliasIdentificationVariable()
    {
        return $this->_aliasIdentificationVariable;
    }

    public function setAliasIdentificationVariable($alias)
    {
        $this->_aliasIdentificationVariable = $alias;
    }

    public function getUpdateItems()
    {
        return $this->_updateItems;
    }
}

