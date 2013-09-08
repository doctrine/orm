<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @group
 */
class DDC2660Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setup()
    {
        parent::setup();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2660Product'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2660Customer'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2660CustomerOrder')
            ));
        } catch(\Exception $e) {
            return;
        }

        for ($i = 0; $i < 5; $i++) {
            $product = new DDC2660Product();
            $customer = new DDC2660Customer();
            $order = new DDC2660CustomerOrder($product, $customer, 'name' . $i);

            $this->_em->persist($product);
            $this->_em->persist($customer);
            $this->_em->flush();

            $this->_em->persist($order);
            $this->_em->flush();
        }

        $this->_em->clear();
    }

    public function testIssueWithExtraColumn()
    {
        $sql = "SELECT o.product_id, o.customer_id, o.name FROM ddc_2660_customer_order o";

        $rsm = new ResultSetMappingBuilder($this->_getEntityManager());
        $rsm->addRootEntityFromClassMetadata(__NAMESPACE__ . '\DDC2660CustomerOrder', 'c');

        $query  = $this->_em->createNativeQuery($sql, $rsm);
        $result = $query->getResult();

        $this->assertCount(5, $result);

        foreach ($result as $order) {
            $this->assertNotNull($order);
            $this->assertInstanceOf(__NAMESPACE__ . '\\DDC2660CustomerOrder', $order);
        }
    }

    public function testIssueWithoutExtraColumn()
    {
        $sql = "SELECT o.product_id, o.customer_id FROM ddc_2660_customer_order o";

        $rsm = new ResultSetMappingBuilder($this->_getEntityManager());
        $rsm->addRootEntityFromClassMetadata(__NAMESPACE__ . '\DDC2660CustomerOrder', 'c');

        $query  = $this->_em->createNativeQuery($sql, $rsm);
        $result = $query->getResult();

        $this->assertCount(5, $result);

        foreach ($result as $order) {
            $this->assertNotNull($order);
            $this->assertInstanceOf(__NAMESPACE__ . '\\DDC2660CustomerOrder', $order);
        }
    }
}
/**
 * @Entity @Table(name="ddc_2660_product")
 */
class DDC2660Product
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

/** @Entity  @Table(name="ddc_2660_customer") */
class DDC2660Customer
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

/** @Entity @Table(name="ddc_2660_customer_order") */
class DDC2660CustomerOrder
{
    /**
     * @Id @ManyToOne(targetEntity="DDC2660Product")
     */
    public $product;

    /**
     * @Id @ManyToOne(targetEntity="DDC2660Customer")
     */
    public $customer;

    /**
     * @Column(type="string")
     */
    public $name;

    public function __construct(DDC2660Product $product, DDC2660Customer $customer, $name)
    {
        $this->product  = $product;
        $this->customer = $customer;
        $this->name = $name;
    }
}
