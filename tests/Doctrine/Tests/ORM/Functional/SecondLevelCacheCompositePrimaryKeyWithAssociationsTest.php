<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\GeoNames\Admin1;
use Doctrine\Tests\Models\GeoNames\Admin1AlternateName;
use Doctrine\Tests\Models\GeoNames\Country;
use Doctrine\Tests\OrmFunctionalTestCase;

class SecondLevelCacheCompositePrimaryKeyWithAssociationsTest extends OrmFunctionalTestCase
{

    /**
     * @var \Doctrine\ORM\Cache
     */
    protected $cache;

    public function setUp()
    {
        $this->enableSecondLevelCache();
        $this->useModelSet('geonames');
        parent::setUp();

        $this->cache = $this->_em->getCache();

        $it = new Country("IT", "Italy");

        $this->_em->persist($it);
        $this->_em->flush();

        $admin1 = new Admin1(1, "Rome", $it);

        $this->_em->persist($admin1);
        $this->_em->flush();

        $name1 = new Admin1AlternateName(1, "Roma", $admin1);
        $name2 = new Admin1AlternateName(2, "Rome", $admin1);

        $admin1->names[] = $name1;
        $admin1->names[] = $name2;

        $this->_em->persist($admin1);
        $this->_em->persist($name1);
        $this->_em->persist($name2);

        $this->_em->flush();
        $this->_em->clear();
        $this->evictRegions();

    }

    public function testFindByReturnsCachedEntity()
    {
        $admin1Repo = $this->_em->getRepository(Admin1::class);

        $queries = $this->getCurrentQueryCount();

        $admin1Rome = $admin1Repo->findOneBy(['country' => 'IT', 'id' => 1]);

        $this->assertEquals("Italy", $admin1Rome->country->name);
        $this->assertEquals(2, count($admin1Rome->names));
        $this->assertEquals($queries + 3, $this->getCurrentQueryCount());

        $this->_em->clear();

        $queries = $this->getCurrentQueryCount();

        $admin1Rome = $admin1Repo->findOneBy(['country' => 'IT', 'id' => 1]);

        $this->assertEquals("Italy", $admin1Rome->country->name);
        $this->assertEquals(2, count($admin1Rome->names));
        $this->assertEquals($queries, $this->getCurrentQueryCount());
    }

    private function evictRegions()
    {
        $this->cache->evictQueryRegions();
        $this->cache->evictEntityRegions();
        $this->cache->evictCollectionRegions();
    }
}
