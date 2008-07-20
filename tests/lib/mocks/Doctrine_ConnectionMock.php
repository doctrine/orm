<?php

require_once 'lib/mocks/Doctrine_SequenceMock.php';

class Doctrine_ConnectionMock extends Doctrine_Connection
{
    protected $_driverName = 'Mysql';
    private $_sequenceModuleMock;
    
    public function getSequenceModule()
    {
        if ( ! $this->_sequenceModuleMock) {
            $this->_sequenceModuleMock = new Doctrine_SequenceMock($this);
        }
        return $this->_sequenceModuleMock;
    }
    
}

?>