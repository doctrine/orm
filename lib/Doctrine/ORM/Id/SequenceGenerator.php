<?php

namespace Doctrine\ORM\Id;

use Doctrine\ORM\EntityManager;

class SequenceGenerator extends AbstractIdGenerator
{
    private $_allocationSize;
    private $_sequenceName;
    private $_nextValue = 0;
    private $_maxValue = null;
    
    public function __construct(EntityManager $em, $sequenceName, $allocationSize = 20)
    {
        parent::__construct($em);
        $this->_sequenceName = $sequenceName;
        $this->_allocationSize = $allocationSize;
    }
    
    /**
     * Generates an ID for the given entity.
     *
     * @param object $entity
     * @return integer|float The generated value.
     * @override
     */
    public function generate($entity)
    {
        if ($this->_maxValue === null || $this->_nextValue == $this->_maxValue) {
            // Allocate new values
            $conn = $this->_em->getConnection();
            $sql = $conn->getDatabasePlatform()->getSequenceNextValSql($this->_sequenceName);
            $this->_maxValue = $conn->fetchOne($sql);
            $this->_nextValue = $this->_maxValue - $this->_allocationSize;
        }
        return $this->_nextValue++;
    }

    public function getCurrentMaxValue()
    {
        return $this->_maxValue;
    }
    
    public function getNextValue()
    {
        return $this->_nextValue;
    }
}
