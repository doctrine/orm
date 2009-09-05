<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ECommerce\ECommerceCart,
    Doctrine\Tests\Models\ECommerce\ECommerceCategory,
    Doctrine\Tests\Models\ECommerce\ECommerceCustomer,
    Doctrine\Tests\Models\ECommerce\ECommerceProduct;
    
use Doctrine\ORM\Mapping\AssociationMapping;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests capabilities of the persister.
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 */
class StandardEntityPersisterTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
    }

    public function testAcceptsForeignKeysAsCriteria()
    {
        $this->_em->getConfiguration()->setAllowPartialObjects(false);
        
        $customer = new ECommerceCustomer();
        $customer->setName('John Doe');
        $cart = new ECommerceCart();
        $cart->setPayment('Credit card');
        $customer->setCart($cart);
        $this->_em->persist($customer);
        $this->_em->flush();
        $this->_em->clear();
        unset($cart);
        
        $persister = $this->_em->getUnitOfWork()->getEntityPersister('Doctrine\Tests\Models\ECommerce\ECommerceCart');
        $newCart = new ECommerceCart();
        $persister->load(array('customer_id' => $customer->getId()), $newCart);
        $this->assertEquals('Credit card', $newCart->getPayment());
    }
    
    /**
     * Ticket #2478 from Damon Jones (dljones)
     */
    public function testAddPersistRetrieve()
    {
        $category = new ECommerceCategory();
        $category->setName('Eletronics');
        
        $product = new ECommerceProduct();
        $product->setName('MP3 Player Foo');
        $category->addProduct($product);
        
        $product2 = new ECommerceProduct();
        $product2->setName('MP3 Player Bar');
        $category->addProduct($product2);
        
        $this->_em->persist($category);
        $this->_em->flush();
        
        // He reported that using $this->_em->clear(); after flush fixes the problem.
        // It should work out of the box. That's what we are testing.
        
        $q = $this->_em->createQuery('
            SELECT c, p 
              FROM Doctrine\Tests\Models\ECommerce\ECommerceCategory c 
              LEFT JOIN c.products p
        ');
        $res = $q->getResult();
        
        $this->assertEquals(1, count($res));
        $this->assertEquals(2, count($res[0]->getProducts()));
    }
}
