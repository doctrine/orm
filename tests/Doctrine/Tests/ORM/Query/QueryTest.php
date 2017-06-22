<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Tests\Mocks\DriverConnectionMock;
use Doctrine\Tests\Mocks\StatementArrayMock;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\OrmTestCase;

class QueryTest extends OrmTestCase
{
    /** @var EntityManager */
    protected $em = null;

    protected function setUp()
    {
        $this->em = $this->getTestEntityManager();
    }

    public function testGetParameters()
    {
        $query = $this->em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u where u.username = ?1");

        $parameters = new ArrayCollection();

        self::assertEquals($parameters, $query->getParameters());
    }

    public function testGetParameters_HasSomeAlready()
    {
        $query = $this->em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u where u.username = ?1");
        $query->setParameter(2, 84);

        $parameters = new ArrayCollection();
        $parameters->add(new Parameter(2, 84));

        self::assertEquals($parameters, $query->getParameters());
    }

    public function testSetParameters()
    {
        $query = $this->em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u where u.username = ?1");

        $parameters = new ArrayCollection();
        $parameters->add(new Parameter(1, 'foo'));
        $parameters->add(new Parameter(2, 'bar'));

        $query->setParameters($parameters);

        self::assertEquals($parameters, $query->getParameters());
    }

    public function testFree()
    {
        $query = $this->em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u where u.username = ?1");
        $query->setParameter(2, 84, \PDO::PARAM_INT);

        $query->free();

        self::assertEquals(0, count($query->getParameters()));
    }

    public function testClone()
    {
        $dql = "select u from Doctrine\Tests\Models\CMS\CmsUser u where u.username = ?1";

        $query = $this->em->createQuery($dql);
        $query->setParameter(2, 84, \PDO::PARAM_INT);
        $query->setHint('foo', 'bar');

        $cloned = clone $query;

        self::assertEquals($dql, $cloned->getDQL());
        self::assertEquals(0, count($cloned->getParameters()));
        self::assertFalse($cloned->getHint('foo'));
    }

    public function testFluentQueryInterface()
    {
        $q = $this->em->createQuery("select a from Doctrine\Tests\Models\CMS\CmsArticle a");
        $q2 = $q->expireQueryCache(true)
          ->setQueryCacheLifetime(3600)
          ->setQueryCacheDriver(null)
          ->expireResultCache(true)
          ->setHint('foo', 'bar')
          ->setHint('bar', 'baz')
          ->setParameter(1, 'bar')
          ->setParameters(new ArrayCollection([new Parameter(2, 'baz')]))
          ->setResultCacheDriver(null)
          ->setResultCacheId('foo')
          ->setDQL('foo')
          ->setFirstResult(10)
          ->setMaxResults(10);

        self::assertSame($q2, $q);
    }

    /**
     * @group DDC-968
     */
    public function testHints()
    {
        $q = $this->em->createQuery("select a from Doctrine\Tests\Models\CMS\CmsArticle a");
        $q->setHint('foo', 'bar')->setHint('bar', 'baz');

        self::assertEquals('bar', $q->getHint('foo'));
        self::assertEquals('baz', $q->getHint('bar'));
        self::assertEquals(['foo' => 'bar', 'bar' => 'baz'], $q->getHints());
        self::assertTrue($q->hasHint('foo'));
        self::assertFalse($q->hasHint('barFooBaz'));
    }

    /**
     * @group DDC-1588
     */
    public function testQueryDefaultResultCache()
    {
        $this->em->getConfiguration()->setResultCacheImpl(new ArrayCache());
        $q = $this->em->createQuery("select a from Doctrine\Tests\Models\CMS\CmsArticle a");
        $q->useResultCache(true);
        self::assertSame($this->em->getConfiguration()->getResultCacheImpl(), $q->getQueryCacheProfile()->getResultCacheDriver());
    }

    /**
     * @expectedException Doctrine\ORM\Query\QueryException
     **/
    public function testIterateWithNoDistinctAndWrongSelectClause()
    {
        $q = $this->em->createQuery("select u, a from Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a");
        $q->iterate();
    }

    /**
     * @expectedException Doctrine\ORM\Query\QueryException
     **/
    public function testIterateWithNoDistinctAndWithValidSelectClause()
    {
        $q = $this->em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a");
        $q->iterate();
    }

    public function testIterateWithDistinct()
    {
        $q = $this->em->createQuery("SELECT DISTINCT u from Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a");

        self::assertInstanceOf(IterableResult::class, $q->iterate());
    }

