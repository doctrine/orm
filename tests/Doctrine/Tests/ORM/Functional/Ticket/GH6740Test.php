<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceCategory;
use Doctrine\Common\Collections\Criteria;

final class GH6740Test extends OrmFunctionalTestCase
{
    /**
     * @var int
     */
    private $productId;

    /**
     * @var int
     */
    private $firstCategoryId;

    /**
     * @var int
     */
    private $secondCategoryId;

    public function setUp() : void
    {
        $this->useModelSet('ecommerce');

        parent::setUp();

        $product = new ECommerceProduct();
        $product->setName('First Product');

        $firstCategory  = new ECommerceCategory();
        $secondCategory = new ECommerceCategory();

        $firstCategory->setName('Business');
        $secondCategory->setName('Home');

        $product->addCategory($firstCategory);
        $product->addCategory($secondCategory);

        $this->_em->persist($product);
        $this->_em->flush();
        $this->_em->clear();

        $this->productId        = $product->getId();
        $this->firstCategoryId  = $firstCategory->getId();
        $this->secondCategoryId = $secondCategory->getId();
    }

    /**
     * @group 6740
     */
    public function testCollectionFilteringLteOperator() : void
    {
        $product  = $this->_em->find(ECommerceProduct::class, $this->productId);
        $criteria = Criteria::create()->where(Criteria::expr()->lte('id', $this->secondCategoryId));

        self::assertCount(2, $product->getCategories()->matching($criteria));
    }

    /**
     * @group 6740
     */
    public function testCollectionFilteringLtOperator() : void
    {
        $product  = $this->_em->find(ECommerceProduct::class, $this->productId);
        $criteria = Criteria::create()->where(Criteria::expr()->lt('id', $this->secondCategoryId));

        self::assertCount(1, $product->getCategories()->matching($criteria));
    }

    /**
     * @group 6740
     */
    public function testCollectionFilteringGteOperator() : void
    {
        $product  = $this->_em->find(ECommerceProduct::class, $this->productId);
        $criteria = Criteria::create()->where(Criteria::expr()->gte('id', $this->firstCategoryId));

        self::assertCount(2, $product->getCategories()->matching($criteria));
    }

    /**
     * @group 6740
     */
    public function testCollectionFilteringGtOperator() : void
    {
        $product  = $this->_em->find(ECommerceProduct::class, $this->productId);
        $criteria = Criteria::create()->where(Criteria::expr()->gt('id', $this->firstCategoryId));

        self::assertCount(1, $product->getCategories()->matching($criteria));
    }

    /**
     * @group 6740
     */
    public function testCollectionFilteringEqualsOperator() : void
    {
        $product  = $this->_em->find(ECommerceProduct::class, $this->productId);
        $criteria = Criteria::create()->where(Criteria::expr()->eq('id', $this->firstCategoryId));

        self::assertCount(1, $product->getCategories()->matching($criteria));
    }
}
