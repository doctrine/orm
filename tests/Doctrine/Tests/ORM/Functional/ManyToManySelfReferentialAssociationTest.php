<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests a self referential many-to-many association mapping (from a model to the same model, without inheritance).
 * For simplicity the relation duplicates entries in the association table
 * to remain symmetrical.
 */
class ManyToManySelfReferentialAssociationTest extends AbstractManyToManyAssociationTestCase
{
    protected $_firstField = 'product_id';
    protected $_secondField = 'related_id';
    protected $_table = 'ecommerce_products_related';
    private $firstProduct;
    private $secondProduct;
    private $firstRelated;
    private $secondRelated;

    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
        $this->firstProduct = new ECommerceProduct();
        $this->secondProduct = new ECommerceProduct();
        $this->firstRelated = new ECommerceProduct();
        $this->firstRelated->setName("Business");
        $this->secondRelated = new ECommerceProduct();
        $this->secondRelated->setName("Home");
    }

    public function testSavesAManyToManyAssociationWithCascadeSaveSet()
    {
        $this->firstProduct->addRelated($this->firstRelated);
        $this->firstProduct->addRelated($this->secondRelated);
        $this->_em->persist($this->firstProduct);
        $this->_em->flush();

        $this->assertForeignKeysContain($this->firstProduct->getId(),
                                   $this->firstRelated->getId());
        $this->assertForeignKeysContain($this->firstProduct->getId(),
                                   $this->secondRelated->getId());
    }

    public function testRemovesAManyToManyAssociation()
    {
        $this->firstProduct->addRelated($this->firstRelated);
        $this->firstProduct->addRelated($this->secondRelated);
        $this->_em->persist($this->firstProduct);
        $this->firstProduct->removeRelated($this->firstRelated);

        $this->_em->flush();

        $this->assertForeignKeysNotContain($this->firstProduct->getId(),
                                   $this->firstRelated->getId());
        $this->assertForeignKeysContain($this->firstProduct->getId(),
                                   $this->secondRelated->getId());
    }

    public function testEagerLoadsOwningSide()
    {
        $this->_createLoadingFixture();
        $products = $this->_findProducts();
        $this->assertLoadingOfOwningSide($products);
    }

    public function testLazyLoadsOwningSide()
    {
        $this->_createLoadingFixture();

        $metadata = $this->_em->getClassMetadata('Doctrine\Tests\Models\ECommerce\ECommerceProduct');
        $metadata->associationMappings['related']['fetch'] = ClassMetadata::FETCH_LAZY;

        $query = $this->_em->createQuery('SELECT p FROM Doctrine\Tests\Models\ECommerce\ECommerceProduct p');
        $products = $query->getResult();
        $this->assertLoadingOfOwningSide($products);
    }

    public function assertLoadingOfOwningSide($products)
    {
        list ($firstProduct, $secondProduct) = $products;
        $this->assertEquals(2, count($firstProduct->getRelated()));
        $this->assertEquals(2, count($secondProduct->getRelated()));

        $categories = $firstProduct->getRelated();
        $firstRelatedBy = $categories[0]->getRelated();
        $secondRelatedBy = $categories[1]->getRelated();

        $this->assertEquals(2, count($firstRelatedBy));
        $this->assertEquals(2, count($secondRelatedBy));

        $this->assertInstanceOf('Doctrine\Tests\Models\ECommerce\ECommerceProduct', $firstRelatedBy[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\ECommerce\ECommerceProduct', $firstRelatedBy[1]);
        $this->assertInstanceOf('Doctrine\Tests\Models\ECommerce\ECommerceProduct', $secondRelatedBy[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\ECommerce\ECommerceProduct', $secondRelatedBy[1]);

        $this->assertCollectionEquals($firstRelatedBy, $secondRelatedBy);
    }

    protected function _createLoadingFixture()
    {
        $this->firstProduct->addRelated($this->firstRelated);
        $this->firstProduct->addRelated($this->secondRelated);
        $this->secondProduct->addRelated($this->firstRelated);
        $this->secondProduct->addRelated($this->secondRelated);
        $this->_em->persist($this->firstProduct);
        $this->_em->persist($this->secondProduct);

        $this->_em->flush();
        $this->_em->clear();
    }

    protected function _findProducts()
    {
        $query = $this->_em->createQuery('SELECT p, r FROM Doctrine\Tests\Models\ECommerce\ECommerceProduct p LEFT JOIN p.related r ORDER BY p.id, r.id');
        return $query->getResult();
    }
}
