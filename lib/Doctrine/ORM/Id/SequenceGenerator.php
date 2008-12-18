<?php

class Doctrine_ORM_Id_SequenceGenerator extends Doctrine_ORM_Id_AbstractIdGenerator
{
    private $_sequenceName;
    
    public function __construct($sequenceName)
    {
        $this->_sequenceName = $sequenceName;
    }
    
    /**
     * Enter description here...
     *
     * @param Doctrine_ORM_Entity $entity
     * @override
     */
    public function generate($entity)
    {
        $conn = $this->_em->getConnection();
        $sql = $conn->getDatabasePlatform()->getSequenceNextValSql($this->_sequenceName);
        return $conn->fetchOne($sql);
    }
}

?>