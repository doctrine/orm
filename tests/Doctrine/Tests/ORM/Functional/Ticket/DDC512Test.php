<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC512Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC512Customer'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC512OfferItem'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC512Item'),
        ));
    }

    public function testIssue()
    {
        $customer1 = new DDC512Customer();
        $item = new DDC512OfferItem();
        $customer1->item = $item;
        $this->_em->persist($customer1);

        $customer2 = new DDC512Customer();
        $this->_em->persist($customer2);

        $this->_em->flush();
        $this->_em->clear();

        $q = $this->_em->createQuery("select u,i from ".__NAMESPACE__."\\DDC512Customer u left join u.item i");
        $result = $q->getResult();

        $this->assertEquals(2, count($result));
        $this->assertInstanceOf(__NAMESPACE__ . '\DDC512Customer', $result[0]);
        $this->assertInstanceOf(__NAMESPACE__ . '\DDC512Customer', $result[1]);
        if ($result[0]->id == $customer1->id) {
            $this->assertInstanceOf(__NAMESPACE__ . '\DDC512OfferItem', $result[0]->item);
            $this->assertEquals($item->id, $result[0]->item->id);
            $this->assertNull($result[1]->item);
        } else {
            $this->assertInstanceOf(__NAMESPACE__ . '\DDC512OfferItem', $result[1]->item);
            $this->assertNull($result[0]->item);
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




