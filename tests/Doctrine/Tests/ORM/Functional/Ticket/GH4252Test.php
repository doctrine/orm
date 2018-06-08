<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Tests\OrmFunctionalTestCase;

use function assert;

/**
 * @group GH-4252
 */
class GH4252Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(GH4252City::class),
            $this->_em->getClassMetadata(GH4252Resident::class),
            $this->_em->getClassMetadata(GH4252Address::class),
        ]);
    }

    public function testIssue(): void
    {
        $city = new GH4252City([new GH4252Resident([new GH4252Address()])]);

        $this->_em->persist($city);
        $this->_em->flush();
        $this->_em->clear();

        $city = $this->_em->find(GH4252City::class, $city->getId());
        assert($city instanceof GH4252City);
        $city->setFlag(false);
        $resident = $city->getResidents()->first();
        assert($resident instanceof GH4252Resident);
        $resident->setFlag(false);
        $address = $resident->getAddresses()->first();
        assert($address instanceof GH4252Address);
        $address->setFlag(false);

        $this->_em->refresh($city);

        $resident = $city->getResidents()->first();
        $address  = $resident->getAddresses()->first();

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
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    private $flag;

    /**
     * @var GH4252Resident[]|Collection
     * @OneToMany(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\GH4252Resident", mappedBy="city", cascade={"persist","refresh"})
     */
    private $residents;

    public function __construct(array $residents)
    {
        $this->residents = new ArrayCollection();
        foreach ($residents as $resident) {
            $this->residents->add($resident);
            $resident->setCity($this);
        }

        $this->flag = true;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFlag(): bool
    {
        return $this->flag;
    }

    public function setFlag(bool $flag): void
    {
        $this->flag = $flag;
    }

    public function getResidents(): Collection
    {
        return $this->residents;
    }
}

/**
 * @Entity
 */
class GH4252Resident
{
    /**
     * @var int
     * @Id @Column(type="integer") @GeneratedValue
     */
    private $id;

    /**
     * @var GH4252City
     * @ManyToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\GH4252City", inversedBy="residents")
     */
    private $city;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    private $flag;

    /**
     * @var GH4252Address[]|Collection
     * @ManyToMany(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\GH4252Address", fetch="EXTRA_LAZY", cascade={"persist","refresh"})
     */
    private $addresses;

    public function __construct(array $addresses)
    {
        $this->addresses = new ArrayCollection();
        foreach ($addresses as $address) {
            $this->addresses->add($address);
        }

        $this->flag = true;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCity(): GH4252City
    {
        return $this->city;
    }

    public function setCity(GH4252City $city): void
    {
        $this->city = $city;
    }

    public function getFlag(): bool
    {
        return $this->flag;
    }

    public function setFlag(bool $flag): void
    {
        $this->flag = $flag;
    }

    public function getAddresses(): Collection
    {
        return $this->addresses;
    }
}

/** @Entity */
class GH4252Address
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    private $flag;

    public function __construct()
    {
        $this->flag = true;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFlag(): bool
    {
        return $this->flag;
    }

    public function setFlag(bool $flag): void
    {
        $this->flag = $flag;
    }
}
