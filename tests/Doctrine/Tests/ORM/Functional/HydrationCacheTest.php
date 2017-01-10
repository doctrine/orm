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

        $user = new CmsUser();
        $user->name = "Benjamin";
        $user->username = "beberlei";
        $user->status = 'active';

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();
    }

    public function testHydrationCache()
    {
        $cache = new ArrayCache();
        $dql = "SELECT u FROM Doctrine\Tests\Models\Cms\CmsUser u";

        $users = $this->em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(null, null, $cache))
                      ->getResult();

        $c = $this->getCurrentQueryCount();
        $users = $this->em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(null, null, $cache))
                      ->getResult();

        self::assertEquals($c, $this->getCurrentQueryCount(), "Should not execute query. Its cached!");

        $users = $this->em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(null, null, $cache))
                      ->getArrayResult();

        self::assertEquals($c + 1, $this->getCurrentQueryCount(), "Hydration is part of cache key.");

        $users = $this->em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(null, null, $cache))
                      ->getArrayResult();

        self::assertEquals($c + 1, $this->getCurrentQueryCount(), "Hydration now cached");

        $users = $this->em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(null, 'cachekey', $cache))
                      ->getArrayResult();

        self::assertTrue($cache->contains('cachekey'), 'Explicit cache key');

        $users = $this->em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(null, 'cachekey', $cache))
                      ->getArrayResult();
        self::assertEquals($c + 2, $this->getCurrentQueryCount(), "Hydration now cached");
    }

    public function testHydrationParametersSerialization()
    {
        $cache = new ArrayCache();

        $dql   = "SELECT u FROM Doctrine\Tests\Models\Cms\CmsUser u WHERE u.id = ?1";
        $query = $this->em->createQuery($dql)
            ->setParameter(1, $userId = 1)
            ->setHydrationCacheProfile(new QueryCacheProfile(null, null, $cache));

        $query->getResult();

        $c = $this->getCurrentQueryCount();

        $query->getResult();

        self::assertEquals($c, $this->getCurrentQueryCount(), "Should not execute query. Its cached!");
    }
}

