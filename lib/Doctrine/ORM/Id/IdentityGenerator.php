<?php

class Doctrine_ORM_Id_IdentityGenerator extends Doctrine_ORM_Id_AbstractIdGenerator
{
    /**
     * Enter description here...
     *
     * @param Doctrine_Entity $entity
     * @return unknown
     * @override
     */
    public function generate(Doctrine_Entity $entity)
    {
        return self::POST_INSERT_INDICATOR;
    }
    
    public function getPostInsertId()
    {
        return $this->_em->getConnection()->lastInsertId();
    }
}

?>