<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Tests a bidirectional one-to-one association mapping (without inheritance).
 */
class OneToOneBidirectionalAssociationTest extends OrmFunctionalTestCase
{
    private ECommerceCustomer $customer;

    private ECommerceCart $cart;

    protected function setUp(): void
    {
        $this->useModelSet('ecommerce');

        parent::setUp();

        $this->customer = new ECommerceCustomer();
        $this->customer->setName('John Doe');
        $this->cart = new ECommerceCart();
        $this->cart->setPayment('Credit card');
    }

    public function testSavesAOneToOneAssociationWithCascadeSaveSet(): void
    {
        $this->customer->setCart($this->cart);
        $this->_em->persist($this->customer);
        $this->_em->flush();

        $this->assertCartForeignKeyIs($this->customer->getId());
    }

    public function testDoesNotSaveAnInverseSideSet(): void
    {
        $this->customer->brokenSetCart($this->cart);
        $this->_em->persist($this->customer);
        $this->_em->flush();

        $this->assertCartForeignKeyIs(null);
    }

    public function testRemovesOneToOneAssociation(): void
    {
        $this->customer->setCart($this->cart);
        $this->_em->persist($this->customer);
        $this->customer->removeCart();

        $this->_em->flush();

        $this->assertCartForeignKeyIs(null);
    }

    public function testEagerLoad(): void
    {
        $this->createFixture();

        $query    = $this->_em->createQuery('select c, ca from Doctrine\Tests\Models\ECommerce\ECommerceCustomer c join c.cart ca');
        $result   = $query->getResult();
        $customer = $result[0];

        self::assertInstanceOf(ECommerceCart::class, $customer->getCart());
        self::assertEquals('paypal', $customer->getCart()->getPayment());
    }

    public function testLazyLoadsObjectsOnTheOwningSide(): void
    {
        $this->createFixture();
        $metadata                                         = $this->_em->getClassMetadata(ECommerceCart::class);
        $metadata->associationMappings['customer']->fetch = ClassMetadata::FETCH_LAZY;

        $query  = $this->_em->createQuery('select c from Doctrine\Tests\Models\ECommerce\ECommerceCart c');
        $result = $query->getResult();
        $cart   = $result[0];

        self::assertInstanceOf(ECommerceCustomer::class, $cart->getCustomer());
        self::assertEquals('Giorgio', $cart->getCustomer()->getName());
    }

    public function testInverseSideIsNeverLazy(): void
    {
        $this->createFixture();
        $metadata                                       = $this->_em->getClassMetadata(ECommerceCustomer::class);
        $metadata->associationMappings['mentor']->fetch = ClassMetadata::FETCH_EAGER;

        $query    = $this->_em->createQuery('select c from Doctrine\Tests\Models\ECommerce\ECommerceCustomer c');
        $result   = $query->getResult();
        $customer = $result[0];

        self::assertNull($customer->getMentor());
        self::assertInstanceOf(ECommerceCart::class, $customer->getCart());
        self::assertFalse($this->isUninitializedObject($customer->getCart()));
        self::assertEquals('paypal', $customer->getCart()->getPayment());
    }

    public function testUpdateWithProxyObject(): void
    {
        $cust = new ECommerceCustomer();
        $cust->setName('Roman');
        $cart = new ECommerceCart();
        $cart->setPayment('CARD');
        $cust->setCart($cart);

        $this->_em->persist($cust);
        $this->_em->flush();
        $this->_em->clear();

        self::assertInstanceOf(ECommerceCart::class, $cust->getCart());
        self::assertEquals('Roman', $cust->getName());
        self::assertSame($cust, $cart->getCustomer());

        $query = $this->_em->createQuery('select ca from Doctrine\Tests\Models\ECommerce\ECommerceCart ca where ca.id =?1');
        $query->setParameter(1, $cart->getId());

        $cart2 = $query->getSingleResult();

        $cart2->setPayment('CHEQUE');

        $this->_em->flush();
        $this->_em->clear();

        $query2 = $this->_em->createQuery('select ca, c from Doctrine\Tests\Models\ECommerce\ECommerceCart ca left join ca.customer c where ca.id =?1');
        $query2->setParameter(1, $cart->getId());

        $cart3 = $query2->getSingleResult();

        self::assertInstanceOf(ECommerceCustomer::class, $cart3->getCustomer());
        self::assertEquals('Roman', $cart3->getCustomer()->getName());
    }

    protected function createFixture(): void
    {
        $customer = new ECommerceCustomer();
        $customer->setName('Giorgio');
        $cart = new ECommerceCart();
        $cart->setPayment('paypal');
        $customer->setCart($cart);

        $this->_em->persist($customer);

        $this->_em->flush();
        $this->_em->clear();
    }

    public function assertCartForeignKeyIs($value): void
    {
        $foreignKey = $this->_em->getConnection()->executeQuery('SELECT customer_id FROM ecommerce_carts WHERE id=?', [$this->cart->getId()])->fetchOne();
        self::assertEquals($value, $foreignKey);
    }
}
