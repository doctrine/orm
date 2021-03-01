<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

/**
 * @Entity
 * @Table(name="company_cars")
 */
class CompanyCar
{
    /**
     * @var int
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @Column(type="string", length=50)
     */
    private $brand;

    public function __construct($brand = null)
    {
        $this->brand = $brand;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getBrand()
    {
        return $this->title;
    }
}
