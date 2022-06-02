<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC964;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * @Entity
 */
class DDC964Address
{
    /**
     * @GeneratedValue
     * @Id
     * @Column(type="integer")
     */
    private int $id;

    public function __construct(
        /**
         * @Column
         */
        private ?string $zip = null,
        /**
         * @Column
         */
        private ?string $country = null,
        /**
         * @Column
         */
        private ?string $city = null,
        /**
         * @Column
         */
        private ?string $street = null
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }
}
