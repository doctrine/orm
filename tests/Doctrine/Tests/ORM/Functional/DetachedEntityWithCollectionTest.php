<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Detach\Owner;
use Doctrine\Tests\OrmFunctionalTestCase;

class DetachedEntityWithCollectionTest extends OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('detach');
        parent::setUp();
    }

    public function testNotDetached() {
        $owner = new Owner(1, ['John Doe', 'Max Power']);

        $this->_em->persist($owner);
        $this->_em->flush();
        $this->_em->clear();

        $foundOwner = $this->_em->find(Owner::class, 1);

        $this->assertInstanceOf(Owner::class, $foundOwner);
        $this->assertEquals(['John Doe', 'Max Power'], $foundOwner->getMembers());

        $foundOwner->changeMembers(['John Connor']);
        $this->assertEquals(['John Connor'], $foundOwner->getMembers());

        $this->_em->flush();
        $this->_em->clear();

        $foundOwner = $this->_em->find(Owner::class, 1);
        $this->assertEquals(['John Connor'], $foundOwner->getMembers());
    }

    public function testDetached() {
        $owner = new Owner(2, ['John Doe', 'Max Power']);

        $this->_em->persist($owner);
        $this->_em->flush();
        $this->_em->clear();

        $foundOwner = $this->_em->find(Owner::class, 2);
        $this->_em->detach($foundOwner);

        $this->assertInstanceOf(Owner::class, $foundOwner);
        $this->assertEquals(['John Doe', 'Max Power'], $foundOwner->getMembers());

        $foundOwner->changeMembers(['John Connor']);
        $this->assertEquals(['John Connor'], $foundOwner->getMembers());

        $this->_em->merge($foundOwner);
        $this->_em->flush();
        $this->_em->clear();

        $foundOwner = $this->_em->find(Owner::class, 2);
        $this->assertEquals(['John Connor'], $foundOwner->getMembers());
    }
}

