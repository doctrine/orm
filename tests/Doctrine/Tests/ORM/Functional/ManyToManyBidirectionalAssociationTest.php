<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceCategory;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Query;

/**
 * Tests a bidirectional many-to-many association mapping (without inheritance).
 * Owning side is ECommerceProduct, inverse side is ECommerceCategory.
 */
class ManyToManyBidirectionalAssociationTest extends AbstractManyToManyAssociationTestCase
{
    protected $_firstField = 'product_id';
    protected $_secondField = 'category_id';
    protected $_table = 'ecommerce_products_categories';
    private $firstProduct;
    private $secondProduct;
    private $firstCategory;
    private $secondCategory;

    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
        $this->firstProduct = new ECommerceProduct();
        $this->firstProduct->setName("First Product");
        $this->secondProduct = new ECommerceProduct();
        $this->secondProduct->setName("Second Product");
        $this->firstCategory = new ECommerceCategory();
        $this->firstCategory->setName("Business");
        $this->secondCategory = new ECommerceCategory();
        $this->secondCategory->setName("Home");
    }

    public function testSavesAManyToManyAssociationWithCascadeSaveSet()
    {
        $this->firstProduct->addCategory($this->firstCategory);
        $this->firstProduct->addCategory($this->secondCategory);
        $this->_em->persist($this->firstProduct);
        $this->_em->flush();

        self::assertForeignKeysContain($this->firstProduct->getId(), $this->firstCategory->getId());
        self::assertForeignKeysContain($this->firstProduct->getId(), $this->secondCategory->getId());
    }

    public function testRemovesAManyToManyAssociation()
    {
        $this->firstProduct->addCategory($this->firstCategory);
        $this->firstProduct->addCategory($this->secondCategory);
        $this->_em->persist($this->firstProduct);
        $this->firstProduct->removeCategory($this->firstCategory);

        $this->_em->flush();

        self::assertForeignKeysNotContain($this->firstProduct->getId(), $this->firstCategory->getId());
        self::assertForeignKeysContain($this->firstProduct->getId(), $this->secondCategory->getId());

        $this->firstProduct->getCategories()->remove(1);
        $this->_em->flush();

        self::assertForeignKeysNotContain($this->firstProduct->getId(), $this->secondCategory->getId());
    }

    public function testEagerLoadFromInverseSideAndLazyLoadFromOwningSide()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->_createLoadingFixture();
        $categories = $this->_findCategories();
        self::assertLazyLoadFromOwningSide($categories);
    }

    public function testEagerLoadFromOwningSideAndLazyLoadFromInverseSide()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->_createLoadingFixture();
        $products = $this->_findProducts();
        self::assertLazyLoadFromInverseSide($products);
    }

    private function _createLoadingFixture()
    {
        $this->firstProduct->addCategory($this->firstCategory);
        $this->firstProduct->addCategory($this->secondCategory);
        $this->secondProduct->addCategory($this->firstCategory);
        $this->secondProduct->addCategory($this->secondCategory);
        $this->_em->persist($this->firstProduct);
        $this->_em->persist($this->secondProduct);

        $this->_em->flush();
        $this->_em->clear();
    }

    protected function _findProducts()
    {
        $query = $this->_em->createQuery('SELECT p, c FROM Doctrine\Tests\Models\ECommerce\ECommerceProduct p LEFT JOIN p.categories c ORDER BY p.id, c.id');
        //$query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $result = $query->getResult();
        self::assertEquals(2, count($result));
        $cats1 = $result[0]->getCategories();
        $cats2 = $result[1]->getCategories();
        self::assertTrue($cats1->isInitialized());
        self::assertTrue($cats2->isInitialized());
        self::assertFalse($cats1[0]->getProducts()->isInitialized());
        self::assertFalse($cats2[0]->getProducts()->isInitialized());

        return $result;
    }

    protected function _findCategories()
    {
        $query = $this->_em->createQuery('SELECT c, p FROM Doctrine\Tests\Models\ECommerce\ECommerceCategory c LEFT JOIN c.products p ORDER BY c.id, p.id');
        //$query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $result = $query->getResult();
        self::assertEquals(2, count($result));
        self::assertInstanceOf(ECommerceCategory::class, $result[0]);
        self::assertInstanceOf(ECommerceCategory::class, $result[1]);
        $prods1 = $result[0]->getProducts();
        $prods2 = $result[1]->getProducts();
        self::assertTrue($prods1->isInitialized());
        self::assertTrue($prods2->isInitialized());

        self::assertFalse($prods1[0]->getCategories()->isInitialized());
        self::assertFalse($prods2[0]->getCategories()->isInitialized());

        return $result;
    }

    public function assertLazyLoadFromInverseSide($products)
    {
        list ($firstProduct, $secondProduct) = $products;

        $firstProductCategories = $firstProduct->getCategories();
        $secondProductCategories = $secondProduct->getCategories();

        self::assertEquals(2, count($firstProductCategories));
        self::assertEquals(2, count($secondProductCategories));

        self::assertTrue($firstProductCategories[0] === $secondProductCategories[0]);
        self::assertTrue($firstProductCategories[1] === $secondProductCategories[1]);

        $firstCategoryProducts = $firstProductCategories[0]->getProducts();
        $secondCategoryProducts = $firstProductCategories[1]->getProducts();

        self::assertFalse($firstCategoryProducts->isInitialized());
        self::assertFalse($secondCategoryProducts->isInitialized());
        self::assertEquals(0, $firstCategoryProducts->unwrap()->count());
        self::assertEquals(0, $secondCategoryProducts->unwrap()->count());

        self::assertEquals(2, count($firstCategoryProducts)); // lazy-load
        self::assertTrue($firstCategoryProducts->isInitialized());
        self::assertFalse($secondCategoryProducts->isInitialized());
        self::assertEquals(2, count($secondCategoryProducts)); // lazy-load
        self::assertTrue($secondCategoryProducts->isInitialized());

        self::assertInstanceOf(ECommerceProduct::class, $firstCategoryProducts[0]);
        self::assertInstanceOf(ECommerceProduct::class, $firstCategoryProducts[1]);
        self::assertInstanceOf(ECommerceProduct::class, $secondCategoryProducts[0]);
        self::assertInstanceOf(ECommerceProduct::class, $secondCategoryProducts[1]);

        self::assertCollectionEquals($firstCategoryProducts, $secondCategoryProducts);
    }

    public function assertLazyLoadFromOwningSide($categories)
    {
        list ($firstCategory, $secondCategory) = $categories;

        $firstCategoryProducts = $firstCategory->getProducts();
        $secondCategoryProducts = $secondCategory->getProducts();

        self::assertEquals(2, count($firstCategoryProducts));
        self::assertEquals(2, count($secondCategoryProducts));

        self::assertTrue($firstCategoryProducts[0] === $secondCategoryProducts[0]);
        self::assertTrue($firstCategoryProducts[1] === $secondCategoryProducts[1]);

        $firstProductCategories = $firstCategoryProducts[0]->getCategories();
        $secondProductCategories = $firstCategoryProducts[1]->getCategories();

        self::assertFalse($firstProductCategories->isInitialized());
        self::assertFalse($secondProductCategories->isInitialized());
        self::assertEquals(0, $firstProductCategories->unwrap()->count());
        self::assertEquals(0, $secondProductCategories->unwrap()->count());

        self::assertEquals(2, count($firstProductCategories)); // lazy-load
        self::assertTrue($firstProductCategories->isInitialized());
        self::assertFalse($secondProductCategories->isInitialized());
        self::assertEquals(2, count($secondProductCategories)); // lazy-load
        self::assertTrue($secondProductCategories->isInitialized());

        self::assertInstanceOf(ECommerceCategory::class, $firstProductCategories[0]);
        self::assertInstanceOf(ECommerceCategory::class, $firstProductCategories[1]);
        self::assertInstanceOf(ECommerceCategory::class, $secondProductCategories[0]);
        self::assertInstanceOf(ECommerceCategory::class, $secondProductCategories[1]);

        self::assertCollectionEquals($firstProductCategories, $secondProductCategories);
    }
}
