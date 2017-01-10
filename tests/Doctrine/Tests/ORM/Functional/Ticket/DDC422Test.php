<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\PersistentCollection;

class DDC422Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC422Guest::class),
            $this->em->getClassMetadata(DDC422Customer::class),
            $this->em->getClassMetadata(DDC422Contact::class)
            ]
        );
    }

    /**
     * @group DDC-422
     */
    public function testIssue()
    {
        $customer = new DDC422Customer;
        $this->em->persist($customer);
        $this->em->flush();
        $this->em->clear();

        $customer = $this->em->find(get_class($customer), $customer->id);

        self::assertInstanceOf(PersistentCollection::class, $customer->contacts);
        self::assertFalse($customer->contacts->isInitialized());
        $contact = new DDC422Contact;
        $customer->contacts->add($contact);
        self::assertTrue($customer->contacts->isDirty());
        self::assertFalse($customer->contacts->isInitialized());
        $this->em->flush();

        self::assertEquals(1, $this->em->getConnection()->fetchColumn("select count(*) from ddc422_customers_contacts"));
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"guest" = "DDC422Guest", "customer" = "DDC422Customer"})
 */
class DDC422Guest {
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

/** @Entity */
class DDC422Customer extends DDC422Guest {
    /**
     * @ManyToMany(targetEntity="DDC422Contact", cascade={"persist","remove"})
     * @JoinTable(name="ddc422_customers_contacts",
     *      joinColumns={@JoinColumn(name="customer_id", referencedColumnName="id", onDelete="cascade" )},
     *      inverseJoinColumns={@JoinColumn(name="contact_id", referencedColumnName="id", onDelete="cascade" )}
     *      )
     */
    public $contacts;

    public function __construct() {
        $this->contacts = new \Doctrine\Common\Collections\ArrayCollection;
    }
}

/** @Entity */
class DDC422Contact {
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

