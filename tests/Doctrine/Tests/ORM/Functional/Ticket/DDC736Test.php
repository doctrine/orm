<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;

require_once __DIR__ . '/../../../TestInit.php';

class DDC736Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
    }

    /**
     * @group DDC-736
     */
    public function testFetchJoinInitializesPreviouslyUninitializedCollectionOfManagedEntity()
    {
        $cust = new ECommerceCustomer;
        $cust->setName('roman');
        
        $cart = new ECommerceCart;
        $cart->setPayment('cash');
        $cart->setCustomer($cust);
        
        $this->_em->persist($cust);
        $this->_em->persist($cart);
        $this->_em->flush();
        $this->_em->clear();

        $cart2 = $this->_em->createQuery("select c, ca from Doctrine\Tests\Models\ECommerce\ECommerceCart ca join ca.customer c")
            ->getSingleResult(/*\Doctrine\ORM\Query::HYDRATE_ARRAY*/);

        $this->assertTrue($cart2 instanceof ECommerceCart);
        $this->assertFalse($cart2->getCustomer() instanceof \Doctrine\ORM\Proxy\Proxy);
        $this->assertTrue($cart2->getCustomer() instanceof ECommerceCustomer);
    }
}
