<?php 

namespace Doctrine\ORM;

/**
 * Represents a native SQL query.
 *
 * @since 2.0
 */
class NativeQuery
{
    private $_sql;
    private $_conn;
    private $_params = array();
    
    public function __construct($sql, Connection $conn)
    {        
        $this->_sql = $sql;
        $this->_conn = $conn;
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