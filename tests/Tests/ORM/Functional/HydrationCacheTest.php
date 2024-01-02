<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\Tests\Models\Cms\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[Group('DDC-1766')]
class HydrationCacheTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();

        $user           = new CmsUser();
        $user->name     = 'Benjamin';
        $user->username = 'beberlei';
        $user->status   = 'active';

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testHydrationCache(): void
    {
        $cache = new ArrayAdapter();

        $dql = 'SELECT u FROM Doctrine\Tests\Models\Cms\CmsUser u';

        $this->_em->createQuery($dql)
                  ->setHydrationCacheProfile(new QueryCacheProfile(0, null, $cache))
                  ->getResult();

        $this->getQueryLog()->reset()->enable();
        $this->_em->createQuery($dql)
                  ->setHydrationCacheProfile(new QueryCacheProfile(0, null, $cache))
                  ->getResult();

        $this->assertQueryCount(0, 'Should not execute query. Its cached!');

        $this->_em->createQuery($dql)
                  ->setHydrationCacheProfile(new QueryCacheProfile(0, null, $cache))
                  ->getArrayResult();

        $this->assertQueryCount(1, 'Hydration is part of cache key.');

        $this->_em->createQuery($dql)
                  ->setHydrationCacheProfile(new QueryCacheProfile(0, null, $cache))
                  ->getArrayResult();

        $this->assertQueryCount(1, 'Hydration now cached');

        $this->_em->createQuery($dql)
                  ->setHydrationCacheProfile(new QueryCacheProfile(0, 'cachekey', $cache))
                  ->getArrayResult();

        self::assertTrue($cache->hasItem('cachekey'), 'Explicit cache key');

        $this->_em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(0, 'cachekey', $cache))
                      ->getArrayResult();
        $this->assertQueryCount(2, 'Hydration now cached');
    }

    public function testHydrationParametersSerialization(): void
    {
        $cache = new ArrayAdapter();

        $dql   = 'SELECT u FROM Doctrine\Tests\Models\Cms\CmsUser u WHERE u.id = ?1';
        $query = $this->_em->createQuery($dql)
            ->setParameter(1, 1)
            ->setHydrationCacheProfile(new QueryCacheProfile(0, null, $cache));

        $query->getResult();

        $this->getQueryLog()->reset()->enable();

        $query->getResult();

        $this->assertQueryCount(0, 'Should not execute query. Its cached!');
    }
}
