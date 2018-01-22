<?php

namespace Doctrine\Tests\Models\IdentityThroughForeignKeyTest;

/**
 * Class ProductVariant
 * @package Doctrine\Tests\Models\IdentityThroughForeignKeyTest
 * @Entity()
 * @Table(name="identitythroughforeignkey_product_variant")
 */
class ProductVariant {

    /**
     * @var Product
     * @Id()
     * @ManyToOne(targetEntity="Doctrine\Tests\Models\IdentityThroughForeignKeyTest\Product", inversedBy="variants")
     * @JoinColumn(name="product_id", referencedColumnName="id")
     */
    public $product;

    /**
     * @var ProductColor
     * @Id()
     * @ManyToOne(targetEntity="Doctrine\Tests\Models\IdentityThroughForeignKeyTest\ProductColor")
     * @JoinColumn(name="color_id", referencedColumnName="id")
     */
    public $color;

    /**
     * @var ProductSize
     * @Id()
     * @ManyToOne(targetEntity="Doctrine\Tests\Models\IdentityThroughForeignKeyTest\ProductSize")
     * @JoinColumn(name="size_id", referencedColumnName="id")
     */
    public $size;
}