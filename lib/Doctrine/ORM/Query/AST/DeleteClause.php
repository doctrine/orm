<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\ORM\Query\AST;

/**
 * DeleteClause ::= "DELETE" ["FROM"] AbstractSchemaName [["AS"] AliasIdentificationVariable]
 */
class DeleteClause extends Node
{
    private $_abstractSchemaName;
    private $_aliasIdentificationVariable;

    public function __construct($abstractSchemaName)
    {
        $this->_abstractSchemaName = $abstractSchemaName;
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
}

