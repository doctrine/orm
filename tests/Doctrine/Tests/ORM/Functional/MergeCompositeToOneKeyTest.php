<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\MixedToOneIdentity\CompositeToOneKeyState;
use Doctrine\Tests\Models\MixedToOneIdentity\Country;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\VerifyDeprecations;

use function assert;

class MergeCompositeToOneKeyTest extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

    protected function setUp(): void
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
    public function testMergingOfEntityWithCompositeIdentifierContainingToOneAssociation(): void
    {
        $country          = new Country();
        $country->country = 'US';

        $state          = new CompositeToOneKeyState();
        $state->state   = 'CA';
        $state->country = $country;

        $merged = $this->_em->merge($state);
        assert($merged instanceof CompositeToOneKeyState);

        $this->assertInstanceOf(CompositeToOneKeyState::class, $state);
        $this->assertNotSame($state, $merged);
        $this->assertInstanceOf(Country::class, $merged->country);
        $this->assertNotSame($country, $merged->country);
        $this->assertHasDeprecationMessages();
    }
}
