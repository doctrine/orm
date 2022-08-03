<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Quote\Group;
use Doctrine\Tests\Models\Quote\User;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1845
 * @group DDC-1885
 */
class DDC1885Test extends OrmFunctionalTestCase
{
    /** @var User */
    private $user;

    protected function setUp(): void
    {
        $this->useModelSet('quote');

        parent::setUp();

        $user           = new User();
        $user->name     = 'FabioBatSilva';
        $user->email    = 'fabio.bat.silva@gmail.com';
        $user->groups[] = new Group('G 1');
        $user->groups[] = new Group('G 2');
        $this->user     = $user;

        // Create
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testCreateRetrieveUpdateDelete(): void
    {
        $user = $this->user;
        $g1   = $user->getGroups()->get(0);
        $g2   = $user->getGroups()->get(1);

        $u1Id = $user->id;
        $g1Id = $g1->id;
        $g2Id = $g2->id;

        // Retrieve
        $user = $this->_em->find(User::class, $u1Id);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals($u1Id, $user->id);

        $this->assertCount(2, $user->groups);

        $g1 = $user->getGroups()->get(0);
        $g2 = $user->getGroups()->get(1);

        $this->assertInstanceOf(Group::class, $g1);
        $this->assertInstanceOf(Group::class, $g2);

        $g1->name = 'Bar 11';
        $g2->name = 'Foo 22';

        // Update
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(User::class, $u1Id);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals($u1Id, $user->id);

        // Delete
        $this->_em->remove($user);

        $this->_em->flush();
        $this->_em->clear();

        $this->assertNull($this->_em->find(User::class, $u1Id));
        $this->assertNull($this->_em->find(Group::class, $g1Id));
        $this->assertNull($this->_em->find(Group::class, $g2Id));
    }

    public function testRemoveItem(): void
    {
        $user = $this->user;
        $u1Id = $user->id;
        $user = $this->_em->find(User::class, $u1Id);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals($u1Id, $user->id);

        $this->assertCount(2, $user->groups);
        $this->assertInstanceOf(Group::class, $user->getGroups()->get(0));
        $this->assertInstanceOf(Group::class, $user->getGroups()->get(1));

        $user->getGroups()->remove(0);

        // Update
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(User::class, $u1Id);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals($u1Id, $user->id);

        $this->assertCount(1, $user->getGroups());
    }

    public function testClearAll(): void
    {
        $user = $this->user;
        $u1Id = $user->id;
        $user = $this->_em->find(User::class, $u1Id);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals($u1Id, $user->id);

        $this->assertCount(2, $user->groups);
        $this->assertInstanceOf(Group::class, $user->getGroups()->get(0));
        $this->assertInstanceOf(Group::class, $user->getGroups()->get(1));

        $user->getGroups()->clear();

        // Update
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(User::class, $u1Id);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals($u1Id, $user->id);

        $this->assertCount(0, $user->getGroups());
    }

    public function testCountExtraLazy(): void
    {
        $user = $this->user;
        $u1Id = $user->id;
        $user = $this->_em->find(User::class, $u1Id);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('FabioBatSilva', $user->name);
        $this->assertEquals($u1Id, $user->id);

        $this->assertCount(2, $user->groups);
        $this->assertInstanceOf(Group::class, $user->getGroups()->get(0));
        $this->assertInstanceOf(Group::class, $user->getGroups()->get(1));
    }
}
