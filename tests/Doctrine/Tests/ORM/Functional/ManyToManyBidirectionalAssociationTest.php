<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceCategory;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests a bidirectional many-to-many association mapping (without inheritance).
 * Inverse side is not present.
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
        $this->secondProduct = new ECommerceProduct();
        $this->firstCategory = new ECommerceCategory();
        $this->firstCategory->setName("Business");
        $this->secondCategory = new ECommerceCategory();
        $this->secondCategory->setName("Home");
    }

    public function testSavesAManyToManyAssociationWithCascadeSaveSet()
    {
        $this->firstProduct->addCategory($this->firstCategory);
        $this->firstProduct->addCategory($this->secondCategory);
        $this->_em->save($this->firstProduct);
        $this->_em->flush();
        
        $this->assertForeignKeysContain($this->firstProduct->getId(),
                                   $this->firstCategory->getId());
        $this->assertForeignKeysContain($this->firstProduct->getId(),
                                   $this->secondCategory->getId());
    }

    public function testRemovesAManyToManyAssociation()
    {
        $this->firstProduct->addCategory($this->firstCategory);
        $this->firstProduct->addCategory($this->secondCategory);
        $this->_em->save($this->firstProduct);
        $this->firstProduct->removeCategory($this->firstCategory);

        $this->_em->flush();

        $this->assertForeignKeysNotContain($this->firstProduct->getId(),
                                   $this->firstCategory->getId());
        $this->assertForeignKeysContain($this->firstProduct->getId(),
                                   $this->secondCategory->getId());
    }

    public function testEagerLoadsInverseSide()
    {
        $this->_createLoadingFixture();
        list ($firstProduct, $secondProduct) = $this->_findProducts();
        $categories = $firstProduct->getCategories();
        
        $this->assertTrue($categories[0] instanceof ECommerceCategory);
        $this->assertTrue($categories[1] instanceof ECommerceCategory);
        $this->assertCollectionEquals($categories, $secondProduct->getCategories());
    }

    public function testEagerLoadsOwningSide()
    {
        $this->_createLoadingFixture();
        list ($firstProduct, $secondProduct) = $this->_findProducts();
        
        $this->assertEquals(2, count($firstProduct->getCategories()));
        $this->assertEquals(2, count($secondProduct->getCategories()));
        
        $categories = $firstProduct->getCategories();        
        $firstCategoryProducts = $categories[0]->getProducts();
        $secondCategoryProducts = $categories[1]->getProducts();
        
        $this->assertEquals(2, count($firstCategoryProducts));
        $this->assertEquals(2, count($secondCategoryProducts));

        $this->assertTrue($firstCategoryProducts[0] instanceof ECommerceProduct);
        $this->assertTrue($firstCategoryProducts[1] instanceof ECommerceProduct);
        $this->assertTrue($secondCategoryProducts[0] instanceof ECommerceProduct);
        $this->assertTrue($secondCategoryProducts[1] instanceof ECommerceProduct);
        
        $this->assertCollectionEquals($firstCategoryProducts, $secondCategoryProducts);
    }

    protected function _createLoadingFixture()
    {
        $this->firstProduct->addCategory($this->firstCategory);
        $this->firstProduct->addCategory($this->secondCategory);
        $this->secondProduct->addCategory($this->firstCategory);
        $this->secondProduct->addCategory($this->secondCategory);
        $this->_em->save($this->firstProduct);
        $this->_em->save($this->secondProduct);
        
        $this->_em->flush();
        $this->_em->clear();
    }

    protected function _findProducts()
    {
        $query = $this->_em->createQuery('SELECT p, c FROM Doctrine\Tests\Models\ECommerce\ECommerceProduct p LEFT JOIN p.categories c ORDER BY p.id, c.id');
        return $query->getResultList();
    }
    
    /* TODO: not yet implemented
    public function testLazyLoad() {
        
    }*/
}
