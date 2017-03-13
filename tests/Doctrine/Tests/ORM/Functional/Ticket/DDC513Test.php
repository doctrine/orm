<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

class DDC513Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC513OfferItem::class),
            $this->em->getClassMetadata(DDC513Item::class),
            $this->em->getClassMetadata(DDC513Price::class),
            ]
        );
    }

    public function testIssue()
    {
        $q = $this->em->createQuery("select u from ".__NAMESPACE__."\\DDC513OfferItem u left join u.price p");

        self::assertSQLEquals(
            'SELECT t0."id" AS c0, t0."discr" AS c1, t0."price" AS c2 FROM "DDC513OfferItem" t1 INNER JOIN "DDC513Item" t0 ON t1."id" = t0."id" LEFT JOIN "DDC513Price" t2 ON t0."price" = t2."id"',
            $q->getSQL()
        );
    }
}

/**
 * @ORM\Entity
  */
class DDC513OfferItem extends DDC513Item
{
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"item" = "DDC513Item", "offerItem" = "DDC513OfferItem"})
 */
class DDC513Item
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\OneToOne(targetEntity="DDC513Price", cascade={"remove","persist"})
     * @ORM\JoinColumn(name="price", referencedColumnName="id")
     */
    public $price;
}

/**
 * @ORM\Entity
 */
class DDC513Price {
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /** @ORM\Column(type="string") */
    public $data;
}
