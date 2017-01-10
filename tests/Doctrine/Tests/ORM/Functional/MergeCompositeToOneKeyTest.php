<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\MixedToOneIdentity\CompositeToOneKeyState;
use Doctrine\Tests\Models\MixedToOneIdentity\Country;
use Doctrine\Tests\OrmFunctionalTestCase;

class MergeCompositeToOneKeyTest extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(Country::class),
            $this->em->getClassMetadata(CompositeToOneKeyState::class),
            ]
        );
    }

    /**
     * @group DDC-3378
     * @group 1176
     */
    public function testMergingOfEntityWithCompositeIdentifierContainingToOneAssociation()
    {
        $country = new Country();
        $country->country = 'US';

        $state = new CompositeToOneKeyState();
        $state->state   = 'CA';
        $state->country = $country;

        /* @var $merged CompositeToOneKeyState */
        $merged = $this->em->merge($state);

        self::assertInstanceOf(CompositeToOneKeyState::class, $state);
        self::assertNotSame($state, $merged);
        self::assertInstanceOf(Country::class, $merged->country);
        self::assertNotSame($country, $merged->country);
    }
}
