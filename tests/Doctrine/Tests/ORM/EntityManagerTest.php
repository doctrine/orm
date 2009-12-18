<?php

namespace Doctrine\Tests\ORM;

require_once __DIR__ . '/../TestInit.php';

class EntityManagerTest extends \Doctrine\Tests\OrmTestCase
{
    private $_em;

    function setUp()
    {
        parent::setUp();
        $this->_em = $this->_getTestEntityManager();
    }

    public function testSettingInvalidFlushModeThrowsException()
    {
        $prev = $this->_em->getFlushMode();
        try {
            $this->_em->setFlushMode('foobar');
            $this->fail("Setting invalid flushmode did not trigger exception.");
        } catch (\Doctrine\ORM\ORMException $expected) {}
        $this->_em->setFlushMode($prev);
    }

    public function testGetConnection()
    {
        $this->assertType('\Doctrine\DBAL\Connection', $this->_em->getConnection());
    }

    public function testGetMetadataFactory()
    {
        $this->assertType('\Doctrine\ORM\Mapping\ClassMetadataFactory', $this->_em->getMetadataFactory());
    }

    public function testGetConfiguration()
    {
        $this->assertType('\Doctrine\ORM\Configuration', $this->_em->getConfiguration());
    }

    public function testGetUnitOfWork()
    {
        $this->assertType('\Doctrine\ORM\UnitOfWork', $this->_em->getUnitOfWork());
    }

    public function testGetProxyFactory()
    {
        $this->assertType('\Doctrine\ORM\Proxy\ProxyFactory', $this->_em->getProxyFactory());
    }

    public function testGetEventManager()
    {
        $this->assertType('\Doctrine\Common\EventManager', $this->_em->getEventManager());
    }

    public function testGetDefaultFlushMode_OnCommit()
    {
        $this->assertEquals(\Doctrine\ORM\EntityManager::FLUSHMODE_COMMIT, $this->_em->getFlushMode());
    }

    public function testCreateNativeQuery()
    {
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $query = $this->_em->createNativeQuery('SELECT foo', $rsm);

        $this->assertSame('SELECT foo', $query->getSql());
    }

    public function testCreateQueryBuilder()
    {
        $this->assertType('\Doctrine\ORM\QueryBuilder', $this->_em->createQueryBuilder());
    }

    public function testCreateQuery_DqlIsOptional()
    {
        $this->assertType('\Doctrine\ORM\Query', $this->_em->createQuery());
    }

    public function testCreateQuery()
    {
        $q = $this->_em->createQuery('SELECT 1');
        $this->assertType('\Doctrine\ORM\Query', $q);
        $this->assertEquals('SELECT 1', $q->getDql());
    }

    static public function dataAffectedByErrorIfClosedException()
    {
        return array(
            array('flush'),
            array('persist'),
            array('remove'),
            array('merge'),
            array('refresh'),
            array('copy'),
        );
    }

    /**
     * @dataProvider dataAffectedByErrorIfClosedException
     * @param string $methodName
     */
    public function testAffectedByErrorIfClosedException($methodName)
    {
        $this->setExpectedException('Doctrine\ORM\ORMException', 'closed');

        $this->_em->close();
        $this->_em->$methodName(new \stdClass());
    }
}