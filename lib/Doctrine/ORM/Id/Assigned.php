<?php

/**
 * Special generator for application-assigned identifiers (doesnt really generate anything).
 *
 * @since 2.0
 */
class Doctrine_ORM_Id_Assigned extends Doctrine_ORM_Id_AbstractIdGenerator
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
        if ( ! $entity->_identifier()) {
            throw new Doctrine_Exception("Entity '$entity' is missing an assigned Id");
        }
        return $entity->_identifier();
    }
}

?>