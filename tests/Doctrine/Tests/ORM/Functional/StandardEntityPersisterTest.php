<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;
use Doctrine\Tests\Models\ECommerce\ECommerceFeature;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Tests capabilities of the persister.
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 */
class StandardEntityPersisterTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
    }

    public function testAcceptsForeignKeysAsCriteria()
    {
        $customer = new ECommerceCustomer();
        $customer->setName('John Doe');
        $cart = new ECommerceCart();
        $cart->setPayment('Credit card');
        $customer->setCart($cart);
        $this->_em->persist($customer);
        $this->_em->flush();
        $this->_em->clear();
        $cardId = $cart->getId();
        unset($cart);

        $class = $this->_em->getClassMetadata(ECommerceCart::class);

        $persister = $this->_em->getUnitOfWork()->getEntityPersister(ECommerceCart::class);
        $newCart = new ECommerceCart();
        $this->_em->getUnitOfWork()->registerManaged($newCart, ['id' => $cardId], []);
        $persister->load(['customer_id' => $customer->getId()], $newCart, $class->associationMappings['customer']);
        $this->assertEquals('Credit card', $newCart->getPayment());
    }

    /**
     * Ticket #2478 from Damon Jones (dljones)
     */
    public function testAddPersistRetrieve()
    {
        $f1 = new ECommerceFeature;
        $f1->setDescription('AC-3');

        $f2 = new ECommerceFeature;
        $f2->setDescription('DTS');

        $p = new ECommerceProduct;
        $p->addFeature($f1);
        $p->addFeature($f2);
        $this->_em->persist($p);

        $this->_em->flush();

        $this->assertEquals(2, count($p->getFeatures()));
        $this->assertInstanceOf(PersistentCollection::class, $p->getFeatures());

        $q = $this->_em->createQuery(
            'SELECT p, f
               FROM Doctrine\Tests\Models\ECommerce\ECommerceProduct p
               JOIN p.features f'
        );

        $res = $q->getResult();

        $this->assertEquals(2, count($p->getFeatures()));
        $this->assertInstanceOf(PersistentCollection::class, $p->getFeatures());

        // Check that the features are the same instances still
        foreach ($p->getFeatures() as $feature) {
            if ($feature->getDescription() == 'AC-3') {
                $this->assertTrue($feature === $f1);
            } else {
                $this->assertTrue($feature === $f2);
            }
        }

        // Now we test how Hydrator affects IdentityMap
        // (change from ArrayCollection to PersistentCollection)
        $f3 = new ECommerceFeature();
        $f3->setDescription('XVID');
        $p->addFeature($f3);

        // Now we persist the Feature #3
        $this->_em->persist($p);
        $this->_em->flush();

        $q = $this->_em->createQuery(
            'SELECT p, f
               FROM Doctrine\Tests\Models\ECommerce\ECommerceProduct p
               JOIN p.features f'
        );

        $res = $q->getResult();

        // Persisted Product now must have 3 Feature items
        $this->assertEquals(3, count($res[0]->getFeatures()));
    }
}
