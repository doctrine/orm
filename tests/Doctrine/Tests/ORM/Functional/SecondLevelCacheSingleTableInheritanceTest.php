<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\Attraction;
use Doctrine\Tests\Models\Cache\Restaurant;
use Doctrine\Tests\Models\Cache\Beach;
use Doctrine\Tests\Models\Cache\Bar;

/**
 * @group DDC-2183
 */
class SecondLevelCacheSingleTableInheritanceTest extends SecondLevelCacheAbstractTest
{
    public function testUseSameRegion()
    {
        $attractionRegion   = $this->cache->getEntityCacheRegionAccess(Attraction::CLASSNAME)->getRegion();
        $restaurantRegion   = $this->cache->getEntityCacheRegionAccess(Restaurant::CLASSNAME)->getRegion();
        $beachRegion        = $this->cache->getEntityCacheRegionAccess(Beach::CLASSNAME)->getRegion();
        $barRegion          = $this->cache->getEntityCacheRegionAccess(Bar::CLASSNAME)->getRegion();

        $this->assertEquals($attractionRegion->getName(), $restaurantRegion->getName());
        $this->assertEquals($attractionRegion->getName(), $beachRegion->getName());
        $this->assertEquals($attractionRegion->getName(), $barRegion->getName());
    }

    public function testCountaisRootClass()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();

        $this->_em->clear();

        foreach ($this->attractions as $attraction) {
            $this->assertTrue($this->cache->containsEntity(Attraction::CLASSNAME, $attraction->getId()));
            $this->assertTrue($this->cache->containsEntity(get_class($attraction), $attraction->getId()));
        }
    }
}