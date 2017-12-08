<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\MixedToOneIdentity\CompositeToOneKeyState;
use Doctrine\Tests\Models\MixedToOneIdentity\Country;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\VerifyDeprecations;

class MergeCompositeToOneKeyTest extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
            $this->_em->getClassMetadata(Country::class),
            $this->_em->getClassMetadata(CompositeToOneKeyState::class),
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
        $merged = $this->_em->merge($state);

        $this->assertInstanceOf(CompositeToOneKeyState::class, $state);
        $this->assertNotSame($state, $merged);
        $this->assertInstanceOf(Country::class, $merged->country);
        $this->assertNotSame($country, $merged->country);
        $this->assertHasDeprecationMessages();
    }
}
