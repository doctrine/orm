<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group #6613
 * @group #6614
 */
class DDC6613Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setUpEntitySchema([
            DDC6613InverseSide::class,
            DDC6613OwningSide::class,
        ]);
    }

    public function testFail()
    {
        $owningSide = new DDC6613OwningSide();

        $this->_em->persist($owningSide);
        $this->_em->flush();
        $this->_em->clear();

        $item1 = new DDC6613InverseSide();
        $item2 = new DDC6613InverseSide();

        $this->_em->persist($item1);
        $this->_em->persist($item2);
        $this->_em->flush();

        /* @var DDC6613OwningSide $foundOwningSide */
        $foundOwningSide = $this->_em->find(DDC6613OwningSide::class, $owningSide->id);

        self::assertInstanceOf(DDC6613OwningSide::class, $foundOwningSide);

        /* @var $phones PersistentCollection */
        $phones = $foundOwningSide->phones;

        self::assertInstanceOf(PersistentCollection::class, $phones);
        self::assertFalse($phones->isInitialized());

        $phones->add($item1);
        $this->_em->flush();

        $phones->add($item2);

        $phones->initialize();

        self::assertTrue($phones->isInitialized());
        self::assertCount(2, $phones);

        $this->_em->flush();

        self::assertFalse($phones->isDirty());
        self::assertTrue($phones->isInitialized());
        self::assertCount(2, $foundOwningSide->phones);
    }
}

/** @Entity */
class DDC6613OwningSide
{
    /** @Id @Column(type="string") */
    public $id;

    /** @ManyToMany(targetEntity=DDC6613InverseSide::class) */
    public $phones;

    public function __construct()
    {
        $this->id     = uniqid('user', true);
        $this->phones = new ArrayCollection();
    }
}

/** @Entity */
class DDC6613InverseSide
{
    /** @Id @Column(type="string") */
    private $id;

    public function __construct()
    {
        $this->id = uniqid('phone', true);
    }
}