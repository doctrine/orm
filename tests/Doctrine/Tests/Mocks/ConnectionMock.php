<?php

namespace Doctrine\Tests\Mocks;

class ConnectionMock extends \Doctrine\DBAL\Connection
{
    private $_platformMock;
    private $_lastInsertId = 0;
    private $_inserts = array();
    
    public function __construct(array $params, $driver, $config = null, $eventManager = null)
    {
        parent::__construct($params, $driver, $config, $eventManager);
        $this->_platformMock = new DatabasePlatformMock();
        $this->_platform = $this->_platformMock;
    }
    
    /**
     * @override
     */
    public function getDatabasePlatform()
    {
        return $this->_platformMock;
    }
    
    /**
     * @override
     */
    public function insert($tableName, array $data)
    {
        $this->_inserts[$tableName][] = $data;
    }
    
    /**
     * @override
     */
    public function lastInsertId($seqName = null)
    {
        return $this->_lastInsertId;
    }
    
    /**
     * @override
     */
    public function quote($input, $type = null)
    {
        if ($type === 'string') {
            return "'" . $input . "'";
        }
        return $input;
    }
    
    /* Mock API */
    
    public function setDatabasePlatform($platform)
    {
        $this->_platformMock = $platform;
    }
    
    public function setLastInsertId($id)
    {
        $this->_lastInsertId = $id;
    }
    
    public function getInserts()
    {
        return $this->_inserts;
    }
    
    public function reset()
    {
        $this->_inserts = array();
        $this->_lastInsertId = 0;
    }
}