<?php

namespace Doctrine\ORM\Id;

use Doctrine\ORM\EntityManager;

/**
 * Id generator that uses a single-row database table and a hi/lo algorithm.  
 *
 * @since 2.0
 */
class TableGenerator extends AbstractIdGenerator
{
    public function generate(EntityManager $em, $entity)
    {
        throw \Doctrine\Common\DoctrineException::updateMe("Not implemented");
    }
}