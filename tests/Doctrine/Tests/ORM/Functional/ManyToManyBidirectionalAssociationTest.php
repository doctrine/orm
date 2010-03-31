<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceCategory;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Query;

require_once __DIR__ . '/../../TestInit.php';

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
        
        $this->assertForeignKeysContain($this->firstProduct->getId(), $this->firstCategory->getId());
        $this->assertForeignKeysContain($this->firstProduct->getId(), $this->secondCategory->getId());
    }

    public function testRemovesAManyToManyAssociation()
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

    public function testEagerLoadFromInverseSideAndLazyLoadFromOwningSide()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->_createLoadingFixture();
        $categories = $this->_findCategories();
        $this->assertLazyLoadFromOwningSide($categories);
    }

    public function testEagerLoadFromOwningSideAndLazyLoadFromInverseSide()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->_createLoadingFixture();
        $products = $this->_findProducts();
        $this->assertLazyLoadFromInverseSide($products);
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
        $this->assertEquals(2, count($result));
        $cats1 = $result[0]->getCategories();
        $cats2 = $result[1]->getCategories();
        $this->assertTrue($cats1->isInitialized());
        $this->assertTrue($cats2->isInitialized());
        $this->assertFalse($cats1[0]->getProducts()->isInitialized());
        $this->assertFalse($cats2[0]->getProducts()->isInitialized());

        return $result;
    }
    
    protected function _findCategories()
    {
        $query = $this->_em->createQuery('SELECT c, p FROM Doctrine\Tests\Models\ECommerce\ECommerceCategory c LEFT JOIN c.products p ORDER BY c.id, p.id');
        //$query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $result = $query->getResult();
        $this->assertEquals(2, count($result));
        $this->assertTrue($result[0] instanceof ECommerceCategory);
        $this->assertTrue($result[1] instanceof ECommerceCategory);
        $prods1 = $result[0]->getProducts();
        $prods2 = $result[1]->getProducts();
        $this->assertTrue($prods1->isInitialized());
        $this->assertTrue($prods2->isInitialized());
        
        $this->assertFalse($prods1[0]->getCategories()->isInitialized());
        $this->assertFalse($prods2[0]->getCategories()->isInitialized());

        return $result;
    }
    
    public function assertLazyLoadFromInverseSide($products)
    {
        list ($firstProduct, $secondProduct) = $products;

        $firstProductCategories = $firstProduct->getCategories();
        $secondProductCategories = $secondProduct->getCategories();
        
        $this->assertEquals(2, count($firstProductCategories));
        $this->assertEquals(2, count($secondProductCategories));
        
        $this->assertTrue($firstProductCategories[0] === $secondProductCategories[0]);
        $this->assertTrue($firstProductCategories[1] === $secondProductCategories[1]);
        
        $firstCategoryProducts = $firstProductCategories[0]->getProducts();
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

        $this->assertTrue($firstCategoryProducts[0] instanceof ECommerceProduct);
        $this->assertTrue($firstCategoryProducts[1] instanceof ECommerceProduct);
        $this->assertTrue($secondCategoryProducts[0] instanceof ECommerceProduct);
        $this->assertTrue($secondCategoryProducts[1] instanceof ECommerceProduct);
        
        $this->assertCollectionEquals($firstCategoryProducts, $secondCategoryProducts);
    }

    public function assertLazyLoadFromOwningSide($categories)
    {
        list ($firstCategory, $secondCategory) = $categories;

        $firstCategoryProducts = $firstCategory->getProducts();
        $secondCategoryProducts = $secondCategory->getProducts();
        
        $this->assertEquals(2, count($firstCategoryProducts));
        $this->assertEquals(2, count($secondCategoryProducts));
        
        $this->assertTrue($firstCategoryProducts[0] === $secondCategoryProducts[0]);
        $this->assertTrue($firstCategoryProducts[1] === $secondCategoryProducts[1]);
        
        $firstProductCategories = $firstCategoryProducts[0]->getCategories();
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

        $this->assertTrue($firstProductCategories[0] instanceof ECommerceCategory);
        $this->assertTrue($firstProductCategories[1] instanceof ECommerceCategory);
        $this->assertTrue($secondProductCategories[0] instanceof ECommerceCategory);
        $this->assertTrue($secondProductCategories[1] instanceof ECommerceCategory);
        
        $this->assertCollectionEquals($firstProductCategories, $secondProductCategories);
    }
}
