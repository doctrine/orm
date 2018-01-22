<?php

namespace Doctrine\Tests\Models\IdentityThroughForeignKeyTest;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ProductSize
 * @package Doctrine\Tests\Models\IdentityThroughForeignKeyTest
 * @Entity()
 * @Table(name="identitythroughforeignkey_product_size")
 */
class ProductSize {

    /**
     * @var integer
     * @Id()
     * @GeneratedValue(strategy="IDENTITY")
     * @Column(name="id", type="integer")
     */
    public $id;

    /**
     * @var Product
     * @ManyToOne(targetEntity="Doctrine\Tests\Models\IdentityThroughForeignKeyTest\Product", inversedBy="sizes")
     * @JoinColumn(name="product_id", referencedColumnName="id")
     */
    public $product;
}