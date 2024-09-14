<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\Tests\Models\Cms\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function method_exists;

/** @group DDC-1766 */
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
        $arrayAdapter = new ArrayAdapter();
        $cache        = method_exists(QueryCacheProfile::class, 'getResultCache') ? $arrayAdapter : DoctrineProvider::wrap($arrayAdapter);

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

        self::assertTrue($arrayAdapter->hasItem('cachekey'), 'Explicit cache key');

        $this->_em->createQuery($dql)
                      ->setHydrationCacheProfile(new QueryCacheProfile(0, 'cachekey', $cache))
                      ->getArrayResult();
        $this->assertQueryCount(2, 'Hydration now cached');
    }

    public function testHydrationParametersSerialization(): void
    {
        $cache = new ArrayAdapter();
        if (! method_exists(QueryCacheProfile::class, 'getResultCache')) {
            $cache = DoctrineProvider::wrap($cache);
        }

        $dql   = 'SELECT u FROM Doctrine\Tests\Models\Cms\CmsUser u WHERE u.id = ?1';
        $query = $this->_em->createQuery($dql)
            ->setParameter(1, $userId = 1)
            ->setHydrationCacheProfile(new QueryCacheProfile(0, null, $cache));

        $query->getResult();

        $this->getQueryLog()->reset()->enable();

        $query->getResult();

        $this->assertQueryCount(0, 'Should not execute query. Its cached!');
    }
}
