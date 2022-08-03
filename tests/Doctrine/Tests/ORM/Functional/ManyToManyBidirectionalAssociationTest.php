<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ECommerce\ECommerceCategory;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;

use function count;

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

    /** @var ECommerceProduct */
    private $firstProduct;

    /** @var ECommerceProduct */
    private $secondProduct;

    /** @var ECommerceCategory */
    private $firstCategory;

    /** @var ECommerceCategory */
    private $secondCategory;

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
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->createLoadingFixture();
        $categories = $this->findCategories();
        $this->assertLazyLoadFromOwningSide($categories);
    }

    public function testEagerLoadFromOwningSideAndLazyLoadFromInverseSide(): void
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
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

    /**
     * @psalm-return list<ECommerceProduct>
     */
    protected function findProducts(): array
    {
        $query = $this->_em->createQuery('SELECT p, c FROM Doctrine\Tests\Models\ECommerce\ECommerceProduct p LEFT JOIN p.categories c ORDER BY p.id, c.id');
        //$query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $result = $query->getResult();
        $this->assertEquals(2, count($result));
        $cats1 = $result[0]->getCategories();
        $cats2 = $result[1]->getCategories();
        $this->assertTrue($cats1->isInitialized());
        $this->assertTrue($cats2->isInitialized());
        $this->assertFalse($cats1[0]->getProducts()->isInitialized());
        $this->assertFalse($cats2[0]->getProducts()->isInitialized());

        return $result;
    }

    /**
     * @psalm-return list<ECommerceCategory>
     */
    protected function findCategories(): array
    {
        $query = $this->_em->createQuery('SELECT c, p FROM Doctrine\Tests\Models\ECommerce\ECommerceCategory c LEFT JOIN c.products p ORDER BY c.id, p.id');
        //$query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $result = $query->getResult();
        $this->assertEquals(2, count($result));
        $this->assertInstanceOf(ECommerceCategory::class, $result[0]);
        $this->assertInstanceOf(ECommerceCategory::class, $result[1]);
        $prods1 = $result[0]->getProducts();
        $prods2 = $result[1]->getProducts();
        $this->assertTrue($prods1->isInitialized());
        $this->assertTrue($prods2->isInitialized());

        $this->assertFalse($prods1[0]->getCategories()->isInitialized());
        $this->assertFalse($prods2[0]->getCategories()->isInitialized());

        return $result;
    }

    /**
     * @psalm-param list<ECommerceProduct>
     */
    public function assertLazyLoadFromInverseSide(array $products): void
    {
        [$firstProduct, $secondProduct] = $products;

        $firstProductCategories  = $firstProduct->getCategories();
        $secondProductCategories = $secondProduct->getCategories();

        $this->assertEquals(2, count($firstProductCategories));
        $this->assertEquals(2, count($secondProductCategories));

        $this->assertTrue($firstProductCategories[0] === $secondProductCategories[0]);
        $this->assertTrue($firstProductCategories[1] === $secondProductCategories[1]);

        $firstCategoryProducts  = $firstProductCategories[0]->getProducts();
        $secondCategoryProducts = $firstProductCategories[1]->getProducts();

        $this->assertFalse($firstCategoryProducts->isInitialized());
        $this->assertFalse($secondCategoryProducts->isInitialized());
        $this->assertEquals(0, $firstCategoryProducts->unwrap()->count());
        $this->assertEquals(0, $secondCategoryProducts->unwrap()->count());

        $this->assertEquals(2, count($firstCategoryProducts)); // lazy-load
        $this->assertTrue($firstCategoryProducts->isInitialized());
        $this->assertFalse($secondCategoryProducts->isInitialized());
        $this->assertEquals(2, count($secondCategoryProducts)); // lazy-load
        $this->assertTrue($secondCategoryProducts->isInitialized());

        $this->assertInstanceOf(ECommerceProduct::class, $firstCategoryProducts[0]);
        $this->assertInstanceOf(ECommerceProduct::class, $firstCategoryProducts[1]);
        $this->assertInstanceOf(ECommerceProduct::class, $secondCategoryProducts[0]);
        $this->assertInstanceOf(ECommerceProduct::class, $secondCategoryProducts[1]);

        $this->assertCollectionEquals($firstCategoryProducts, $secondCategoryProducts);
    }

    /**
     * @psalm-param list<ECommerceCategory>
     */
    public function assertLazyLoadFromOwningSide(array $categories): void
    {
        [$firstCategory, $secondCategory] = $categories;

        $firstCategoryProducts  = $firstCategory->getProducts();
        $secondCategoryProducts = $secondCategory->getProducts();

        $this->assertEquals(2, count($firstCategoryProducts));
        $this->assertEquals(2, count($secondCategoryProducts));

        $this->assertTrue($firstCategoryProducts[0] === $secondCategoryProducts[0]);
        $this->assertTrue($firstCategoryProducts[1] === $secondCategoryProducts[1]);

        $firstProductCategories  = $firstCategoryProducts[0]->getCategories();
        $secondProductCategories = $firstCategoryProducts[1]->getCategories();

        $this->assertFalse($firstProductCategories->isInitialized());
        $this->assertFalse($secondProductCategories->isInitialized());
        $this->assertEquals(0, $firstProductCategories->unwrap()->count());
        $this->assertEquals(0, $secondProductCategories->unwrap()->count());

        $this->assertEquals(2, count($firstProductCategories)); // lazy-load
        $this->assertTrue($firstProductCategories->isInitialized());
        $this->assertFalse($secondProductCategories->isInitialized());
        $this->assertEquals(2, count($secondProductCategories)); // lazy-load
        $this->assertTrue($secondProductCategories->isInitialized());

        $this->assertInstanceOf(ECommerceCategory::class, $firstProductCategories[0]);
        $this->assertInstanceOf(ECommerceCategory::class, $firstProductCategories[1]);
        $this->assertInstanceOf(ECommerceCategory::class, $secondProductCategories[0]);
        $this->assertInstanceOf(ECommerceCategory::class, $secondProductCategories[1]);

        $this->assertCollectionEquals($firstProductCategories, $secondProductCategories);
    }
}
