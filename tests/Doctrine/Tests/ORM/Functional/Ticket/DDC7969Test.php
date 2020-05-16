<?php

declare(strict_types=1);

namespace tests\Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Cache\Region\DefaultMultiGetRegion;
use Doctrine\Tests\Models\Cache\Attraction;
use Doctrine\Tests\Models\Cache\Bar;
use Doctrine\Tests\ORM\Functional\SecondLevelCacheAbstractTest;

class DDC7969Test extends SecondLevelCacheAbstractTest
{
    public function testChildEntityRetrievedFromCache() : void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();

        // Entities are already cached due to fixtures - hence flush before testing
        $region = $this->cache->getEntityCacheRegion(Attraction::class);

        if ($region instanceof DefaultMultiGetRegion) {
            $region->getCache()->flushAll();
        }

        /** @var Bar $bar */
        $bar = $this->attractions[0];

        $repository = $this->_em->getRepository(Bar::class);

        $this->assertFalse($this->cache->containsEntity(Bar::class, $bar->getId()));
        $this->assertFalse($this->cache->containsEntity(Attraction::class, $bar->getId()));

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