    /**
     * @group DDC-1697
     */
    public function testCollectionParameters()
    {
        $cities = [
            0 => "Paris",
            3 => "Canne",
            9 => "St Julien"
        ];

        $query  = $this->em
                ->createQuery("SELECT a FROM Doctrine\Tests\Models\CMS\CmsAddress a WHERE a.city IN (:cities)")
                ->setParameter('cities', $cities);

        $parameters = $query->getParameters();
        $parameter  = $parameters->first();

        self::assertEquals('cities', $parameter->getName());
        self::assertEquals($cities, $parameter->getValue());
    }

    /**
     * @group DDC-2224
     */
    public function testProcessParameterValueClassMetadata()
    {
        $query  = $this->em->createQuery("SELECT a FROM Doctrine\Tests\Models\CMS\CmsAddress a WHERE a.city IN (:cities)");
        self::assertEquals(
            CmsAddress::class,
            $query->processParameterValue($this->em->getClassMetadata(CmsAddress::class))
        );
    }

    public function testDefaultQueryHints()
    {
        $config = $this->em->getConfiguration();
        $defaultHints = [
            'hint_name_1' => 'hint_value_1',
            'hint_name_2' => 'hint_value_2',
            'hint_name_3' => 'hint_value_3',
        ];

        $config->setDefaultQueryHints($defaultHints);
        $query = $this->em->createQuery();
        self::assertSame($config->getDefaultQueryHints(), $query->getHints());
        $this->em->getConfiguration()->setDefaultQueryHint('hint_name_1', 'hint_another_value_1');
        self::assertNotSame($config->getDefaultQueryHints(), $query->getHints());
        $q2 = clone $query;
        self::assertSame($config->getDefaultQueryHints(), $q2->getHints());
    }

    /**
     * @group DDC-3714
     */
    public function testResultCacheCaching()
    {
        $this->em->getConfiguration()->setResultCacheImpl(new ArrayCache());
        $this->em->getConfiguration()->setQueryCacheImpl(new ArrayCache());
        /** @var DriverConnectionMock $driverConnectionMock */
        $driverConnectionMock = $this->em->getConnection()->getWrappedConnection();
        $stmt = new StatementArrayMock([
            [
                'c0' => 1,
            ]
        ]);
        $driverConnectionMock->setStatementMock($stmt);
        $res = $this->em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u")
            ->useQueryCache(true)
            ->useResultCache(true, 60)
            //let it cache
            ->getResult();

        self::assertCount(1, $res);

        $driverConnectionMock->setStatementMock(null);

        $res = $this->em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u")
            ->useQueryCache(true)
            ->useResultCache(false)
            ->getResult();
        self::assertCount(0, $res);
    }

    /**
     * @group DDC-3741
     */
    public function testSetHydrationCacheProfileNull()
    {
        $query = $this->em->createQuery();
        $query->setHydrationCacheProfile(null);

        self::assertNull($query->getHydrationCacheProfile());
    }

    /**
     * @group 2947
     */
    public function testResultCacheEviction()
    {
        $this->em->getConfiguration()->setResultCacheImpl(new ArrayCache());

        $query = $this->em
            ->createQuery("SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u")
            ->useResultCache(true);

        /** @var DriverConnectionMock $driverConnectionMock */
        $driverConnectionMock = $this->em->getConnection()
            ->getWrappedConnection();

        // Performs the query and sets up the initial cache
        self::assertCount(0, $query->getResult());

        $driverConnectionMock->setStatementMock(new StatementArrayMock([['c0' => 1]]));

        // Performs the query and sets up the initial cache
        self::assertCount(1, $query->expireResultCache(true)->getResult());

        $driverConnectionMock->setStatementMock(new StatementArrayMock([['c0' => 1], ['c0' => 2]]));

        // Retrieves cached data since expire flag is false and we have a cached result set
        self::assertCount(1, $query->expireResultCache(false)->getResult());

        // Performs the query and caches the result set since expire flag is true
        self::assertCount(2, $query->expireResultCache(true)->getResult());

        $driverConnectionMock->setStatementMock(new StatementArrayMock([['c0' => 1]]));

        // Retrieves cached data since expire flag is false and we have a cached result set
        self::assertCount(2, $query->expireResultCache(false)->getResult());
    }

    /**
     * @group #6162
     */
    public function testSelectJoinSubquery()
    {
        $query = $this->em->createQuery("select u from Doctrine\Tests\Models\CMS\CmsUser u JOIN (SELECT )");

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Subquery');
        $query->getSQL();
    }

    /**
     * @group #6162
     */
    public function testSelectFromSubquery()
    {
        $query = $this->em->createQuery("select u from (select Doctrine\Tests\Models\CMS\CmsUser c) as u");

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Subquery');
        $query->getSQL();
    }
}
