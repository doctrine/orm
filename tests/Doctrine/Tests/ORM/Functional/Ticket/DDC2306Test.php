<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @group DDC-2306
 */
class DDC2306Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setup()
    {
        parent::setup();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2306Zone'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2306User'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2306Address'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2306UserAddress'),
        ));
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
    public function testIssue()
    {
        $zone          = new DDC2306Zone();
        $user          = new DDC2306User;
        $address       = new DDC2306Address;
        $userAddress   = new DDC2306UserAddress($user, $address);
        $user->zone    = $zone;
        $address->zone = $zone;

        $this->_em->persist($zone);
        $this->_em->persist($user);
        $this->_em->persist($address);
        $this->_em->persist($userAddress);
        $this->_em->flush();
        $this->_em->clear();

        /* @var $address DDC2306Address */
        $address = $this->_em->find(__NAMESPACE__ . '\\DDC2306Address', $address->id);
        /* @var $user DDC2306User|\Doctrine\ORM\Proxy\Proxy */
        $user    = $address->users->first()->user;

        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $user);
        $this->assertInstanceOf(__NAMESPACE__ . '\\DDC2306User', $user);

        $userId = $user->id;

        $this->assertNotNull($userId);

        $user->__load();

        $this->assertEquals(
            $userId,
            $user->id,
            'As of DDC-1734, the identifier is NULL for un-managed proxies. The identifier should be an integer here'
        );
    }
}

/** @Entity */
class DDC2306Zone
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

/**
 * @Entity
 */
class DDC2306User
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @var DDC2306UserAddress[]|\Doctrine\Common\Collections\Collection
     *
     * @OneToMany(targetEntity="DDC2306UserAddress", mappedBy="user")
     */
    public $addresses;

    /** @ManyToOne(targetEntity="DDC2306Zone", fetch="EAGER") */
    public $zone;

    /** Constructor */
    public function __construct() {
        $this->addresses = new ArrayCollection();
    }
}

/** @Entity */
class DDC2306Address
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @var DDC2306UserAddress[]|\Doctrine\Common\Collections\Collection
     *
     * @OneToMany(targetEntity="DDC2306UserAddress", mappedBy="address", orphanRemoval=true)
     */
    public $users;

    /** @ManyToOne(targetEntity="DDC2306Zone", fetch="EAGER") */
    public $zone;

    /** Constructor */
    public function __construct() {
        $this->users = new ArrayCollection();
    }
}

/** @Entity */
class DDC2306UserAddress
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @ManyToOne(targetEntity="DDC2306User") */
    public $user;

    /** @ManyToOne(targetEntity="DDC2306Address", fetch="LAZY") */
    public $address;

    /** Constructor */
    public function __construct(DDC2306User $user, DDC2306Address $address)
    {
        $this->user    = $user;
        $this->address = $address;

        $user->addresses->add($this);
        $address->users->add($this);
    }
}