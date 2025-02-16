<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Cache;
use Doctrine\Tests\Models\GeoNames\Admin1;
use Doctrine\Tests\Models\GeoNames\Admin1AlternateName;
use Doctrine\Tests\Models\GeoNames\Country;
use Doctrine\Tests\OrmFunctionalTestCase;

class SecondLevelCacheCompositePrimaryKeyWithAssociationsTest extends OrmFunctionalTestCase
{
    /** @var Cache */
    protected $cache;

    protected function setUp(): void
    {
        $this->enableSecondLevelCache();
        $this->useModelSet('geonames');

        parent::setUp();

        $this->cache = $this->_em->getCache();

        $it = new Country('IT', 'Italy');

        $this->_em->persist($it);
        $this->_em->flush();

        $admin1 = new Admin1(1, 'Rome', $it);

        $this->_em->persist($admin1);
        $this->_em->flush();

        $name1 = new Admin1AlternateName(1, 'Roma', $admin1);
        $name2 = new Admin1AlternateName(2, 'Rome', $admin1);

        $admin1->names[] = $name1;
        $admin1->names[] = $name2;

        $this->_em->persist($admin1);
        $this->_em->persist($name1);
        $this->_em->persist($name2);

        $this->_em->flush();
        $this->_em->clear();
        $this->evictRegions();
    }

    public function testFindByReturnsCachedEntity(): void
    {
        $admin1Repo = $this->_em->getRepository(Admin1::class);

        $this->getQueryLog()->reset()->enable();

        $admin1Rome = $admin1Repo->findOneBy(['country' => 'IT', 'id' => 1]);

        self::assertEquals('Italy', $admin1Rome->country->name);
        self::assertCount(2, $admin1Rome->names);
        $this->assertQueryCount(3);

        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();

        $admin1Rome = $admin1Repo->findOneBy(['country' => 'IT', 'id' => 1]);

        self::assertEquals('Italy', $admin1Rome->country->name);
        self::assertCount(2, $admin1Rome->names);
        $this->assertQueryCount(0);
    }

    private function evictRegions(): void
    {
        $this->cache->evictQueryRegions();
        $this->cache->evictEntityRegions();
        $this->cache->evictCollectionRegions();
    }
}
