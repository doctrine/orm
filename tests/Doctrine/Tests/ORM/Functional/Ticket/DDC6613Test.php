<?php
/**
 *
 * User: Uladzimir Struts <Sysaninster@gmail.com>
 * Date: 11.08.2017
 * Time: 12:28
 */

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Schema\SchemaException;
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

        try {
            $this->setUpEntitySchema(
                [
                    DDC6613Phone::class,
                    DDC6613User::class,
                ]
            );
        } catch (SchemaException $e) {
        }
    }

    public function testFail()
    {
        $newUser = new DDC6613User();
        $this->_em->persist($newUser);
        $this->_em->flush();

        $this->_em->clear();

        /** @var DDC6613User $user */
        $user   = $this->_em->find(DDC6613User::class, $newUser->id);

        self::assertInstanceOf(DDC6613User::class, $user);

        $phone1 = new DDC6613Phone();
        $phones = $user->phones;
        $user->phones->add($phone1);
        $this->_em->persist($phone1);
        $this->_em->flush();

        $phone2 = new DDC6613Phone();
        $user->phones->add($phone2);
        $this->_em->persist($phone2);

        /* @var $phones PersistentCollection */
//        $phones = $user->phones;

        self::assertInstanceOf(PersistentCollection::class, $phones);
        self::assertFalse($phones->isInitialized());

        $phones->initialize();

        self::assertTrue($phones->isInitialized());
        self::assertCount(2, $phones);

        $this->_em->flush();

        self::assertFalse($phones->isDirty());
        self::assertTrue($phones->isInitialized());
        self::assertCount(2, $user->phones);
    }
}

/** @Entity */
class DDC6613User
{
    /** @Id @Column(type="string") */
    public $id;

    /** @ManyToMany(targetEntity=DDC6613Phone::class) */
    public $phones;

    public function __construct()
    {
        $this->id     = uniqid('user', true);
        $this->phones = new ArrayCollection();
    }
}

/** @Entity */
class DDC6613Phone
{
    /** @Id @Column(type="integer") */
    private $id;

    public function __construct()
    {
        $this->id = uniqid('phone', true);
    }
}