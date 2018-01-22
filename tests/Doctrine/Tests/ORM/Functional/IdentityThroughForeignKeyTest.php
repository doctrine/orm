<?php

namespace Doctrine\Tests\ORM\Functional;


use Doctrine\ORM\EntityManager;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Models\IdentityThroughForeignKeyTest\Product;
use Doctrine\Tests\Models\IdentityThroughForeignKeyTest\ProductColor;
use Doctrine\Tests\Models\IdentityThroughForeignKeyTest\ProductSize;
use Doctrine\Tests\Models\IdentityThroughForeignKeyTest\ProductVariant;
use Doctrine\Tests\OrmTestCase;

class IdentityThroughForeignKeyTest extends OrmTestCase {

    /** @var EntityManager */
    protected $em;

    protected function setUp() {
        parent::setUp();
        $this->em = $this->_getTestEntityManager();
    }


    public function testIdentityThroughForeignKeyCollectionPersistence() {
        $product = new Product();

        $color = new ProductColor();
        $color->product = $product;
        $product->colors->add($color);

        $size = new ProductSize();
        $size->product = $product;
        $product->sizes->add($size);

        $variant = new ProductVariant();
        $variant->product = $product;
        $variant->color = $color;
        $variant->size = $size;
        $product->variants->add($variant);

        $this->em->persist($product);
        $this->em->flush();

        /** @var ConnectionMock $conn */
        $conn = $this->em->getConnection();
        print_r($conn->getInserts());
        $this->assertTrue(true);
    }
}