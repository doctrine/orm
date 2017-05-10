<?php

namespace Doctrine\ORM;

/**
 * @author Iltar van der Berg <ivanderberg@hostnet.nl>
 */
interface ObjectPersisterInterface
{
    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::persist()
     */
    public function persist($entity);

    /**
     * @see \Doctrine\Common\Persistence\ObjectManager::flush()
     */
    public function flush($entity = null);
}