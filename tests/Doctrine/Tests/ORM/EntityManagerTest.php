<?php

namespace Doctrine\Tests\ORM;

require_once __DIR__ . '/../TestInit.php';

/**
 * EntityManager tests.
 */
class EntityManagerTest extends \Doctrine\Tests\OrmTestCase
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
        } catch (\Doctrine\ORM\EntityManagerException $expected) {}
        $this->_em->setFlushMode($prev);
    }    
}