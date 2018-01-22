<?php

namespace Doctrine\Tests\Models\IdentityThroughForeignKeyTest;

/**
 * Class ProductColor
 * @package Doctrine\Tests\Models\IdentityThroughForeignKeyTest
 * @Entity()
 * @Table(name="identitythroughforeignkey_product_color")
 */
class ProductColor {

    /**
     * @var integer
     * @Id()
     * @GeneratedValue(strategy="IDENTITY")
     * @Column(name="id", type="integer")
     */
    public $id;

    /**
     * @var Product
     * @ManyToOne(targetEntity="Doctrine\Tests\Models\IdentityThroughForeignKeyTest\Product", inversedBy="colors")
     * @JoinColumn(name="product_id", referencedColumnName="id")
     */
    public $product;
}