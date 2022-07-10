<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\MixedToOneIdentity\CompositeToOneKeyState;
use Doctrine\Tests\Models\MixedToOneIdentity\Country;
use Doctrine\Tests\OrmFunctionalTestCase;

use function assert;

class MergeCompositeToOneKeyTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            Country::class,
            CompositeToOneKeyState::class
        );
    }

    /**
     * @group DDC-3378
     * @group 1176
     */
    public function testMergingOfEntityWithCompositeIdentifierContainingToOneAssociation(): void
    {
        $country          = new Country();
        $country->country = 'US';

        $state          = new CompositeToOneKeyState();
        $state->state   = 'CA';
        $state->country = $country;

        $merged = $this->_em->merge($state);
        assert($merged instanceof CompositeToOneKeyState);

        self::assertInstanceOf(CompositeToOneKeyState::class, $state);
        self::assertNotSame($state, $merged);
        self::assertInstanceOf(Country::class, $merged->country);
        self::assertNotSame($country, $merged->country);
    }
}
