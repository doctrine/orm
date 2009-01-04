<?php 

#namespace Doctrine\ORM;

/**
 * @todo Migrate the old RawSql to NativeQuery.
 *       Use JPA/Hibernate NativeQuerys as a role-model.
 */
class Doctrine_NativeQuery
{
    private static $_placeHolderPattern = '#\{([a-z][a-z0-9_]*)\.(\*|[a-z][a-z0-9_]*)\}#i';
    private $_sql;
    private $_conn;
    
    private $_params = array();
    
    private $_entities = array();
    private $_placeholders = array();
    private $_usedEntityAliases = array();
    private $_usedFields = array();
    
    public function __construct($sql, Doctrine_Connection $conn)
    {
        $numMatches = preg_match_all(self::$_placeHolderPattern, $sql, $matches);
        
        $this->_placeHolders = $matches[0];
        $this->_usedEntityAliases = $matches[1];
        $this->_usedFields = $matches[2];
        
        $this->_sql = $sql;
        $this->_conn = $conn;
    }
    
    private function _parse()
    {
        // replace placeholders in $sql with generated names
        for ($i = 0; $i < count($this->_placeholders); $i++) {
            $entityClassName = $this->_entities[$this->_usedEntityAliases[$i]];
            $entityClass = $this->_conn->getClassMetadata($entityClassName);
            $columnName = $entityClass->getColumnName($this->_usedFields[$i]);
            $tableName = $entityClass->getTableName();
            $replacement = $tableName . '.' . $columnName . ' AS '
                    . $this->_generateColumnAlias($columnName, $tableName);
            $sql = str_replace($this->_placeholders[$i], $replacement, $sql);
        }
    }
    
    private function _generateColumnAlias($columnName, $tableName)
    {
        return $tableName . '__' . $columnName;
    }
    
    /*public function addScalar()
    {
        
    }*/
    
    public function addEntity($alias, $className)
    {
        $this->_entities[$alias] = $className;
    }
    
    public function addJoin($join)
    {
        
    }
    
    public function setParameter($key, $value)
    {
        $this->_params[$key] = $value;
    }
    
    public function addParameter($value)
    {
        $this->_params[] = $value;
    }
    
    
    public function execute(array $params)
    {
        if ($this->_entities) {
            //...
        } else {
            return $this->_conn->execute($this->_sql, array_merge($this->_params, $params));
        }
    }
    
    public function executeUpdate(array $params)
    {
        return $this->_conn->exec($this->_sql, array_merge($this->_params, $params));
    }
}