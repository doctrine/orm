<?php

#namespace Doctrine::DBAL::Id;

/**
 * Enter description here...
 *
 * @todo Rename to AbstractIdGenerator
 */
abstract class Doctrine_Id_AbstractIdGenerator
{
    protected $_em;
    
    public function __construct(Doctrine_EntityManager $em)
    {
        $this->_em = $em;
    }
    
    abstract public function configureForClass(Doctrine_ClassMetadata $class);
    
    abstract public function generate();
}

?>