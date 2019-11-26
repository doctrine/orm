<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\Tests\Models\Cms\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1766
 */
class HydrationCacheTest extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        $this->useModelSet('cms');

        parent::setUp();

        $user           = new CmsUser();
        $user->name     = 'Benjamin';
        $user->username = 'beberlei';
        $user->status   = 'active';

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();
    }

    public function testHydrationCache() : void
    {
        $cache = new ArrayCache();
        $dql   = 'SELECT u FROM Doctrine\Tests\Models\Cms\CmsUser u';

        $users = $this->em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(0, null, $cache))
                      ->getResult();

        $c     = $this->getCurrentQueryCount();
        $users = $this->em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(0, null, $cache))
                      ->getResult();

        self::assertEquals($c, $this->getCurrentQueryCount(), 'Should not execute query. Its cached!');

        $users = $this->em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(0, null, $cache))
                      ->getArrayResult();

        self::assertEquals($c + 1, $this->getCurrentQueryCount(), 'Hydration is part of cache key.');

        $users = $this->em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(0, null, $cache))
                      ->getArrayResult();

        self::assertEquals($c + 1, $this->getCurrentQueryCount(), 'Hydration now cached');

        $users = $this->em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(0, 'cachekey', $cache))
                      ->getArrayResult();

        self::assertTrue($cache->contains('cachekey'), 'Explicit cache key');

        $users = $this->em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(0, 'cachekey', $cache))
                      ->getArrayResult();
        self::assertEquals($c + 2, $this->getCurrentQueryCount(), 'Hydration now cached');
    }

    public function testHydrationParametersSerialization() : void
    {
        $cache = new ArrayCache();

        $dql                          = 'SELECT u FROM Doctrine\Tests\Models\Cms\CmsUser u WHERE u.id = ?1';
        $query                        = $this->em->createQuery($dql)
            ->setParameter(1, $userId = 1)
            ->setHydrationCacheProfile(new QueryCacheProfile(0, null, $cache));

        $query->getResult();

        $c = $this->getCurrentQueryCount();

        $query->getResult();

        self::assertEquals($c, $this->getCurrentQueryCount(), 'Should not execute query. Its cached!');
    }
}
