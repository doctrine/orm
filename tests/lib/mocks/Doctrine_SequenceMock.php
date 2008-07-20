<?php

class Doctrine_SequenceMock extends Doctrine_Sequence
{
    private $_sequenceNumber = 0;
    
    /**
     * @override
     */
    public function nextId($seqName, $ondemand = true)
    {
        return $this->_sequenceNumber++;
    }

    /**
     * @override
     */
    public function lastInsertId($table = null, $field = null)
    {
        return $this->_sequenceNumber - 1;
    }

    /**
     * @override
     */
    public function currId($seqName)
    {
        return $this->_sequenceNumber;
    }
    
    public function reset()
    {
        $this->_sequenceNumber = 0;
    }
}

?>