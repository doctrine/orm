<?php

namespace Doctrine\ORM\Id;

class IdentityGenerator extends AbstractIdGenerator
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
        return $this->_em->getConnection()->lastInsertId();
    }

    /**
     * @return boolean
     * @override
     */
    public function isPostInsertGenerator() {
        return true;
    }
}

