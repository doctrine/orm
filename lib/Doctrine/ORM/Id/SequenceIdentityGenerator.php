<?php

class Doctrine_ORM_Id_SequenceIdentityGenerator extends Doctrine_ORM_Id_IdentityGenerator
{
    private $_sequenceName;
    
    public function __construct($sequenceName)
    {
        $this->_sequenceName = $sequenceName;
    }
    
    /**
     * Enter description here...
     *
     * @param Doctrine_Connection $conn
     * @override
     */
    public function getPostInsertId()
    {
        return $this->_em->getConnection()->lastInsertId($this->_sequenceName);
    }
    
}

?>