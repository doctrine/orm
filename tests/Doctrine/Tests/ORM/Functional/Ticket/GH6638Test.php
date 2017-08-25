<?php

namespace Doctrine\Tests\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

final class GH6638Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(GH6638Customer::class),
            $this->_em->getClassMetadata(GH6638Cart::class),
        ]);
    }

    public function testFetchingOfOneToOneRelations() : void
    {
        $initialCustomer = new GH6638Customer();

        $initialCart = new GH6638Cart();
        $initialCustomer->cart = $initialCart;
        $initialCart->customer = $initialCustomer;

        $this->_em->persist($initialCustomer);
        $this->_em->persist($initialCart);
        $this->_em->flush();
        $this->_em->clear();

        $repository = $this->_em->getRepository(GH6638Customer::class);

        $customer = $repository->find($initialCustomer->id);

        $this->assertInstanceOf(GH6638Cart::class, $customer->cart);

        $customer->cart = null;

        $this->assertNull($customer->cart);

        $repository->findBy(['id' => $initialCustomer->id]);

        $this->assertNull($customer->cart);
    }
}

/**
 * @Entity
 */
class GH6638Customer
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @OneToOne(targetEntity="GH6638Cart", mappedBy="customer")
     */
    public $cart;
}

/**
 * @Entity
 */
class GH6638Cart
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @OneToOne(targetEntity="GH6638Customer", inversedBy="cart")
     * @JoinColumn()
     */
    public $customer;
}
