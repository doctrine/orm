<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\City;

require_once __DIR__ . '/../../TestInit.php';

/**
 * @group DDC-2183
 */
class SecondLevelCacheTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $countries = array();
    private $states    = array();
    private $cities    = array();

    protected function setUp()
    {

        $this->enableSecondLevelCache();

        $this->useModelSet('cache');

        parent::setUp();
    }

    private function loadFixturesCountries()
    {
        $brazil  = new Country("Brazil");
        $germany = new Country("Germany");

        $this->countries[] = $brazil;
        $this->countries[] = $germany;

        $this->_em->persist($brazil);
        $this->_em->persist($germany);
        $this->_em->flush();
    }

    private function loadFixturesStates()
    {
        $saopaulo   = new State("São Paulo", $this->countries[0]);
        $rio        = new State("Rio de janeiro", $this->countries[0]);
        $berlin     = new State("Berlin", $this->countries[1]);
        $bavaria    = new State("Bavaria", $this->countries[1]);

        $this->states[] = $saopaulo;
        $this->states[] = $rio;
        $this->states[] = $bavaria;
        $this->states[] = $berlin;

        $this->_em->persist($saopaulo);
        $this->_em->persist($rio);
        $this->_em->persist($bavaria);
        $this->_em->persist($berlin);

        $this->_em->flush();
    }

    private function loadFixturesCities()
    {
        $saopaulo   = new City("São Paulo", $this->states[0]);
        $rio        = new City("Rio de janeiro", $this->states[0]);
        $berlin     = new City("Berlin", $this->states[1]);
        $munich     = new City("Munich", $this->states[1]);

        $this->states[0]->addCity($saopaulo);
        $this->states[0]->addCity($rio);
        $this->states[1]->addCity($berlin);
        $this->states[1]->addCity($berlin);

        $this->cities[] = $saopaulo;
        $this->cities[] = $rio;
        $this->cities[] = $munich;
        $this->cities[] = $berlin;

        $this->_em->persist($saopaulo);
        $this->_em->persist($rio);
        $this->_em->persist($munich);
        $this->_em->persist($berlin);

        $this->_em->flush();
    }

    private function getQueryCount()
    {
        return count($this->_sqlLoggerStack->queries);
    }

    public function testPutAndLoadEntities()
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        $cache              = $this->_em->getCache();
        $entityClass        = 'Doctrine\Tests\Models\Cache\Country';

        $cache->evictEntityRegion($entityClass);

        $this->assertFalse($cache->containsEntity($entityClass, $this->countries[0]->getId()));
        $this->assertFalse($cache->containsEntity($entityClass, $this->countries[1]->getId()));

        $c1 = $this->_em->find($entityClass, $this->countries[0]->getId());
        $c2 = $this->_em->find($entityClass, $this->countries[1]->getId());

        $this->assertTrue($cache->containsEntity($entityClass, $this->countries[0]->getId()));
        $this->assertTrue($cache->containsEntity($entityClass, $this->countries[1]->getId()));

        $this->assertInstanceOf($entityClass, $c1);
        $this->assertInstanceOf($entityClass, $c2);

        $this->assertEquals($this->countries[0]->getId(), $c1->getId());
        $this->assertEquals($this->countries[0]->getName(), $c1->getName());

        $this->assertEquals($this->countries[1]->getId(), $c2->getId());
        $this->assertEquals($this->countries[1]->getName(), $c2->getName());

        $this->_em->clear();

        $queryCount = $this->getQueryCount();

        $c3 = $this->_em->find($entityClass, $this->countries[0]->getId());
        $c4 = $this->_em->find($entityClass, $this->countries[1]->getId());

        $this->assertEquals($queryCount, $this->getQueryCount());

        $this->assertInstanceOf($entityClass, $c3);
        $this->assertInstanceOf($entityClass, $c4);
        
        $this->assertEquals($c1->getId(), $c3->getId());
        $this->assertEquals($c1->getName(), $c3->getName());

        $this->assertEquals($c2->getId(), $c4->getId());
        $this->assertEquals($c2->getName(), $c4->getName());
    }

    public function testRemoveEntities()
    {
        $this->loadFixturesCountries();
        $this->_em->clear();

        $cache              = $this->_em->getCache();
        $entityClass        = 'Doctrine\Tests\Models\Cache\Country';

        $cache->evictEntityRegion($entityClass);

        $this->assertFalse($cache->containsEntity($entityClass, $this->countries[0]->getId()));
        $this->assertFalse($cache->containsEntity($entityClass, $this->countries[1]->getId()));

        $c1 = $this->_em->find($entityClass, $this->countries[0]->getId());
        $c2 = $this->_em->find($entityClass, $this->countries[1]->getId());

        $this->assertTrue($cache->containsEntity($entityClass, $this->countries[0]->getId()));
        $this->assertTrue($cache->containsEntity($entityClass, $this->countries[1]->getId()));

        $this->assertInstanceOf($entityClass, $c1);
        $this->assertInstanceOf($entityClass, $c2);

        $this->assertEquals($this->countries[0]->getId(), $c1->getId());
        $this->assertEquals($this->countries[0]->getName(), $c1->getName());

        $this->assertEquals($this->countries[1]->getId(), $c2->getId());
        $this->assertEquals($this->countries[1]->getName(), $c2->getName());

        $this->_em->remove($c1);
        $this->_em->remove($c2);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertFalse($cache->containsEntity($entityClass, $this->countries[0]->getId()));
        $this->assertFalse($cache->containsEntity($entityClass, $this->countries[1]->getId()));

        $this->assertNull($this->_em->find($entityClass, $this->countries[0]->getId()));
        $this->assertNull($this->_em->find($entityClass, $this->countries[1]->getId()));
    }

    public function testUpdateEntities()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        $cache              = $this->_em->getCache();
        $entityClass        = 'Doctrine\Tests\Models\Cache\State';

        $cache->evictEntityRegion($entityClass);

        $this->assertFalse($cache->containsEntity($entityClass, $this->states[0]->getId()));
        $this->assertFalse($cache->containsEntity($entityClass, $this->states[1]->getId()));

        $s1 = $this->_em->find($entityClass, $this->states[0]->getId());
        $s2 = $this->_em->find($entityClass, $this->states[1]->getId());

        $this->assertTrue($cache->containsEntity($entityClass, $this->states[0]->getId()));
        $this->assertTrue($cache->containsEntity($entityClass, $this->states[1]->getId()));

        $this->assertInstanceOf($entityClass, $s1);
        $this->assertInstanceOf($entityClass, $s2);

        $this->assertEquals($this->states[0]->getId(), $s1->getId());
        $this->assertEquals($this->states[0]->getName(), $s1->getName());

        $this->assertEquals($this->states[1]->getId(), $s2->getId());
        $this->assertEquals($this->states[1]->getName(), $s2->getName());

        $s1->setName("NEW NAME 1");
        $s2->setName("NEW NAME 2");

        $this->_em->persist($s1);
        $this->_em->persist($s2);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertTrue($cache->containsEntity($entityClass, $this->states[0]->getId()));
        $this->assertTrue($cache->containsEntity($entityClass, $this->states[1]->getId()));

        $queryCount = $this->getQueryCount();

        $c3 = $this->_em->find($entityClass, $this->states[0]->getId());
        $c4 = $this->_em->find($entityClass, $this->states[1]->getId());

        $this->assertEquals($queryCount, $this->getQueryCount());

        $this->assertTrue($cache->containsEntity($entityClass, $this->states[0]->getId()));
        $this->assertTrue($cache->containsEntity($entityClass, $this->states[1]->getId()));

        $this->assertInstanceOf($entityClass, $c3);
        $this->assertInstanceOf($entityClass, $c4);

        $this->assertEquals($s1->getId(), $c3->getId());
        $this->assertEquals("NEW NAME 1", $c3->getName());

        $this->assertEquals($s2->getId(), $c4->getId());
        $this->assertEquals("NEW NAME 2", $c4->getName());
    }

    public function testPutAndLoadManyToOneRelation()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->_em->clear();

        $cache              = $this->_em->getCache();
        $sourceClass        = 'Doctrine\Tests\Models\Cache\State';
        $targetClass        = 'Doctrine\Tests\Models\Cache\Country';

        $cache->evictEntityRegion($sourceClass);
        $cache->evictEntityRegion($targetClass);

        $this->assertFalse($cache->containsEntity($sourceClass, $this->states[0]->getId()));
        $this->assertFalse($cache->containsEntity($sourceClass, $this->states[1]->getId()));
        $this->assertFalse($cache->containsEntity($targetClass, $this->states[0]->getCountry()->getId()));
        $this->assertFalse($cache->containsEntity($targetClass, $this->states[1]->getCountry()->getId()));

        $c1 = $this->_em->find($sourceClass, $this->states[0]->getId());
        $c2 = $this->_em->find($sourceClass, $this->states[1]->getId());

        //trigger lazy load
        $this->assertNotNull($c1->getCountry()->getName());
        $this->assertNotNull($c2->getCountry()->getName());

        $this->assertTrue($cache->containsEntity($targetClass, $this->states[0]->getCountry()->getId()));
        $this->assertTrue($cache->containsEntity($targetClass, $this->states[1]->getCountry()->getId()));
        $this->assertTrue($cache->containsEntity($sourceClass, $this->states[0]->getId()));
        $this->assertTrue($cache->containsEntity($sourceClass, $this->states[1]->getId()));

        $this->assertInstanceOf($sourceClass, $c1);
        $this->assertInstanceOf($sourceClass, $c2);
        $this->assertInstanceOf($targetClass, $c1->getCountry());
        $this->assertInstanceOf($targetClass, $c2->getCountry());

        $this->assertEquals($this->states[0]->getId(), $c1->getId());
        $this->assertEquals($this->states[0]->getName(), $c1->getName());
        $this->assertEquals($this->states[0]->getCountry()->getId(), $c1->getCountry()->getId());
        $this->assertEquals($this->states[0]->getCountry()->getName(), $c1->getCountry()->getName());

        $this->assertEquals($this->states[1]->getId(), $c2->getId());
        $this->assertEquals($this->states[1]->getName(), $c2->getName());
        $this->assertEquals($this->states[1]->getCountry()->getId(), $c2->getCountry()->getId());
        $this->assertEquals($this->states[1]->getCountry()->getName(), $c2->getCountry()->getName());

        $this->_em->clear();

        $queryCount = $this->getQueryCount();

        $c3 = $this->_em->find($sourceClass, $this->states[0]->getId());
        $c4 = $this->_em->find($sourceClass, $this->states[1]->getId());

        $this->assertEquals($queryCount, $this->getQueryCount());

        //trigger lazy load from cache
        $this->assertNotNull($c3->getCountry()->getName());
        $this->assertNotNull($c4->getCountry()->getName());

        $this->assertInstanceOf($sourceClass, $c3);
        $this->assertInstanceOf($sourceClass, $c4);
        $this->assertInstanceOf($targetClass, $c3->getCountry());
        $this->assertInstanceOf($targetClass, $c4->getCountry());

        $this->assertEquals($c1->getId(), $c3->getId());
        $this->assertEquals($c1->getName(), $c3->getName());

        $this->assertEquals($c2->getId(), $c4->getId());
        $this->assertEquals($c2->getName(), $c4->getName());

        $this->assertEquals($this->states[0]->getCountry()->getId(), $c3->getCountry()->getId());
        $this->assertEquals($this->states[0]->getCountry()->getName(), $c3->getCountry()->getName());

        $this->assertEquals($this->states[1]->getCountry()->getId(), $c4->getCountry()->getId());
        $this->assertEquals($this->states[1]->getCountry()->getName(), $c4->getCountry()->getName());
    }

    public function testPutAndLoadOneToManyRelation()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->_em->clear();

        $cache       = $this->_em->getCache();
        $targetClass = 'Doctrine\Tests\Models\Cache\City';
        $sourceClass = 'Doctrine\Tests\Models\Cache\State';

        $cache->evictEntityRegion($sourceClass);
        $cache->evictEntityRegion($targetClass);
        $cache->evictCollectionRegion($sourceClass, 'cities');

        $this->assertFalse($cache->containsEntity($sourceClass, $this->states[0]->getId()));
        $this->assertFalse($cache->containsEntity($sourceClass, $this->states[1]->getId()));

        $this->assertFalse($cache->containsCollection($sourceClass, 'cities', $this->states[0]->getId()));
        $this->assertFalse($cache->containsCollection($sourceClass, 'cities', $this->states[1]->getId()));

        $this->assertFalse($cache->containsEntity($targetClass, $this->states[0]->getCities()->get(0)->getId()));
        $this->assertFalse($cache->containsEntity($targetClass, $this->states[0]->getCities()->get(1)->getId()));
        $this->assertFalse($cache->containsEntity($targetClass, $this->states[1]->getCities()->get(0)->getId()));
        $this->assertFalse($cache->containsEntity($targetClass, $this->states[1]->getCities()->get(1)->getId()));

        $c1 = $this->_em->find($sourceClass, $this->states[0]->getId());
        $c2 = $this->_em->find($sourceClass, $this->states[1]->getId());

        //trigger lazy load
        $this->assertCount(2, $c1->getCities());
        $this->assertCount(2, $c2->getCities());

        $this->assertInstanceOf($targetClass, $c1->getCities()->get(0));
        $this->assertInstanceOf($targetClass, $c1->getCities()->get(1));

        $this->assertInstanceOf($targetClass, $c2->getCities()->get(0));
        $this->assertInstanceOf($targetClass, $c2->getCities()->get(1));

        $this->assertTrue($cache->containsEntity($sourceClass, $this->states[0]->getId()));
        $this->assertTrue($cache->containsEntity($sourceClass, $this->states[1]->getId()));

        $this->assertTrue($cache->containsCollection($sourceClass, 'cities', $this->states[0]->getId()));
        $this->assertTrue($cache->containsCollection($sourceClass, 'cities', $this->states[1]->getId()));

        $this->assertTrue($cache->containsEntity($targetClass, $this->states[0]->getCities()->get(0)->getId()));
        $this->assertTrue($cache->containsEntity($targetClass, $this->states[0]->getCities()->get(1)->getId()));
        $this->assertTrue($cache->containsEntity($targetClass, $this->states[1]->getCities()->get(0)->getId()));
        $this->assertTrue($cache->containsEntity($targetClass, $this->states[1]->getCities()->get(1)->getId()));

        $this->_em->clear();

        $queryCount = $this->getQueryCount();

        $c3 = $this->_em->find($sourceClass, $this->states[0]->getId());
        $c4 = $this->_em->find($sourceClass, $this->states[1]->getId());

        //trigger lazy load from cache
        $this->assertCount(2, $c3->getCities());
        $this->assertCount(2, $c4->getCities());

        $this->assertInstanceOf($targetClass, $c3->getCities()->get(0));
        $this->assertInstanceOf($targetClass, $c3->getCities()->get(1));
        $this->assertInstanceOf($targetClass, $c4->getCities()->get(0));
        $this->assertInstanceOf($targetClass, $c4->getCities()->get(1));

        $this->assertEquals($c1->getCities()->get(0)->getId(), $c3->getCities()->get(0)->getId());
        $this->assertEquals($c1->getCities()->get(0)->getName(), $c3->getCities()->get(0)->getName());

        $this->assertEquals($c2->getCities()->get(1)->getId(), $c4->getCities()->get(1)->getId());
        $this->assertEquals($c2->getCities()->get(1)->getName(), $c4->getCities()->get(1)->getName());

        $this->assertEquals($queryCount, $this->getQueryCount());
    }
}

