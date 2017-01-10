<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Proxy\Proxy;

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
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC522Customer::class),
                    $this->em->getClassMetadata(DDC522Cart::class),
                    $this->em->getClassMetadata(DDC522ForeignKeyTest::class)
                ]
            );
        } catch(\Exception $e) {
        }
    }

    /**
     * @group DDC-522
     */
    public function testJoinColumnWithSameNameAsAssociationField()
    {
        $cust = new DDC522Customer;
        $cust->name = "name";
        $cart = new DDC522Cart;
        $cart->total = 0;
        $cust->cart = $cart;
        $cart->customer = $cust;
        $this->em->persist($cust);
        $this->em->persist($cart);
        $this->em->flush();

        $this->em->clear();

        $r = $this->em->createQuery('select ca,c from ' . DDC522Cart::class . ' ca join ca.customer c')
            ->getResult();

        self::assertInstanceOf(DDC522Cart::class, $r[0]);
        self::assertInstanceOf(DDC522Customer::class, $r[0]->customer);
        self::assertNotInstanceOf(Proxy::class, $r[0]->customer);
        self::assertEquals('name', $r[0]->customer->name);

        $fkt = new DDC522ForeignKeyTest();
        $fkt->cartId = $r[0]->id; // ignored for persistence
        $fkt->cart = $r[0]; // must be set properly
        $this->em->persist($fkt);
        $this->em->flush();
        $this->em->clear();

        $fkt2 = $this->em->find(get_class($fkt), $fkt->id);
        self::assertEquals($fkt->cart->id, $fkt2->cartId);
        self::assertInstanceOf(Proxy::class, $fkt2->cart);
        self::assertFalse($fkt2->cart->__isInitialized__);
    }

    /**
     * @group DDC-522
     * @group DDC-762
     */
    public function testJoinColumnWithNullSameNameAssociationField()
    {
        $fkCust = new DDC522ForeignKeyTest;
        $fkCust->name = 'name';
        $fkCust->cart = null;

        $this->em->persist($fkCust);
        $this->em->flush();
        $this->em->clear();

        $expected = clone $fkCust;

        // removing dynamic field (which is not persisted)
        unset($expected->name);

        self::assertEquals($expected, $this->em->find(DDC522ForeignKeyTest::class, $fkCust->id));
    }
}

/** @Entity */
class DDC522Customer
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @Column */
    public $name;

    /** @OneToOne(targetEntity="DDC522Cart", mappedBy="customer") */
    public $cart;
}

/** @Entity */
class DDC522Cart
{
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
class DDC522ForeignKeyTest
{
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
