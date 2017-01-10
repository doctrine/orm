<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\ORM\Functional\SecondLevelCacheAbstractTest;

class DDC3967Test extends SecondLevelCacheAbstractTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixturesCountries();
        $this->em->getCache()->evictEntityRegion(Country::class);
        $this->em->clear();
    }

    public function testIdentifierCachedWithProperType()
    {
        $country = array_pop($this->countries);
        $id = $country->getId();

        // First time, loaded from database
        $this->em->find(Country::class, "$id");
        $this->em->clear();

        // Second time, loaded from cache
        /** @var Country $country */
        $country = $this->em->find(Country::class, "$id");

        // Identifier type should be integer
        self::assertSame($country->getId(), $id);
    }
}
