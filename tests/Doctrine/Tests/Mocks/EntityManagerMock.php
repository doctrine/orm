<?php

namespace Doctrine\Tests\Mocks;

/**
 * Special EntityManager mock used for testing purposes.
 */
class EntityManagerMock extends \Doctrine\ORM\EntityManager
{
    private $_uowMock;
    private $_idGenerators = array();

    /**
     * @override
     */
    public function getUnitOfWork()
    {
        return isset($this->_uowMock) ? $this->_uowMock : parent::getUnitOfWork();
    }
    
    /* Mock API */

    /**
     * Sets a (mock) UnitOfWork that will be returned when getUnitOfWork() is called.
     *
     * @param <type> $uow
     */
    public function setUnitOfWork($uow)
    {
        $this->_uowMock = $uow;
    }
    
    /**
     * Mock factory method to create an EntityManager.
     *
     * @param unknown_type $conn
     * @param unknown_type $name
     * @param Doctrine_Configuration $config
     * @param Doctrine_EventManager $eventManager
     * @return Doctrine\ORM\EntityManager
     */
    public static function create($conn, $name, \Doctrine\ORM\Configuration $config = null,
            Doctrine_Common_EventManager $eventManager = null)
    {
        if (is_null($config)) {
            $config = new \Doctrine\ORM\Configuration();
        }
        if (is_null($eventManager)) {
            $eventManager = new \Doctrine\Common\EventManager();
        }
        
        return new EntityManagerMock($conn, $name, $config, $eventManager);   
    }

    public function setIdGenerator($className, $generator)
    {
        $this->_idGenerators[$className] = $generator;
    }

    /** @override */
    public function getIdGenerator($className)
    {

        if (isset($this->_idGenerators[$className])) {
            return $this->_idGenerators[$className];
        }
                
        return parent::getIdGenerator($className);
    }
}

