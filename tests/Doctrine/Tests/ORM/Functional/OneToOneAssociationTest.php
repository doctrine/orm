<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests association mapping for ECommerceCustomer and ECommerceCart.
 * The latter is the owning side of the relation.
 */
class OneToOneAssociationTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public $customer;
    public $cart;

    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
        $this->customer = new ECommerceCustomer();
        $this->customer->name = 'John Doe';
        $this->cart = new ECommerceCart();
        $this->cart->payment = 'Credit card';
    }

    public function testSavesAOneToOneAssociationWithCascadeSaveSet() {
        $this->customer->setCart($this->cart);
        $this->_em->save($this->customer);

        $this->assertCartForeignKeyIs($this->customer->id);
    }

    public function testDoesNotSaveAnInverseSideSet() {
        $this->customer->cart = $this->cart;
        $this->_em->save($this->customer);

        $this->assertCartForeignKeyIs(null);
    }

    public function testRemovesOneToOneAssociation()
    {
        $this->customer->setCart($this->cart);
        $this->_em->save($this->customer);
        $this->customer->removeCart();

        $this->_em->flush();

        $this->assertCartForeignKeyIs(null);
    }

    public function testLoadsAnAssociation()
    {
        $conn = $this->_em->getConnection();
        $conn->execute('INSERT INTO ecommerce_customers (name) VALUES ("Giorgio")');
        $customerId = $conn->lastInsertId();
        $conn->execute("INSERT INTO ecommerce_carts (customer_id, payment) VALUES ('$customerId', 'paypal')");

        $customer = $this->_em->find("Doctrine\Tests\Models\ECommerce\ECommerceCustomer", $customerId);

        $this->assertEquals('paypal', $customer->cart->payment);
    }

    public function assertCartForeignKeyIs($value) {
        $foreignKey = $this->_em->getConnection()->execute('SELECT customer_id FROM ecommerce_carts WHERE id=?', array($this->cart->id))->fetchColumn();
        $this->assertEquals($value, $foreignKey);
    }
}
