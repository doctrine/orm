<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FetchMode;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Tests a bidirectional one-to-one association mapping (without inheritance).
 */
class OneToOneBidirectionalAssociationTest extends OrmFunctionalTestCase
{
    private $customer;
    private $cart;

    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
        $this->customer = new ECommerceCustomer();
        $this->customer->setName('John Doe');
        $this->cart = new ECommerceCart();
        $this->cart->setPayment('Credit card');
    }

    public function testSavesAOneToOneAssociationWithCascadeSaveSet() {
        $this->customer->setCart($this->cart);
        $this->em->persist($this->customer);
        $this->em->flush();

        self::assertCartForeignKeyIs($this->customer->getId());
    }

    public function testDoesNotSaveAnInverseSideSet() {
        $this->customer->brokenSetCart($this->cart);
        $this->em->persist($this->customer);
        $this->em->flush();

        self::assertCartForeignKeyIs(null);
    }

    public function testRemovesOneToOneAssociation()
    {
        $this->customer->setCart($this->cart);
        $this->em->persist($this->customer);
        $this->customer->removeCart();

        $this->em->flush();

        self::assertCartForeignKeyIs(null);
    }

    public function testEagerLoad()
    {
        $this->createFixture();

        $query = $this->em->createQuery('select c, ca from Doctrine\Tests\Models\ECommerce\ECommerceCustomer c join c.cart ca');
        $result = $query->getResult();
        $customer = $result[0];

        self::assertInstanceOf(ECommerceCart::class, $customer->getCart());
        self::assertEquals('paypal', $customer->getCart()->getPayment());
    }

    public function testLazyLoadsObjectsOnTheOwningSide() {
        $this->createFixture();
        $metadata = $this->em->getClassMetadata(ECommerceCart::class);
        $metadata->associationMappings['customer']['fetchMode'] = FetchMode::LAZY;

        $query = $this->em->createQuery('select c from Doctrine\Tests\Models\ECommerce\ECommerceCart c');
        $result = $query->getResult();
        $cart = $result[0];

        self::assertInstanceOf(ECommerceCustomer::class, $cart->getCustomer());
        self::assertEquals('Giorgio', $cart->getCustomer()->getName());
    }

    public function testInverseSideIsNeverLazy()
    {
        $this->createFixture();
        $metadata = $this->em->getClassMetadata(ECommerceCustomer::class);
        $metadata->associationMappings['mentor']['fetch'] = FetchMode::EAGER;

        $query = $this->em->createQuery('select c from Doctrine\Tests\Models\ECommerce\ECommerceCustomer c');
        $result = $query->getResult();
        $customer = $result[0];

        self::assertNull($customer->getMentor());
        self::assertInstanceOf(ECommerceCart::class, $customer->getCart());
        self::assertNotInstanceOf(Proxy::class, $customer->getCart());
        self::assertEquals('paypal', $customer->getCart()->getPayment());
    }

    public function testUpdateWithProxyObject()
    {
        $cust = new ECommerceCustomer;
        $cust->setName('Roman');
        $cart = new ECommerceCart;
        $cart->setPayment('CARD');
        $cust->setCart($cart);

        $this->em->persist($cust);
        $this->em->flush();
        $this->em->clear();

        self::assertInstanceOf(ECommerceCart::class, $cust->getCart());
        self::assertEquals('Roman', $cust->getName());
        self::assertSame($cust, $cart->getCustomer());

        $query = $this->em->createQuery('select ca from Doctrine\Tests\Models\ECommerce\ECommerceCart ca where ca.id =?1');
        $query->setParameter(1, $cart->getId());

        $cart2 = $query->getSingleResult();

        $cart2->setPayment('CHEQUE');

        $this->em->flush();
        $this->em->clear();

        $query2 = $this->em->createQuery('select ca, c from Doctrine\Tests\Models\ECommerce\ECommerceCart ca left join ca.customer c where ca.id =?1');
        $query2->setParameter(1, $cart->getId());

        $cart3 = $query2->getSingleResult();

        self::assertInstanceOf(ECommerceCustomer::class, $cart3->getCustomer());
        self::assertEquals('Roman', $cart3->getCustomer()->getName());
    }

    protected function createFixture()
    {
        $customer = new ECommerceCustomer;
        $customer->setName('Giorgio');
        $cart = new ECommerceCart;
        $cart->setPayment('paypal');
        $customer->setCart($cart);

        $this->em->persist($customer);

        $this->em->flush();
        $this->em->clear();
    }

    public function assertCartForeignKeyIs($value) {
        $foreignKey = $this->em->getConnection()->executeQuery('SELECT customer_id FROM ecommerce_carts WHERE id=?', [$this->cart->getId()])->fetchColumn();
        self::assertEquals($value, $foreignKey);
    }
}
