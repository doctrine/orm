<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC493Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC493Customer'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC493Distributor'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC493Contact')
        ));
    }

    public function testIssue()
    {
        $q = $this->_em->createQuery("select u, c.data from ".__NAMESPACE__."\\DDC493Distributor u JOIN u.contact c");
        $this->assertEquals('SELECT d0_.id AS id0, d1_.data AS data1, d0_.discr AS discr2, d0_.contact AS contact3 FROM DDC493Distributor d2_ INNER JOIN DDC493Customer d0_ ON d2_.id = d0_.id INNER JOIN DDC493Contact d1_ ON d0_.contact = d1_.id', $q->getSQL());
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"distributor" = "DDC493Distributor", "customer" = "DDC493Customer"})
 */
class DDC493Customer {
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @OneToOne(targetEntity="DDC493Contact", cascade={"remove","persist"})
     * @JoinColumn(name="contact", referencedColumnName="id")
     */
    public $contact;

}

/**
 * @Entity
 */
class DDC493Distributor extends DDC493Customer {
}

/**
 * @Entity
  */
class DDC493Contact
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
    /** @Column(type="string") */
    public $data;
}




