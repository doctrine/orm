<?php

class Doctrine_ORM_Id_IdentityGenerator extends Doctrine_ORM_Id_AbstractIdGenerator
{
    /**
     * Enter description here...
     *
     * @param Doctrine_ORM_Entity $entity
     * @return unknown
     * @override
     */
    public function generate($entity)
    {
        return self::POST_INSERT_INDICATOR;
    }
    
    public function getPostInsertId()
    {
        return $this->_em->getConnection()->lastInsertId();
    }
}

?>