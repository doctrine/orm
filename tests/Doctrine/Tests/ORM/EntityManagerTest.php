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

    /**
     * @group DDC-899
     */
    public function testIsOpen()
    {
        $this->assertTrue($this->_em->isOpen());
        $this->_em->close();
        $this->assertFalse($this->_em->isOpen());
    }

    public function testGetConnection()
    {
        $this->assertInstanceOf('\Doctrine\DBAL\Connection', $this->_em->getConnection());
    }

    public function testGetMetadataFactory()
    {
        $this->assertInstanceOf('\Doctrine\ORM\Mapping\ClassMetadataFactory', $this->_em->getMetadataFactory());
    }

    public function testGetConfiguration()
    {
        $this->assertInstanceOf('\Doctrine\ORM\Configuration', $this->_em->getConfiguration());
    }

    public function testGetUnitOfWork()
    {
        $this->assertInstanceOf('\Doctrine\ORM\UnitOfWork', $this->_em->getUnitOfWork());
    }

    public function testGetProxyFactory()
    {
        $this->assertInstanceOf('\Doctrine\ORM\Proxy\ProxyFactory', $this->_em->getProxyFactory());
    }

    public function testGetEventManager()
    {
        $this->assertInstanceOf('\Doctrine\Common\EventManager', $this->_em->getEventManager());
    }

    public function testCreateNativeQuery()
    {
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $query = $this->_em->createNativeQuery('SELECT foo', $rsm);

        $this->assertSame('SELECT foo', $query->getSql());
    }

    public function testCreateQueryBuilder()
    {
        $this->assertInstanceOf('\Doctrine\ORM\QueryBuilder', $this->_em->createQueryBuilder());
    }

    public function testCreateQueryBuilderAliasValid()
    {
        $q = $this->_em->createQueryBuilder()
             ->select('u')->from('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $q2 = clone $q;

        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q->getQuery()->getDql());
        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q2->getQuery()->getDql());

        $q3 = clone $q;

        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q3->getQuery()->getDql());
    }

    public function testCreateQuery_DqlIsOptional()
    {
        $this->assertInstanceOf('\Doctrine\ORM\Query', $this->_em->createQuery());
    }

    public function testGetPartialReference()
    {
        $user = $this->_em->getPartialReference('Doctrine\Tests\Models\CMS\CmsUser', 42);
        $this->assertTrue($this->_em->contains($user));
        $this->assertEquals(42, $user->id);
        $this->assertNull($user->getName());
    }

    public function testCreateQuery()
    {
        $q = $this->_em->createQuery('SELECT 1');
        $this->assertInstanceOf('\Doctrine\ORM\Query', $q);
        $this->assertEquals('SELECT 1', $q->getDql());
    }

    static public function dataMethodsAffectedByNoObjectArguments()
    {
        return array(
            array('persist'),
            array('remove'),
            array('merge'),
            array('refresh'),
            array('detach')
        );
    }

    /**
     * @dataProvider dataMethodsAffectedByNoObjectArguments
     * @expectedException \InvalidArgumentException
     * @param string $methodName
     */
    public function testThrowsExceptionOnNonObjectValues($methodName) {
        $this->_em->$methodName(null);
    }

    static public function dataAffectedByErrorIfClosedException()
    {
        return array(
            array('flush'),
            array('persist'),
            array('remove'),
            array('merge'),
            array('refresh'),
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
    
    /**
     * @group DDC-1125
     */
    public function testTransactionalAcceptsReturn()
    {
        $return = $this->_em->transactional(function ($em) {
            return 'foo';
        });
        
        $this->assertEquals('foo', $return);
    }
}