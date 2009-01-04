<?php

#namespace Doctrine\Tests\Mocks;

#use Doctrine\DBAL\Connection;

require_once 'lib/mocks/Doctrine_SequenceMock.php';
require_once 'lib/mocks/Doctrine_DatabasePlatformMock.php';

class Doctrine_ConnectionMock extends Doctrine_DBAL_Connection
{
    private $_platformMock;
    private $_lastInsertId = 0;
    private $_inserts = array();
    
    public function __construct() {
        $this->_platformMock = new Doctrine_DatabasePlatformMock();
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

