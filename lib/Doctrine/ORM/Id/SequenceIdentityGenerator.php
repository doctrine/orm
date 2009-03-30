<?php

namespace Doctrine\ORM\Id;

use Doctrine\ORM\EntityManager;

class SequenceIdentityGenerator extends IdentityGenerator
{
    private $_sequenceName;
    
    public function __construct($sequenceName)
    {
        $this->_sequenceName = $sequenceName;
    }

    public function generate(EntityManager $em, $entity)
    {
        return $em->getConnection()->lastInsertId($this->_sequenceName);
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