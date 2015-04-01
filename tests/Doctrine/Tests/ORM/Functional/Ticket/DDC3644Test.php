<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Functional tests for orphan removal with one to many association.
 */
class DDC3644Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->setUpEntitySchema(array(
            'Doctrine\Tests\ORM\Functional\Ticket\DDC3644User',
            'Doctrine\Tests\ORM\Functional\Ticket\DDC3644Address',
            'Doctrine\Tests\ORM\Functional\Ticket\DDC3644Animal',
            'Doctrine\Tests\ORM\Functional\Ticket\DDC3644Pet',
        ));
    }

    /**
     * @group DDC-3644
     */
    public function testIssueWithRegularEntity()
    {
        // Define initial dataset
        $current   = new DDC3644Address('Sao Paulo, SP, Brazil');
        $previous  = new DDC3644Address('Rio de Janeiro, RJ, Brazil');
        $initial   = new DDC3644Address('Sao Carlos, SP, Brazil');
        $addresses = new ArrayCollection(array($current, $previous, $initial));
        $user      = new DDC3644User();

        $user->name = 'Guilherme Blanco';
        $user->setAddresses($addresses);

        $this->_em->persist($user);
        $this->_em->persist($current);
        $this->_em->persist($previous);
        $this->_em->persist($initial);

        $this->_em->flush();

        $userId = $user->id;
        unset($current, $previous, $initial, $addresses, $user);

        $this->_em->clear();

        // Replace entire collection (this should trigger OneToManyPersister::remove())
        $current   = new DDC3644Address('Toronto, ON, Canada');
        $addresses = new ArrayCollection(array($current));
        $user      = $this->_em->find(__NAMESPACE__ . '\DDC3644User', $userId);

        $user->setAddresses($addresses);

        $this->_em->persist($user);
        $this->_em->persist($current);

        $this->_em->flush();
        $this->_em->clear();

        // We should only have 1 item in the collection list now
        $user = $this->_em->find(__NAMESPACE__ . '\DDC3644User', $userId);

        $this->assertCount(1, $user->addresses);

        // We should only have 1 item in the addresses table too
        $repository = $this->_em->getRepository(__NAMESPACE__ . '\DDC3644Address');
        $addresses  = $repository->findAll();

        $this->assertCount(1, $addresses);
    }

    /**
     * @group DDC-3644
     */
    public function testIssueWithJoinedEntity()
    {
        // Define initial dataset
        $actual = new DDC3644Pet('Catharina');
        $past   = new DDC3644Pet('Nanny');
        $pets   = new ArrayCollection(array($actual, $past));
        $user   = new DDC3644User();

        $user->name = 'Guilherme Blanco';
        $user->setPets($pets);

        $this->_em->persist($user);
        $this->_em->persist($actual);
        $this->_em->persist($past);

        $this->_em->flush();

        $userId = $user->id;
        unset($actual, $past, $pets, $user);

        $this->_em->clear();

        // Replace entire collection (this should trigger OneToManyPersister::remove())
        $actual = new DDC3644Pet('Valentina');
        $pets   = new ArrayCollection(array($actual));
        $user   = $this->_em->find(__NAMESPACE__ . '\DDC3644User', $userId);

        $user->setPets($pets);

        $this->_em->persist($user);
        $this->_em->persist($actual);

        $this->_em->flush();
        $this->_em->clear();

        // We should only have 1 item in the collection list now
        $user = $this->_em->find(__NAMESPACE__ . '\DDC3644User', $userId);

        $this->assertCount(1, $user->pets);

        // We should only have 1 item in the pets table too
        $repository = $this->_em->getRepository(__NAMESPACE__ . '\DDC3644Pet');
        $pets       = $repository->findAll();

        $this->assertCount(1, $pets);
    }
}

/**
 * @Entity
 */
class DDC3644User
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer", name="hash_id")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $name;

    /**
     * @OneToMany(targetEntity="DDC3644Address", mappedBy="user", orphanRemoval=true)
     */
    public $addresses = [];

    /**
     * @OneToMany(targetEntity="DDC3644Pet", mappedBy="owner", orphanRemoval=true)
     */
    public $pets = [];

    public function setAddresses(Collection $addresses)
    {
        $self = $this;

        $this->addresses = $addresses;

        $addresses->map(function ($address) use ($self) {
            $address->user = $self;
        });
    }

    public function setPets(Collection $pets)
    {
        $self = $this;

        $this->pets = $pets;

        $pets->map(function ($pet) use ($self) {
            $pet->owner = $self;
        });
    }
}

/**
 * @Entity
 */
class DDC3644Address
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="DDC3644User", inversedBy="addresses")
     * @JoinColumn(referencedColumnName="hash_id")
     */
    public $user;

    /**
     * @Column(type="string")
     */
    public $address;

    public function __construct($address)
    {
        $this->address = $address;
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discriminator", type="string")
 * @DiscriminatorMap({"pet" = "DDC3644Pet"})
 */
abstract class DDC3644Animal
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/**
 * @Entity
 */
class DDC3644Pet extends DDC3644Animal
{
    /**
     * @ManyToOne(targetEntity="DDC3644User", inversedBy="pets")
     * @JoinColumn(referencedColumnName="hash_id")
     */
    public $owner;
}