<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\Models\ECommerce\ECommerceCategory;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;

/**
 * @group DDC-2074
 */
class DDC2074Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testShouldNotScheduleDeletionOnClonedInstances()
    {
        $class = $this->_em->getClassMetadata('Doctrine\Tests\Models\ECommerce\ECommerceProduct');
        $product = new ECommerceProduct();
        $category = new ECommerceCategory();
        $collection = new PersistentCollection($this->_em, $class, new ArrayCollection(array($category)));
        $collection->setOwner($product, $class->associationMappings['categories']);

        $uow = $this->_em->getUnitOfWork();
        $clonedCollection = clone $collection;
        $clonedCollection->clear();

        $this->assertEquals(0, count($uow->getScheduledCollectionDeletions()));
    }
}