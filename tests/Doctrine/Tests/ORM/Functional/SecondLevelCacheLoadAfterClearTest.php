<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\Models\Cache\Attraction;
use Doctrine\Tests\Models\Cache\Bar;
use Doctrine\Tests\Models\Cache\Beach;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\Restaurant;

class SecondLevelCacheLoadAfterClearTest extends SecondLevelCacheAbstractTest
{

    public function testAssociationLoadAfterClear()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();

        $cityId = $this->cities[0]->getId();

        $this->_em->clear();

        /** @var City $city */
        $city = $this->_em->find(City::class, $cityId);

        $this->_em->clear();

        $this->assertNotEmpty($city->getAttractions()->toArray());
    }

}
