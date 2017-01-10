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
    public function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
    }

    public function testShouldNotScheduleDeletionOnClonedInstances()
    {
        $class = $this->em->getClassMetadata(ECommerceProduct::class);
        $product = new ECommerceProduct();
        $category = new ECommerceCategory();
        $collection = new PersistentCollection($this->em, $class, new ArrayCollection([$category]));
        $collection->setOwner($product, $class->associationMappings['categories']);

        $uow = $this->em->getUnitOfWork();
        $clonedCollection = clone $collection;
        $clonedCollection->clear();

        self::assertEquals(0, count($uow->getScheduledCollectionDeletions()));
    }

    public function testSavingClonedPersistentCollection()
    {
        $product = new ECommerceProduct();
        $category = new ECommerceCategory();
        $category->setName('foo');
        $product->addCategory($category);

        $this->em->persist($product);
        $this->em->persist($category);
        $this->em->flush();

        $newProduct = clone $product;

        $this->em->persist($newProduct);
        $this->em->flush();
        $this->em->clear();

        $product1 = $this->em->find(ECommerceProduct::class, $product->getId());
        $product2 = $this->em->find(ECommerceProduct::class, $newProduct->getId());

        self::assertCount(1, $product1->getCategories());
        self::assertCount(1, $product2->getCategories());

        self::assertSame($product1->getCategories()->get(0), $product2->getCategories()->get(0));
    }
}
