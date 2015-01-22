<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\MixedToOneIdentity\CompositeToOneKeyState;
use Doctrine\Tests\Models\MixedToOneIdentity\Country;

class MergeCompositeToOneKeyTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(Country::CLASSNAME),
            $this->_em->getClassMetadata(CompositeToOneKeyState::CLASSNAME),
        ));
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

        $this->assertInstanceOf(CompositeToOneKeyState::CLASSNAME, $state);
        $this->assertNotSame($state, $merged);
        $this->assertInstanceOf(Country::CLASSNAME, $merged->country);
        $this->assertNotSame($country, $merged->country);
    }
}
