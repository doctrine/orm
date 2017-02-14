<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

class DDC493Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC493Customer::class),
            $this->em->getClassMetadata(DDC493Distributor::class),
            $this->em->getClassMetadata(DDC493Contact::class)
            ]
        );
    }

    public function testIssue()
    {
        $q = $this->em->createQuery("select u, c.data from ".__NAMESPACE__."\\DDC493Distributor u JOIN u.contact c");

        self::assertSQLEquals(
            'SELECT d0_."id" AS id_0, d1_."data" AS data_1, d0_."discr" AS discr_2, d0_."contact" AS contact_3 FROM "DDC493Distributor" d2_ INNER JOIN "DDC493Customer" d0_ ON d2_."id" = d0_."id" INNER JOIN "DDC493Contact" d1_ ON d0_."contact" = d1_."id"',
            $q->getSQL()
        );
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"distributor" = "DDC493Distributor", "customer" = "DDC493Customer"})
 */
class DDC493Customer {
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @ORM\OneToOne(targetEntity="DDC493Contact", cascade={"remove","persist"})
     * @ORM\JoinColumn(name="contact", referencedColumnName="id")
     */
    public $contact;

}

/**
 * @ORM\Entity
 */
class DDC493Distributor extends DDC493Customer {
}

/**
 * @ORM\Entity
  */
class DDC493Contact
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
    /** @ORM\Column(type="string") */
    public $data;
}
