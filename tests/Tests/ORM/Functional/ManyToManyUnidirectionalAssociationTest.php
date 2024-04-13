<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;

/**
 * Tests a unidirectional many-to-many association mapping (without inheritance).
 * Inverse side is not present.
 */
class ManyToManyUnidirectionalAssociationTest extends AbstractManyToManyAssociationTestCase
{
    /** @var string */
    protected $firstField = 'cart_id';

    /** @var string */
    protected $secondField = 'product_id';

    /** @var string */
    protected $table = 'ecommerce_carts_products';

    /** @var ECommerceProduct */
    private $firstProduct;

    /** @var ECommerceProduct */
    private $secondProduct;

    /** @var ECommerceCart */
    private $firstCart;

    /** @var ECommerceCart */
    private $secondCart;

    protected function setUp(): void
    {
        $this->useModelSet('ecommerce');

        parent::setUp();

        $this->firstProduct = new ECommerceProduct();
        $this->firstProduct->setName('Doctrine 1.x Manual');
        $this->secondProduct = new ECommerceProduct();
        $this->secondProduct->setName('Doctrine 2.x Manual');
        $this->firstCart  = new ECommerceCart();
        $this->secondCart = new ECommerceCart();
    }

    public function testSavesAManyToManyAssociationWithCascadeSaveSet(): void
    {
        $this->firstCart->addProduct($this->firstProduct);
        $this->firstCart->addProduct($this->secondProduct);
        $this->_em->persist($this->firstCart);
        $this->_em->flush();

        $this->assertForeignKeysContain($this->firstCart->getId(), $this->firstProduct->getId());
        $this->assertForeignKeysContain($this->firstCart->getId(), $this->secondProduct->getId());
    }

    public function testRemovesAManyToManyAssociation(): void
    {
        $this->firstCart->addProduct($this->firstProduct);
        $this->firstCart->addProduct($this->secondProduct);
        $this->_em->persist($this->firstCart);
        $this->firstCart->removeProduct($this->firstProduct);

        $this->_em->flush();

        $this->assertForeignKeysNotContain($this->firstCart->getId(), $this->firstProduct->getId());
        $this->assertForeignKeysContain($this->firstCart->getId(), $this->secondProduct->getId());
    }

    public function testEagerLoad(): void
    {
        $this->createFixture();

        $query      = $this->_em->createQuery('SELECT c, p FROM Doctrine\Tests\Models\ECommerce\ECommerceCart c LEFT JOIN c.products p ORDER BY c.id, p.id');
        $result     = $query->getResult();
        $firstCart  = $result[0];
        $products   = $firstCart->getProducts();
        $secondCart = $result[1];

        self::assertInstanceOf(ECommerceProduct::class, $products[0]);
        self::assertInstanceOf(ECommerceProduct::class, $products[1]);
        $this->assertCollectionEquals($products, $secondCart->getProducts());
        //$this->assertEquals("Doctrine 1.x Manual", $products[0]->getName());
        //$this->assertEquals("Doctrine 2.x Manual", $products[1]->getName());
    }

    public function testLazyLoadsCollection(): void
    {
        $this->createFixture();
        $metadata                                           = $this->_em->getClassMetadata(ECommerceCart::class);
        $metadata->associationMappings['products']['fetch'] = ClassMetadata::FETCH_LAZY;

        $query      = $this->_em->createQuery('SELECT c FROM Doctrine\Tests\Models\ECommerce\ECommerceCart c');
        $result     = $query->getResult();
        $firstCart  = $result[0];
        $products   = $firstCart->getProducts();
        $secondCart = $result[1];

        self::assertInstanceOf(ECommerceProduct::class, $products[0]);
        self::assertInstanceOf(ECommerceProduct::class, $products[1]);
        $this->assertCollectionEquals($products, $secondCart->getProducts());
    }

    private function createFixture(): void
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
