<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="company_cars")
 */
class CompanyCar
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $brand;

    public function __construct($brand = null) {
        $this->brand = $brand;
    }

    public function getId() {
        return $this->id;
    }

    public function getBrand() {
        return $this->title;
    }
}