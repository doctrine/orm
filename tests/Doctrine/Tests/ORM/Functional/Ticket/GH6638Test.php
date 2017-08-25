<?php

namespace Doctrine\Tests\Functional\Ticket;

use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group #6638
 * @group #6648
 */
final class GH6638Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema([
                $this->_em->getClassMetadata(GH6638Customer::class),
                $this->_em->getClassMetadata(GH6638Cart::class),
            ]);
        } catch (ToolsException $ignored) {
        }
    }

    public function testFindByDoesNotReHydrateAssociation() : void
    {
        $initialCustomer = new GH6638Customer();
        $initialCart     = new GH6638Cart();

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

    public function testFindOneByDoesNotReHydrateAssociation() : void
    {
        $initialCustomer = new GH6638Customer();
        $initialCart     = new GH6638Cart();

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

        $repository->findOneBy(['id' => $initialCustomer->id]);

        $this->assertNull($customer->cart);
    }
}

/** @Entity */
class GH6638Customer
{
    /** @Id @Column(type="string") @GeneratedValue(strategy="NONE") */
    public $id;

    /** @OneToOne(targetEntity=GH6638Cart::class, mappedBy="customer") */
    public $cart;

    public function __construct()
    {
        $this->id = uniqid(self::class, true);
    }
}

/** @Entity */
class GH6638Cart
{
    /** @Id @Column(type="string") @GeneratedValue(strategy="NONE") */
    public $id;

    /**
     * @OneToOne(targetEntity=GH6638Customer::class, inversedBy="cart")
     */
    public $customer;

    public function __construct()
    {
        $this->id = uniqid(self::class, true);
    }
}
