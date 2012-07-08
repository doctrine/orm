<?php
namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\Models\Cms\CmsUser;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\Common\Cache\ArrayCache;

/**
 * @group DDC-1766
 */
class HydrationCacheTest extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');

        parent::setUp();

        $user = new CmsUser;
        $user->name = "Benjamin";
        $user->username = "beberlei";
        $user->status = 'active';

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testHydrationCache()
    {
        $cache = new ArrayCache();
        $dql = "SELECT u FROM Doctrine\Tests\Models\Cms\CmsUser u";

        $users = $this->_em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(null, null, $cache))
                      ->getResult();

        $c = $this->getCurrentQueryCount();
        $users = $this->_em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(null, null, $cache))
                      ->getResult();

        $this->assertEquals($c, $this->getCurrentQueryCount(), "Should not execute query. Its cached!");

        $users = $this->_em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(null, null, $cache))
                      ->getArrayResult();

        $this->assertEquals($c + 1, $this->getCurrentQueryCount(), "Hydration is part of cache key.");

        $users = $this->_em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(null, null, $cache))
                      ->getArrayResult();

        $this->assertEquals($c + 1, $this->getCurrentQueryCount(), "Hydration now cached");

        $users = $this->_em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(null, 'cachekey', $cache))
                      ->getArrayResult();

        $this->assertTrue($cache->contains('cachekey'), 'Explicit cache key');

        $users = $this->_em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(null, 'cachekey', $cache))
                      ->getArrayResult();
        $this->assertEquals($c + 2, $this->getCurrentQueryCount(), "Hydration now cached");
    }

    public function testHydrationParametersSerialization()
    {
        $cache = new ArrayCache();

        $dql   = "SELECT u FROM Doctrine\Tests\Models\Cms\CmsUser u WHERE u.id = ?1";
        $query = $this->_em->createQuery($dql)
            ->setParameter(1, $userId = 1)
            ->setHydrationCacheProfile(new QueryCacheProfile(null, null, $cache));

        $query->getResult();

        $c = $this->getCurrentQueryCount();

        $query->getResult();

        $this->assertEquals($c, $this->getCurrentQueryCount(), "Should not execute query. Its cached!");
    }
}

