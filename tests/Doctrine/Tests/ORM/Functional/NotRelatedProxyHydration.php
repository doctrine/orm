<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;

/**
 * Tests a ManyToOne reated hydration does not remove proxy
 */
class NotRelatedProxyHydration extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected $customerId;
    protected $mentor1Id;
    protected $mentor2Id;

    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();

        $mentor1 = new ECommerceCustomer();
        $mentor1->setName('John Doe');
        $this->_em->persist($mentor1);
        $mentor2 = new ECommerceCustomer();
        $mentor2->setName('Jane Doe');
        $this->_em->persist($mentor2);

        $customer = new ECommerceCustomer();
        $customer->setName('Jean Dupont');
        $customer->setMentor($mentor1);

        $this->_em->persist($customer);
        $this->_em->flush();

        $this->customerId = $customer->getId();
        $this->mentor1Id = $mentor1->getId();
        $this->mentor2Id = $mentor2->getId();

        $this->_em->clear();
    }

    public function testHydrationDoesNotResetProxy()
    {
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\ECommerce\ECommerceCustomer');

        $customer = $repos->find($this->customerId);
        $this->assertSame($this->mentor1Id, $customer->getMentor()->getId());

        $customer->setMentor($this->_em->getReference('Doctrine\Tests\Models\ECommerce\ECommerceCustomer', $this->mentor2Id));
        $repos->findOneByName('Jean Dupont');

        $this->assertSame($this->mentor2Id, $customer->getMentor()->getId());
    }
}
