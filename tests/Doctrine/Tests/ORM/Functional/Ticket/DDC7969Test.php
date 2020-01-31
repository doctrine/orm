<?php

namespace tests\Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Cache\Attraction;
use Doctrine\Tests\Models\Cache\Bar;
use Doctrine\Tests\ORM\Functional\SecondLevelCacheAbstractTest;

class DDC7969Test extends SecondLevelCacheAbstractTest
{
    public function testChildEntityRetrievedFromCache()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();

        // Entities are already cached due to fixtures - hence flush before testing
        $this->cache->getEntityCacheRegion(Attraction::class)->getCache()->flushAll();

        /** @var Bar $bar */
        $bar = $this->attractions[0];

        $repository = $this->_em->getRepository(Bar::class);

        $this->assertFalse($this->cache->containsEntity(Bar::class, $bar->getId()));

        $repository->findOneBy([
            'name' => $bar->getName(),
        ]);

        $this->assertTrue($this->cache->containsEntity(Bar::class, $bar->getId()));

        $repository->findOneBy([
            'name' => $bar->getName(),
        ]);

        // One hit for entity cache, one hit for query cache
        $this->assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
    }
}
