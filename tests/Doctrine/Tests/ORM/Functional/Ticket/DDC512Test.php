<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class DDC512Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC512Customer::class),
            $this->em->getClassMetadata(DDC512OfferItem::class),
            $this->em->getClassMetadata(DDC512Item::class),
            ]
        );
    }

    public function testIssue()
    {
        $customer1 = new DDC512Customer();
        $item = new DDC512OfferItem();
        $customer1->item = $item;
        $this->em->persist($customer1);

        $customer2 = new DDC512Customer();
        $this->em->persist($customer2);

        $this->em->flush();
        $this->em->clear();

        $q = $this->em->createQuery("select u,i from ".__NAMESPACE__."\\DDC512Customer u left join u.item i");
        $result = $q->getResult();

        self::assertEquals(2, count($result));
        self::assertInstanceOf(DDC512Customer::class, $result[0]);
        self::assertInstanceOf(DDC512Customer::class, $result[1]);
        if ($result[0]->id == $customer1->id) {
            self::assertInstanceOf(DDC512OfferItem::class, $result[0]->item);
            self::assertEquals($item->id, $result[0]->item->id);
            self::assertNull($result[1]->item);
        } else {
            self::assertInstanceOf(DDC512OfferItem::class, $result[1]->item);
            self::assertNull($result[0]->item);
        }
    }
}

/**
 * @Entity
 */
class DDC512Customer {
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * NOTE that we can currently not name the join column the same as the field
     * (item = item), this currently confuses Doctrine.
     *
     * @OneToOne(targetEntity="DDC512OfferItem", cascade={"remove","persist"})
     * @JoinColumn(name="item_id", referencedColumnName="id")
     */
    public $item;
}

/**
 * @Entity
  */
class DDC512OfferItem extends DDC512Item
{
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"item" = "DDC512Item", "offerItem" = "DDC512OfferItem"})
 */
class DDC512Item
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}
