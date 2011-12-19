<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests a unidirectional many-to-many association mapping (without inheritance).
 * Inverse side is not present.
 */
class ManyToManyUnidirectionalAssociationTest extends AbstractManyToManyAssociationTestCase
{
    protected $_firstField = 'cart_id';
    protected $_secondField = 'product_id';
    protected $_table = 'ecommerce_carts_products';
    private $firstProduct;
    private $secondProduct;
    private $firstCart;
    private $secondCart;

    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
        $this->firstProduct = new ECommerceProduct();
        $this->firstProduct->setName('Doctrine 1.x Manual');
        $this->secondProduct = new ECommerceProduct();
        $this->secondProduct->setName('Doctrine 2.x Manual');
        $this->firstCart = new ECommerceCart();
        $this->secondCart = new ECommerceCart();
    }

    public function testSavesAManyToManyAssociationWithCascadeSaveSet()
    {
        $this->firstCart->addProduct($this->firstProduct);
        $this->firstCart->addProduct($this->secondProduct);
        $this->_em->persist($this->firstCart);
        $this->_em->flush();

        $this->assertForeignKeysContain($this->firstCart->getId(), $this->firstProduct->getId());
        $this->assertForeignKeysContain($this->firstCart->getId(), $this->secondProduct->getId());
    }

    public function testRemovesAManyToManyAssociation()
    {
        $this->firstCart->addProduct($this->firstProduct);
        $this->firstCart->addProduct($this->secondProduct);
        $this->_em->persist($this->firstCart);
        $this->firstCart->removeProduct($this->firstProduct);

        $this->_em->flush();

        $this->assertForeignKeysNotContain($this->firstCart->getId(), $this->firstProduct->getId());
        $this->assertForeignKeysContain($this->firstCart->getId(), $this->secondProduct->getId());
    }

    public function testEagerLoad()
    {
        $this->_createFixture();

        $query = $this->_em->createQuery('SELECT c, p FROM Doctrine\Tests\Models\ECommerce\ECommerceCart c LEFT JOIN c.products p ORDER BY c.id, p.id');
        $result = $query->getResult();
        $firstCart = $result[0];
        $products = $firstCart->getProducts();
        $secondCart = $result[1];

        $this->assertInstanceOf('Doctrine\Tests\Models\ECommerce\ECommerceProduct', $products[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\ECommerce\ECommerceProduct', $products[1]);
        $this->assertCollectionEquals($products, $secondCart->getProducts());
        //$this->assertEquals("Doctrine 1.x Manual", $products[0]->getName());
        //$this->assertEquals("Doctrine 2.x Manual", $products[1]->getName());
    }

    public function testLazyLoadsCollection()
    {
        $this->_createFixture();
        $metadata = $this->_em->getClassMetadata('Doctrine\Tests\Models\ECommerce\ECommerceCart');
        $metadata->associationMappings['products']['fetch'] = ClassMetadata::FETCH_LAZY;

        $query = $this->_em->createQuery('SELECT c FROM Doctrine\Tests\Models\ECommerce\ECommerceCart c');
        $result = $query->getResult();
        $firstCart = $result[0];
        $products = $firstCart->getProducts();
        $secondCart = $result[1];

        $this->assertInstanceOf('Doctrine\Tests\Models\ECommerce\ECommerceProduct', $products[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\ECommerce\ECommerceProduct', $products[1]);
        $this->assertCollectionEquals($products, $secondCart->getProducts());
    }

    private function _createFixture()
    {
        $this->firstCart->addProduct($this->firstProduct);
        $this->firstCart->addProduct($this->secondProduct);
        $this->secondCart->addProduct($this->firstProduct);
        $this->secondCart->addProduct($this->secondProduct);
        $this->_em->persist($this->firstCart);
        $this->_em->persist($this->secondCart);

        $this->_em->flush();
        $this->_em->clear();
    }
}
