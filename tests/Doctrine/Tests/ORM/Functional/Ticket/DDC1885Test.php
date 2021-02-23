<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Quote\Group as GroupQuote;
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

    protected function setUp() : void
    {
        $this->useModelSet('quote');

        parent::setUp();

        $user           = new User();
        $user->name     = 'FabioBatSilva';
        $user->email    = 'fabio.bat.silva@gmail.com';
        $user->groups[] = new GroupQuote('G 1');
        $user->groups[] = new GroupQuote('G 2');
        $this->user     = $user;

        // Create
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();
    }

    public function testCreateRetrieveUpdateDelete() : void
    {
        $user = $this->user;
        $g1   = $user->getGroups()->get(0);
        $g2   = $user->getGroups()->get(1);

        $u1Id = $user->id;
        $g1Id = $g1->id;
        $g2Id = $g2->id;

        // Retrieve
        $user = $this->em->find(User::class, $u1Id);

        self::assertInstanceOf(User::class, $user);
        self::assertEquals('FabioBatSilva', $user->name);
        self::assertEquals($u1Id, $user->id);

        self::assertCount(2, $user->groups);

        $g1 = $user->getGroups()->get(0);
        $g2 = $user->getGroups()->get(1);

        self::assertInstanceOf(GroupQuote::class, $g1);
        self::assertInstanceOf(GroupQuote::class, $g2);

        $g1->name = 'Bar 11';
        $g2->name = 'Foo 22';

        // Update
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $user = $this->em->find(User::class, $u1Id);

        self::assertInstanceOf(User::class, $user);
        self::assertEquals('FabioBatSilva', $user->name);
        self::assertEquals($u1Id, $user->id);

        // Delete
        $this->em->remove($user);

        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->find(User::class, $u1Id));
        self::assertNull($this->em->find(GroupQuote::class, $g1Id));
        self::assertNull($this->em->find(GroupQuote::class, $g2Id));
    }

    public function testRemoveItem() : void
    {
        $user = $this->user;
        $u1Id = $user->id;
        $user = $this->em->find(User::class, $u1Id);

        self::assertInstanceOf(User::class, $user);
        self::assertEquals('FabioBatSilva', $user->name);
        self::assertEquals($u1Id, $user->id);

        self::assertCount(2, $user->groups);
        self::assertInstanceOf(GroupQuote::class, $user->getGroups()->get(0));
        self::assertInstanceOf(GroupQuote::class, $user->getGroups()->get(1));

        $user->getGroups()->remove(0);

        // Update
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $user = $this->em->find(User::class, $u1Id);

        self::assertInstanceOf(User::class, $user);
        self::assertEquals('FabioBatSilva', $user->name);
        self::assertEquals($u1Id, $user->id);

        self::assertCount(1, $user->getGroups());
    }

    public function testClearAll() : void
    {
        $user = $this->user;
        $u1Id = $user->id;
        $user = $this->em->find(User::class, $u1Id);

        self::assertInstanceOf(User::class, $user);
        self::assertEquals('FabioBatSilva', $user->name);
        self::assertEquals($u1Id, $user->id);

        self::assertCount(2, $user->groups);
        self::assertInstanceOf(GroupQuote::class, $user->getGroups()->get(0));
        self::assertInstanceOf(GroupQuote::class, $user->getGroups()->get(1));

        $user->getGroups()->clear();

        // Update
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $user = $this->em->find(User::class, $u1Id);

        self::assertInstanceOf(User::class, $user);
        self::assertEquals('FabioBatSilva', $user->name);
        self::assertEquals($u1Id, $user->id);

        self::assertCount(0, $user->getGroups());
    }

    public function testCountExtraLazy() : void
    {
        $user = $this->user;
        $u1Id = $user->id;
        $user = $this->em->find(User::class, $u1Id);

        self::assertInstanceOf(User::class, $user);
        self::assertEquals('FabioBatSilva', $user->name);
        self::assertEquals($u1Id, $user->id);

        self::assertCount(2, $user->groups);
        self::assertInstanceOf(GroupQuote::class, $user->getGroups()->get(0));
        self::assertInstanceOf(GroupQuote::class, $user->getGroups()->get(1));
    }
}
