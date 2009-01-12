<?php

/**
 * This class is just an intermediate implementation for refactoring purposes
 * and will be replaced by the ParserResult class of the new DQL parser.
 *
 */
class Doctrine_ORM_Query_ParserResultDummy
{
    private $_isMixedQuery;
    private $_dbStatement;
    private $_isIdentityQuery;
    private $_hydrationMode;
    private $_tableToClassAliasMap;
    private $_queryComponents;
    
    
    public function isMixedQuery()
    {
        return $this->_isMixedQuery;
    }
    
    public function isIdentityQuery()
    {
        return $this->_isIdentityQuery;
    }
    
    public function setMixedQuery($bool)
    {
        $this->_isMixedQuery = (bool) $bool;
    }
    
    public function getDatabaseStatement()
    {
        return $this->_dbStatement;
    }
    
    public function setDatabaseStatement($stmt)
    {
        $this->_dbStatement = $stmt;
    }
    
    public function getHydrationMode()
    {
        return $this->_hydrationMode;
    }
    
    public function setHydrationMode($hydrationMode)
    {
        $this->_hydrationMode = $hydrationMode;
    }
    
    public function getTableToClassAliasMap()
    {
        return $this->_tableToClassAliasMap;
    }
    
    public function setTableToClassAliasMap(array $map)
    {
        $this->_tableToClassAliasMap = $map;
    }
    
    public function setQueryComponents(array $queryComponents)
    {
        $this->_queryComponents = $queryComponents;
    }
    
    public function getQueryComponents()
    {
        return $this->_queryComponents;
    }
}


?>