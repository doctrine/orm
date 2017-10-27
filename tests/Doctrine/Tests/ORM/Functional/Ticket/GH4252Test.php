<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @group GH-4252
 */
class GH4252Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setup()
    {
        parent::setup();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(GH4252City::class),
            $this->_em->getClassMetadata(GH4252Resident::class),
            $this->_em->getClassMetadata(GH4252Address::class),
        ));
    }

    public function testIssue()
    {
        $city = new GH4252City([new GH4252Resident([new GH4252Address()])]);

        $this->_em->persist($city);
        $this->_em->flush();
        $this->_em->clear();

        /** @var GH4252City $city */
        $city = $this->_em->find(GH4252City::class, $city->getId());
        $city->setFlag(false);
        /** @var GH4252Resident $resident */
        $resident = $city->getResidents()->first();
        $resident->setFlag(false);
        /** @var GH4252Address $address */
        $address = $resident->getAddresses()->first();
        $address->setFlag(false);

        $this->_em->refresh($city);

        $resident = $city->getResidents()->first();
        $address = $resident->getAddresses()->first();

        $this->assertTrue($city->getFlag());
        $this->assertTrue($resident->getFlag());
        $this->assertTrue($address->getFlag());
    }
}

/**
 * @Entity
 */
class GH4252City
{
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    private $flag;

    /**
     * @var GH4252Resident[]|\Doctrine\Common\Collections\Collection
     *
     * @OneToMany(targetEntity="GH4252Resident", mappedBy="city", cascade={"persist","refresh"})
     */
    private $residents;

    /** Constructor */
    public function __construct(array $residents)
    {
        $this->residents = new ArrayCollection();
        foreach ($residents as $resident) {
            $this->residents->add($resident);
            $resident->setCity($this);
        }
        $this->flag = true;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFlag()
    {
        return $this->flag;
    }

    public function setFlag($flag)
    {
        $this->flag = $flag;
    }

    public function getResidents()
    {
        return $this->residents;
    }
}

/**
 * @Entity
 */
class GH4252Resident
{
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;

    /**
     * @var GH4252City
     * @ManyToOne(targetEntity="GH4252City", inversedBy="residents")
     */
    private $city;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    private $flag;

    /**
     * @var GH4252Address[]|\Doctrine\Common\Collections\Collection
     *
     * @ManyToMany(targetEntity="GH4252Address", fetch="EXTRA_LAZY", cascade={"persist","refresh"})
     */
    private $addresses;

    /** Constructor */
    public function __construct(array $addresses)
    {
        $this->addresses = new ArrayCollection();
        foreach ($addresses as $address) {
            $this->addresses->add($address);
        }
        $this->flag = true;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setCity(GH4252City $city)
    {
        $this->city = $city;
    }

    public function getFlag()
    {
        return $this->flag;
    }

    public function setFlag($flag)
    {
        $this->flag = $flag;
    }

    public function getAddresses()
    {
        return $this->addresses;
    }
}

/** @Entity */
class GH4252Address
{
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    private $flag;

    /** Constructor */
    public function __construct()
    {
        $this->flag = true;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFlag()
    {
        return $this->flag;
    }

    public function setFlag($flag)
    {
        $this->flag = $flag;
    }
}
