<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\ORM\Functional\SecondLevelCacheAbstractTest;
use function array_pop;
use function sprintf;

class DDC3967Test extends SecondLevelCacheAbstractTest
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->loadFixturesCountries();
        $this->em->getCache()->evictEntityRegion(Country::class);
        $this->em->clear();
    }

    public function testIdentifierCachedWithProperType() : void
    {
        $country = \array_pop($this->countries);
        $id      = $country->getId();

        // First time, loaded from database
        $this->em->find(Country::class, \sprintf('%d', $id));
        $this->em->clear();

        // Second time, loaded from cache
        /** @var Country $country */
        $country = $this->em->find(Country::class, \sprintf('%d', $id));

        // Identifier type should be integer
        self::assertSame($country->getId(), $id);
    }
}
