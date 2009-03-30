<?php

namespace Doctrine\ORM\Id;

use Doctrine\ORM\EntityManager;

class IdentityGenerator extends AbstractIdGenerator
{
    /**
     * Generates an ID for the given entity.
     *
     * @param object $entity
     * @return integer|float
     * @override
     */
    public function generate(EntityManager $em, $entity)
    {
        return $em->getConnection()->lastInsertId();
    }

    /**
     * @return boolean
     * @override
     */
    public function isPostInsertGenerator()
    {
        return true;
    }
}