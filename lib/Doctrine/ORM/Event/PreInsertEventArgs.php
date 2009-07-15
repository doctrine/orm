<?php

namespace Doctrine\ORM\Event;

use Doctrine\Common\EventArgs;

/**
 * Class that holds event arguments for a preInsert event.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class PreInsertEventArgs extends EventArgs
{
    private $_entity;
    private $_entityChangeSet;

    public function __construct($entity, array $changeSet)
    {
        $this->_entity = $entity;
        $this->_entityChangeSet = $changeSet;
    }

    public function getEntity()
    {
        return $this->_entity;
    }
    
    public function getEntityChangeSet()
    {
    	return $this->_entityChangeSet;
    }
    
    /*public function getEntityId()
    {
    	
    }*/
}

