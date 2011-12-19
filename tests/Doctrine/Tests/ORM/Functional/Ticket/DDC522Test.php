<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Company\CompanyPerson;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * Tests that join columns (foreign keys) can be named the same as the association
 * fields they're used on without causing issues.
 */
class DDC522Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC522Customer'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC522Cart'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC522ForeignKeyTest')
            ));
        } catch(\Exception $e) {

        }
    }

    /**
     * @group DDC-522
     */
    public function testJoinColumnWithSameNameAsAssociationField()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

        $cust = new DDC522Customer;
        $cust->name = "name";
        $cart = new DDC522Cart;
        $cart->total = 0;
        $cust->cart = $cart;
        $cart->customer = $cust;
        $this->_em->persist($cust);
        $this->_em->persist($cart);
        $this->_em->flush();

        $this->_em->clear();

        $r = $this->_em->createQuery("select ca,c from ".get_class($cart)." ca join ca.customer c")
                ->getResult();

        $this->assertInstanceOf(__NAMESPACE__ . '\DDC522Cart', $r[0]);
        $this->assertInstanceOf(__NAMESPACE__ . '\DDC522Customer', $r[0]->customer);
        $this->assertNotInstanceOf('Doctrine\ORM\Proxy\Proxy', $r[0]->customer);
        $this->assertEquals('name', $r[0]->customer->name);

        $fkt = new DDC522ForeignKeyTest();
        $fkt->cartId = $r[0]->id; // ignored for persistence
        $fkt->cart = $r[0]; // must be set properly
        $this->_em->persist($fkt);
        $this->_em->flush();
        $this->_em->clear();

        $fkt2 = $this->_em->find(get_class($fkt), $fkt->id);
        $this->assertEquals($fkt->cart->id, $fkt2->cartId);
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $fkt2->cart);
        $this->assertFalse($fkt2->cart->__isInitialized__);
    }

    /**
     * @group DDC-522
     * @group DDC-762
     */
    public function testJoinColumnWithNullSameNameAssociationField()
    {
        $fkCust = new DDC522ForeignKeyTest;
        $fkCust->name = "name";
        $fkCust->cart = null;

        $this->_em->persist($fkCust);
        $this->_em->flush();
        $this->_em->clear();

        $newCust = $this->_em->find(get_class($fkCust), $fkCust->id);
    }
}

/** @Entity */
class DDC522Customer {
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /** @Column */
    public $name;
    /** @OneToOne(targetEntity="DDC522Cart", mappedBy="customer") */
    public $cart;
}

/** @Entity */
class DDC522Cart {
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /** @Column(type="integer") */
    public $total;
    /**
     * @OneToOne(targetEntity="DDC522Customer", inversedBy="cart")
     * @JoinColumn(name="customer", referencedColumnName="id")
     */
    public $customer;
}

/** @Entity */
class DDC522ForeignKeyTest {
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /** @Column(type="integer", name="cart_id", nullable=true) */
    public $cartId;
    /**
     * @OneToOne(targetEntity="DDC522Cart")
     * @JoinColumn(name="cart_id", referencedColumnName="id")
     */
    public $cart;
}
