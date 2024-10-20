<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function assert;

#[Group('DDC-2306')]
class DDC2306Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC2306Zone::class,
            DDC2306User::class,
            DDC2306Address::class,
            DDC2306UserAddress::class,
        );
    }

    /**
     * Verifies that when eager loading is triggered, proxies are kept managed.
     *
     * The problem resides in the refresh hint passed to {@see \Doctrine\ORM\UnitOfWork::createEntity},
     * which, as of DDC-1734, causes the proxy to be marked as un-managed.
     * The check against the identity map only uses the identifier hash and the passed in class name, and
     * does not take into account the fact that the set refresh hint may be for an entity of a different
     * type from the one passed to {@see \Doctrine\ORM\UnitOfWork::createEntity}
     *
     * As a result, a refresh requested for an entity `Foo` with identifier `123` may cause a proxy
     * of type `Bar` with identifier `123` to be marked as un-managed.
     */
    public function testIssue(): void
    {
        $zone          = new DDC2306Zone();
        $user          = new DDC2306User();
        $address       = new DDC2306Address();
        $userAddress   = new DDC2306UserAddress($user, $address);
        $user->zone    = $zone;
        $address->zone = $zone;

        $this->_em->persist($zone);
        $this->_em->persist($user);
        $this->_em->persist($address);
        $this->_em->persist($userAddress);
        $this->_em->flush();
        $this->_em->clear();

        $address = $this->_em->find(DDC2306Address::class, $address->id);
        assert($address instanceof DDC2306Address);
        $user = $address->users->first()->user;

        $this->assertTrue($this->isUninitializedObject($user));
        self::assertInstanceOf(DDC2306User::class, $user);

        $userId = $user->id;

        self::assertNotNull($userId);

        $this->_em->getUnitOfWork()->initializeObject($user);

        self::assertEquals(
            $userId,
            $user->id,
            'As of DDC-1734, the identifier is NULL for un-managed proxies. The identifier should be an integer here',
        );
    }
}

#[Entity]
class DDC2306Zone
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;
}

#[Entity]
class DDC2306User
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var DDC2306UserAddress[]|Collection */
    #[OneToMany(targetEntity: 'DDC2306UserAddress', mappedBy: 'user')]
    public $addresses;

    /** @var DDC2306Zone */
    #[ManyToOne(targetEntity: 'DDC2306Zone', fetch: 'EAGER')]
    public $zone;

    /** Constructor */
    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }
}

#[Entity]
class DDC2306Address
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var DDC2306UserAddress[]|Collection */
    #[OneToMany(targetEntity: 'DDC2306UserAddress', mappedBy: 'address', orphanRemoval: true)]
    public $users;

    /** @var DDC2306Zone */
    #[ManyToOne(targetEntity: 'DDC2306Zone', fetch: 'EAGER')]
    public $zone;

    /** Constructor */
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }
}

#[Entity]
class DDC2306UserAddress
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** Constructor */
    public function __construct(
        #[ManyToOne(targetEntity: 'DDC2306User')]
        public DDC2306User $user,
        #[ManyToOne(targetEntity: 'DDC2306Address', fetch: 'LAZY')]
        public DDC2306Address $address,
    ) {
        $user->addresses->add($this);
        $address->users->add($this);
    }
}
