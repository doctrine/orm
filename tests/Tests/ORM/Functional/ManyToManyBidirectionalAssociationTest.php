<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ECommerce\ECommerceCategory;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;

/**
 * Tests a bidirectional many-to-many association mapping (without inheritance).
 * Owning side is ECommerceProduct, inverse side is ECommerceCategory.
 */
class ManyToManyBidirectionalAssociationTest extends AbstractManyToManyAssociationTestCase
{
    /** @var string */
    protected $firstField = 'product_id';

    /** @var string */
    protected $secondField = 'category_id';

    /** @var string */
    protected $table = 'ecommerce_products_categories';

    private ECommerceProduct $firstProduct;

    private ECommerceProduct $secondProduct;

    private ECommerceCategory $firstCategory;

    private ECommerceCategory $secondCategory;

    protected function setUp(): void
    {
        $this->useModelSet('ecommerce');

        parent::setUp();

        $this->firstProduct = new ECommerceProduct();
        $this->firstProduct->setName('First Product');
        $this->secondProduct = new ECommerceProduct();
        $this->secondProduct->setName('Second Product');
        $this->firstCategory = new ECommerceCategory();
        $this->firstCategory->setName('Business');
        $this->secondCategory = new ECommerceCategory();
        $this->secondCategory->setName('Home');
    }

    public function testSavesAManyToManyAssociationWithCascadeSaveSet(): void
    {
        $this->firstProduct->addCategory($this->firstCategory);
        $this->firstProduct->addCategory($this->secondCategory);
        $this->_em->persist($this->firstProduct);
        $this->_em->flush();

        $this->assertForeignKeysContain($this->firstProduct->getId(), $this->firstCategory->getId());
        $this->assertForeignKeysContain($this->firstProduct->getId(), $this->secondCategory->getId());
    }

    public function testRemovesAManyToManyAssociation(): void
    {
        $this->firstProduct->addCategory($this->firstCategory);
        $this->firstProduct->addCategory($this->secondCategory);
        $this->_em->persist($this->firstProduct);
        $this->firstProduct->removeCategory($this->firstCategory);

        $this->_em->flush();

        $this->assertForeignKeysNotContain($this->firstProduct->getId(), $this->firstCategory->getId());
        $this->assertForeignKeysContain($this->firstProduct->getId(), $this->secondCategory->getId());

        $this->firstProduct->getCategories()->remove(1);
        $this->_em->flush();

        $this->assertForeignKeysNotContain($this->firstProduct->getId(), $this->secondCategory->getId());
    }

    public function testEagerLoadFromInverseSideAndLazyLoadFromOwningSide(): void
    {
        $this->createLoadingFixture();
        $categories = $this->findCategories();
        $this->assertLazyLoadFromOwningSide($categories);
    }

    public function testEagerLoadFromOwningSideAndLazyLoadFromInverseSide(): void
    {
        $this->createLoadingFixture();
        $products = $this->findProducts();
        $this->assertLazyLoadFromInverseSide($products);
    }

    private function createLoadingFixture(): void
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

    /** @phpstan-return list<ECommerceProduct> */
    protected function findProducts(): array
    {
        $query  = $this->_em->createQuery('SELECT p, c FROM Doctrine\Tests\Models\ECommerce\ECommerceProduct p LEFT JOIN p.categories c ORDER BY p.id, c.id');
        $result = $query->getResult();
        self::assertCount(2, $result);
        $cats1 = $result[0]->getCategories();
        $cats2 = $result[1]->getCategories();
        self::assertTrue($cats1->isInitialized());
        self::assertTrue($cats2->isInitialized());
        self::assertFalse($cats1[0]->getProducts()->isInitialized());
        self::assertFalse($cats2[0]->getProducts()->isInitialized());

        return $result;
    }

