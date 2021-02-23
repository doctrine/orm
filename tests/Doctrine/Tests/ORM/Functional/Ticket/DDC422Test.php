<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\OrmFunctionalTestCase;
use function get_class;

class DDC422Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC422Guest::class),
                $this->em->getClassMetadata(DDC422Customer::class),
                $this->em->getClassMetadata(DDC422Contact::class),
            ]
        );
    }

    /**
     * @group DDC-422
     */
    public function testIssue() : void
    {
        $customer = new DDC422Customer();
        $this->em->persist($customer);
        $this->em->flush();
        $this->em->clear();

        $customer = $this->em->find(get_class($customer), $customer->id);

        self::assertInstanceOf(PersistentCollection::class, $customer->contacts);
        self::assertFalse($customer->contacts->isInitialized());
        $contact = new DDC422Contact();
        $customer->contacts->add($contact);
        self::assertTrue($customer->contacts->isDirty());
        self::assertFalse($customer->contacts->isInitialized());
        $this->em->flush();

        self::assertEquals(1, $this->em->getConnection()->fetchColumn('select count(*) from ddc422_customers_contacts'));
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"guest" = DDC422Guest::class, "customer" = DDC422Customer::class})
 */
class DDC422Guest
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
}

/** @ORM\Entity */
class DDC422Customer extends DDC422Guest
{
    /**
     * @ORM\ManyToMany(targetEntity=DDC422Contact::class, cascade={"persist","remove"})
     * @ORM\JoinTable(name="ddc422_customers_contacts",
     *      joinColumns={@ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="cascade" )},
     *      inverseJoinColumns={@ORM\JoinColumn(name="contact_id", referencedColumnName="id", onDelete="cascade" )}
     *      )
     */
    public $contacts;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
    }
}

/** @ORM\Entity */
class DDC422Contact
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
}
