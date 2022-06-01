<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="company_cars")
 */
class CompanyCar
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private int $id;

    public function __construct(
        /**
         * @Column(type="string", length=50)
         */
        private ?string $brand = null
    )
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }
}
