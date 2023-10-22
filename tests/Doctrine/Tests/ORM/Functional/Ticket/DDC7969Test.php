<?php

declare(strict_types=1);

namespace tests\Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Cache\Attraction;
use Doctrine\Tests\Models\Cache\Bar;
use Doctrine\Tests\ORM\Functional\SecondLevelCacheFunctionalTestCase;

use function assert;

class DDC7969Test extends SecondLevelCacheFunctionalTestCase
{
    public function testChildEntityRetrievedFromCache(): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesAttractions();

        // Entities are already cached due to fixtures - hence flush before testing
        $this->cache->getEntityCacheRegion(Attraction::class)->evictAll();

        $bar = $this->attractions[0];
        assert($bar instanceof Bar);

        $repository = $this->_em->getRepository(Bar::class);

        self::assertFalse($this->cache->containsEntity(Bar::class, $bar->getId()));
        self::assertFalse($this->cache->containsEntity(Attraction::class, $bar->getId()));

        $repository->findOneBy([
            'name' => $bar->getName(),
        ]);

        self::assertTrue($this->cache->containsEntity(Bar::class, $bar->getId()));

        $repository->findOneBy([
            'name' => $bar->getName(),
        ]);

        // One hit for entity cache, one hit for query cache
        self::assertEquals(2, $this->secondLevelCacheLogger->getHitCount());
    }
}
