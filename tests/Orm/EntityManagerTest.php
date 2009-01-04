<?php
require_once 'lib/DoctrineTestInit.php';

#namespace Doctrine\Tests\ORM;

/**
 * EntityManager tests.
 */
class Orm_EntityManagerTest extends Doctrine_OrmTestCase
{
    private $_em;

    function setUp() {
        parent::setUp();
        $this->_em = $this->_getTestEntityManager();
    }

    public function testSettingInvalidFlushModeThrowsException()
    {
        $prev = $this->_em->getFlushMode();
        try {
            $this->_em->setFlushMode('foobar');
            $this->fail("Setting invalid flushmode did not trigger exception.");
        } catch (Doctrine_ORM_Exceptions_EntityManagerException $expected) {}
        $this->_em->setFlushMode($prev);
    }    
}