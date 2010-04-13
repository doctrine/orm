<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC513Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC513OfferItem'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC513Item'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC513Price'),
        ));
    }

    public function testIssue()
    {
        $q = $this->_em->createQuery("select u from ".__NAMESPACE__."\\DDC513OfferItem u left join u.price p");
        $this->assertEquals('SELECT d0_.id AS id0, d0_.discr AS discr1, d0_.price AS price2 FROM DDC513OfferItem d1_ INNER JOIN DDC513Item d0_ ON d1_.id = d0_.id LEFT JOIN DDC513Price d2_ ON d0_.price = d2_.id', $q->getSQL());
    }
}

/**
 * @Entity
  */
class DDC513OfferItem extends DDC513Item
{
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"item" = "DDC513Item", "offerItem" = "DDC513OfferItem"})
 */
class DDC513Item
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @OneToOne(targetEntity="DDC513Price", cascade={"remove","persist"})
     * @JoinColumn(name="price", referencedColumnName="id")
     */
    public $price;
}

/**
 * @Entity
 */
class DDC513Price {
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /** @Column(type="string") */
    public $data;
}




