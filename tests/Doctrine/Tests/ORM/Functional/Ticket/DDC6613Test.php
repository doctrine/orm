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
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
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
        $user = new DDC6613User();
        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->clear();

        /** @var User $user */
        $user   = $this->_em->find(User::class, 1);
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

/**
 * @Entity
 * @Table(name="ddc6613_user")
 */
class DDC6613User
{
    /**
     * @Id
     * @GeneratedValue(strategy="NONE")
     * @Column(type="string")
     */
    private $id;

    /**
     * @ManyToMany(targetEntity="Phone", fetch="LAZY", cascade={"remove", "detach"})
     */
    public $phones;

    public function __construct()
    {
        $this->id     = uniqid('user', true);
        $this->phones = new ArrayCollection();
    }
}

/**
 * @Table(name="ddc6613_phone")
 */
class DDC6613Phone
{
    /**
     * @Id
     * @GeneratedValue(strategy="NONE")
     * @Column(type="integer")
     */
    public $id;

    public function __construct()
    {
        $this->id = uniqid('phone', true);
    }
}