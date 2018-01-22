<?php

namespace Doctrine\Tests\Models\IdentityThroughForeignKeyTest;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Product
 * @package Doctrine\Tests\Models\IdentityThroughForeignKeyTest
 * @Entity()
 * @Table(name="identitythroughforeignkey_product")
 */
class Product {

    /**
     * @var integer
     * @Id()
     * @GeneratedValue(strategy="IDENTITY")
     * @Column(name="id")
     */
    public $id;

    /**
     * @var Collection|ProductColor[]
     * @OneToMany(targetEntity="Doctrine\Tests\Models\IdentityThroughForeignKeyTest\ProductColor", mappedBy="product", cascade={"persist"})
     */
    public $colors;

    /**
     * @var Collection|ProductColor[]
     * @OneToMany(targetEntity="Doctrine\Tests\Models\IdentityThroughForeignKeyTest\ProductSize", mappedBy="product", cascade={"persist"})
     */
    public $sizes;

    /**
     * @var Collection|ProductVariant[]
     * @OneToMany(targetEntity="Doctrine\Tests\Models\IdentityThroughForeignKeyTest\ProductVariant", mappedBy="product", cascade={"persist"})
     */
    public $variants;

    public function __construct() {
        $this->colors = new ArrayCollection();
        $this->sizes = new ArrayCollection();
        $this->variants = new ArrayCollection();
    }
}