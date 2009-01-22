<?php

namespace Doctrine\ORM\Id;

/**
 * Id generator that uses a single-row database table and a hi/lo algorithm.  
 *
 * @since 2.0
 */
class TableGenerator extends AbstractIdGenerator
{
    
    public function generate($entity)
    {
        throw new \Exception("Not implemented");
    }
    
}

