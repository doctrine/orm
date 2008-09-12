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
     * @param Doctrine_Entity $entity
     * @return unknown
     * @override
     */
    public function generate(Doctrine_Entity $entity)
    {
        if ( ! $entity->_identifier()) {
            throw Doctrine_IdException::missingAssignedId($entity);
        }
    }
}

?>