    /** @phpstan-return list<ECommerceCategory> */
    protected function findCategories(): array
    {
        $query  = $this->_em->createQuery('SELECT c, p FROM Doctrine\Tests\Models\ECommerce\ECommerceCategory c LEFT JOIN c.products p ORDER BY c.id, p.id');
        $result = $query->getResult();
        self::assertCount(2, $result);
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

    /** @phpstan-param list<ECommerceProduct> */
    public function assertLazyLoadFromInverseSide(array $products): void
    {
        [$firstProduct, $secondProduct] = $products;

        $firstProductCategories  = $firstProduct->getCategories();
        $secondProductCategories = $secondProduct->getCategories();

        self::assertCount(2, $firstProductCategories);
        self::assertCount(2, $secondProductCategories);

        self::assertSame($firstProductCategories[0], $secondProductCategories[0]);
        self::assertSame($firstProductCategories[1], $secondProductCategories[1]);

        $firstCategoryProducts  = $firstProductCategories[0]->getProducts();
        $secondCategoryProducts = $firstProductCategories[1]->getProducts();

        self::assertFalse($firstCategoryProducts->isInitialized());
        self::assertFalse($secondCategoryProducts->isInitialized());
        self::assertEquals(0, $firstCategoryProducts->unwrap()->count());
        self::assertEquals(0, $secondCategoryProducts->unwrap()->count());

        self::assertCount(2, $firstCategoryProducts); // lazy-load
        self::assertTrue($firstCategoryProducts->isInitialized());
        self::assertFalse($secondCategoryProducts->isInitialized());
        self::assertCount(2, $secondCategoryProducts); // lazy-load
        self::assertTrue($secondCategoryProducts->isInitialized());

        self::assertInstanceOf(ECommerceProduct::class, $firstCategoryProducts[0]);
        self::assertInstanceOf(ECommerceProduct::class, $firstCategoryProducts[1]);
        self::assertInstanceOf(ECommerceProduct::class, $secondCategoryProducts[0]);
        self::assertInstanceOf(ECommerceProduct::class, $secondCategoryProducts[1]);

        $this->assertCollectionEquals($firstCategoryProducts, $secondCategoryProducts);
    }

    /** @phpstan-param list<ECommerceCategory> */
    public function assertLazyLoadFromOwningSide(array $categories): void
    {
        [$firstCategory, $secondCategory] = $categories;

        $firstCategoryProducts  = $firstCategory->getProducts();
        $secondCategoryProducts = $secondCategory->getProducts();

        self::assertCount(2, $firstCategoryProducts);
        self::assertCount(2, $secondCategoryProducts);

        self::assertSame($firstCategoryProducts[0], $secondCategoryProducts[0]);
        self::assertSame($firstCategoryProducts[1], $secondCategoryProducts[1]);

        $firstProductCategories  = $firstCategoryProducts[0]->getCategories();
        $secondProductCategories = $firstCategoryProducts[1]->getCategories();

        self::assertFalse($firstProductCategories->isInitialized());
        self::assertFalse($secondProductCategories->isInitialized());
        self::assertEquals(0, $firstProductCategories->unwrap()->count());
        self::assertEquals(0, $secondProductCategories->unwrap()->count());

        self::assertCount(2, $firstProductCategories); // lazy-load
        self::assertTrue($firstProductCategories->isInitialized());
        self::assertFalse($secondProductCategories->isInitialized());
        self::assertCount(2, $secondProductCategories); // lazy-load
        self::assertTrue($secondProductCategories->isInitialized());

        self::assertInstanceOf(ECommerceCategory::class, $firstProductCategories[0]);
        self::assertInstanceOf(ECommerceCategory::class, $firstProductCategories[1]);
        self::assertInstanceOf(ECommerceCategory::class, $secondProductCategories[0]);
        self::assertInstanceOf(ECommerceCategory::class, $secondProductCategories[1]);

        $this->assertCollectionEquals($firstProductCategories, $secondProductCategories);
    }
}
