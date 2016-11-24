<?php

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\OrmTestCase;

class EntityManagerTest extends OrmTestCase
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
        self::assertTrue($this->_em->isOpen());
        $this->_em->close();
        self::assertFalse($this->_em->isOpen());
    }

    public function testGetConnection()
    {
        self::assertInstanceOf('Doctrine\DBAL\Connection', $this->_em->getConnection());
    }

    public function testGetMetadataFactory()
    {
        self::assertInstanceOf('Doctrine\ORM\Mapping\ClassMetadataFactory', $this->_em->getMetadataFactory());
    }

    public function testGetConfiguration()
    {
        self::assertInstanceOf('Doctrine\ORM\Configuration', $this->_em->getConfiguration());
    }

    public function testGetUnitOfWork()
    {
        self::assertInstanceOf('Doctrine\ORM\UnitOfWork', $this->_em->getUnitOfWork());
    }

    public function testGetProxyFactory()
    {
        self::assertInstanceOf('Doctrine\ORM\Proxy\ProxyFactory', $this->_em->getProxyFactory());
    }

    public function testGetEventManager()
    {
        self::assertInstanceOf('Doctrine\Common\EventManager', $this->_em->getEventManager());
    }

    public function testCreateNativeQuery()
    {
        $rsm = new ResultSetMapping();
        $query = $this->_em->createNativeQuery('SELECT foo', $rsm);

        self::assertSame('SELECT foo', $query->getSql());
    }

    /**
     * @covers Doctrine\ORM\EntityManager::createNamedNativeQuery
     */
    public function testCreateNamedNativeQuery()
    {
        $rsm = new ResultSetMapping();
        $this->_em->getConfiguration()->addNamedNativeQuery('foo', 'SELECT foo', $rsm);

        $query = $this->_em->createNamedNativeQuery('foo');

        self::assertInstanceOf('Doctrine\ORM\NativeQuery', $query);
    }

    public function testCreateQueryBuilder()
    {
        self::assertInstanceOf('Doctrine\ORM\QueryBuilder', $this->_em->createQueryBuilder());
    }

    public function testCreateQueryBuilderAliasValid()
    {
        $q = $this->_em->createQueryBuilder()
             ->select('u')->from('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $q2 = clone $q;

        self::assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q->getQuery()->getDql());
        self::assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q2->getQuery()->getDql());

        $q3 = clone $q;

        self::assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $q3->getQuery()->getDql());
    }

    public function testCreateQuery_DqlIsOptional()
    {
        self::assertInstanceOf('Doctrine\ORM\Query', $this->_em->createQuery());
    }

    public function testGetPartialReference()
    {
        $user = $this->_em->getPartialReference('Doctrine\Tests\Models\CMS\CmsUser', 42);
        self::assertTrue($this->_em->contains($user));
        self::assertEquals(42, $user->id);
        self::assertNull($user->getName());
    }

    public function testCreateQuery()
    {
        $q = $this->_em->createQuery('SELECT 1');
        self::assertInstanceOf('Doctrine\ORM\Query', $q);
        self::assertEquals('SELECT 1', $q->getDql());
    }

    /**
     * @covers Doctrine\ORM\EntityManager::createNamedQuery
     */
    public function testCreateNamedQuery()
    {
        $this->_em->getConfiguration()->addNamedQuery('foo', 'SELECT 1');

        $query = $this->_em->createNamedQuery('foo');
        self::assertInstanceOf('Doctrine\ORM\Query', $query);
        self::assertEquals('SELECT 1', $query->getDql());
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
     */
    public function testThrowsExceptionOnNonObjectValues($methodName) {
        $this->expectException(ORMInvalidArgumentException::class);
        $this->expectExceptionMessage('EntityManager#' . $methodName . '() expects parameter 1 to be an entity object, NULL given.');

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
        $this->expectException(ORMException::class);
        $this->expectExceptionMessage('closed');

        $this->_em->close();
        $this->_em->$methodName(new \stdClass());
    }

    public function dataToBeReturnedByTransactional()
    {
        return array(
            array(null),
            array(false),
            array('foo'),
        );
    }

    /**
     * @dataProvider dataToBeReturnedByTransactional
     */
    public function testTransactionalAcceptsReturn($value)
    {
        self::assertSame(
            $value,
            $this->_em->transactional(function ($em) use ($value) {
                return $value;
            })
        );
    }

    public function testTransactionalAcceptsVariousCallables()
    {
        self::assertSame('callback', $this->_em->transactional(array($this, 'transactionalCallback')));
    }

    public function transactionalCallback($em)
    {
        self::assertSame($this->_em, $em);
        return 'callback';
    }
}
