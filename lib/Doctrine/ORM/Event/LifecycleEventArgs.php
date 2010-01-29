<?php

namespace Doctrine\ORM\Event;

class LifecycleEventArgs extends \Doctrine\Common\EventArgs
{
    //private $_em;
    private $_entity;
    
    public function __construct($entity)
    {
        $this->_entity = $entity;
    }
    
    public function getEntity()
    {
        return $this->_entity;
    }
    
    /*
    public function getEntityManager()
    {
        return $this->_em;
    }
    */
}