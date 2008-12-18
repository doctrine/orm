<?php

#namespace Doctrine::ORM::Id;

/**
 * Enter description here...
 *
 * @todo Rename to AbstractIdGenerator
 */
abstract class Doctrine_ORM_Id_AbstractIdGenerator
{
    const POST_INSERT_INDICATOR = 'POST_INSERT_INDICATOR';
    
    protected $_em;
    
    public function __construct(Doctrine_ORM_EntityManager $em)
    {
        $this->_em = $em;
    }
    
    abstract public function generate($entity);
}

?>