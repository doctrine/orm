<?php

require_once 'lib/mocks/Doctrine_SequenceMock.php';
require_once 'lib/mocks/Doctrine_DatabasePlatformMock.php';

class Doctrine_ConnectionMock extends Doctrine_Connection
{
    protected $_driverName = 'Mock';
    private $_sequenceModuleMock;
    private $_platformMock;
    private $_inserts = array();
    
    public function __construct(array $params)
    {
        parent::__construct($params);
    }
    
    /**
     * @override
     */
    public function getSequenceManager()
    {
        return $this->_sequenceModuleMock;
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
    
    /* Mock API */
    
    public function setDatabasePlatform($platform)
    {
        $this->_platformMock = $platform;
    }
    
    public function setSequenceManager($seqManager)
    {
        $this->_sequenceModuleMock = $seqManager;
    }
    
    public function getInserts()
    {
        return $this->_inserts;
    }
    
    public function reset()
    {
        $this->_inserts = array();
    }
}

?>