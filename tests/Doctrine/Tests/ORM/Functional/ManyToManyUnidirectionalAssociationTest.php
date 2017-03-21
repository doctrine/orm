<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\FetchMode;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Tests a unidirectional many-to-many association mapping (without inheritance).
 * Inverse side is not present.
 */
class ManyToManyUnidirectionalAssociationTest extends AbstractManyToManyAssociationTestCase
{
    protected $firstField = 'cart_id';
    protected $secondField = 'product_id';
    protected $table = 'ecommerce_carts_products';
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

        $this->em->persist($this->firstCart);
        $this->em->flush();

        self::assertForeignKeysContain($this->firstCart->getId(), $this->firstProduct->getId());
        self::assertForeignKeysContain($this->firstCart->getId(), $this->secondProduct->getId());
    }

    public function testRemovesAManyToManyAssociation()
    {
        $this->firstCart->addProduct($this->firstProduct);
        $this->firstCart->addProduct($this->secondProduct);
        $this->em->persist($this->firstCart);
        $this->firstCart->removeProduct($this->firstProduct);

        $this->em->flush();

        self::assertForeignKeysNotContain($this->firstCart->getId(), $this->firstProduct->getId());
        self::assertForeignKeysContain($this->firstCart->getId(), $this->secondProduct->getId());
    }

    public function testEagerLoad()
    {
        $this->createFixture();

        $query = $this->em->createQuery('SELECT c, p FROM Doctrine\Tests\Models\ECommerce\ECommerceCart c LEFT JOIN c.products p ORDER BY c.id, p.id');
        $result = $query->getResult();
        $firstCart = $result[0];
        $products = $firstCart->getProducts();
        $secondCart = $result[1];

        self::assertInstanceOf(ECommerceProduct::class, $products[0]);
        self::assertInstanceOf(ECommerceProduct::class, $products[1]);
        self::assertCollectionEquals($products, $secondCart->getProducts());
        //self::assertEquals("Doctrine 1.x Manual", $products[0]->getName());
        //self::assertEquals("Doctrine 2.x Manual", $products[1]->getName());
    }

    public function testLazyLoadsCollection()
    {
        $this->createFixture();
        $metadata = $this->em->getClassMetadata(ECommerceCart::class);
        $metadata->getProperty('products')->setFetchMode(FetchMode::LAZY);

        $query = $this->em->createQuery('SELECT c FROM Doctrine\Tests\Models\ECommerce\ECommerceCart c');
        $result = $query->getResult();
        $firstCart = $result[0];
        $products = $firstCart->getProducts();
        $secondCart = $result[1];

        self::assertInstanceOf(ECommerceProduct::class, $products[0]);
        self::assertInstanceOf(ECommerceProduct::class, $products[1]);
        self::assertCollectionEquals($products, $secondCart->getProducts());
    }

    private function createFixture()
    {
        $this->firstCart->addProduct($this->firstProduct);
        $this->firstCart->addProduct($this->secondProduct);
        $this->secondCart->addProduct($this->firstProduct);
        $this->secondCart->addProduct($this->secondProduct);
        $this->em->persist($this->firstCart);
        $this->em->persist($this->secondCart);

        $this->em->flush();
        $this->em->clear();
    }
}
