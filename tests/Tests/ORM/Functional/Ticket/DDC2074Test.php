<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\Models\ECommerce\ECommerceCategory;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-2074')]
class DDC2074Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('ecommerce');

        parent::setUp();
    }

    public function testShouldNotScheduleDeletionOnClonedInstances(): void
    {
        $class      = $this->_em->getClassMetadata(ECommerceProduct::class);
        $product    = new ECommerceProduct();
        $category   = new ECommerceCategory();
        $collection = new PersistentCollection($this->_em, $class, new ArrayCollection([$category]));
        $collection->setOwner($product, $class->associationMappings['categories']);

        $uow              = $this->_em->getUnitOfWork();
        $clonedCollection = clone $collection;
        $clonedCollection->clear();

        self::assertCount(0, $uow->getScheduledCollectionDeletions());
    }

    public function testSavingClonedPersistentCollection(): void
    {
        $product  = new ECommerceProduct();
        $category = new ECommerceCategory();
        $category->setName('foo');
        $product->addCategory($category);

        $this->_em->persist($product);
        $this->_em->persist($category);
        $this->_em->flush();

        $newProduct = clone $product;

        $this->_em->persist($newProduct);
        $this->_em->flush();
        $this->_em->clear();

        $product1 = $this->_em->find(ECommerceProduct::class, $product->getId());
        $product2 = $this->_em->find(ECommerceProduct::class, $newProduct->getId());

        self::assertCount(1, $product1->getCategories());
        self::assertCount(1, $product2->getCategories());

        self::assertSame($product1->getCategories()->get(0), $product2->getCategories()->get(0));
    }
}
