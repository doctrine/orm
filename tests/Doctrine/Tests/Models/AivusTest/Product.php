<?php

namespace Doctrine\Tests\Models\AivusTest;

/**
 * @Entity
 * @Table(name="products")
 */
class Product
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     *
     * @var int
     */
    private $id;
}
