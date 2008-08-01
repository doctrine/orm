<?php

require_once 'lib/mocks/Doctrine_SequenceMock.php';
require_once 'lib/mocks/Doctrine_DatabasePlatformMock.php';

class Doctrine_ConnectionMock extends Doctrine_Connection
{
    protected $_driverName = 'Mock';
    private $_sequenceModuleMock;
    private $_platformMock;
    
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
    
    /* Mock API */
    
    public function setDatabasePlatform($platform)
    {
        $this->_platformMock = $platform;
    }
    
    public function setSequenceManager($seqManager)
    {
        $this->_sequenceModuleMock = $seqManager;
    }
}

?>