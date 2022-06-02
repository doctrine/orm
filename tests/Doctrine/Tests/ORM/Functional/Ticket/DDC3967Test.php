<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\ORM\Functional\SecondLevelCacheFunctionalTestCase;

use function array_pop;
use function assert;

class DDC3967Test extends SecondLevelCacheFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixturesCountries();
        $this->_em->getCache()->evictEntityRegion(Country::class);
        $this->_em->clear();
    }

    public function testIdentifierCachedWithProperType(): void
    {
        $country = array_pop($this->countries);
        $id      = $country->getId();

        // First time, loaded from database
        $this->_em->find(Country::class, (string) $id);
        $this->_em->clear();

        $country = $this->_em->find(Country::class, (string) $id);
        assert($country instanceof Country);

        // Identifier type should be integer
        self::assertSame($country->getId(), $id);
    }
}
