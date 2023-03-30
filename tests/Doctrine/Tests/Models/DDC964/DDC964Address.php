<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC964;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class DDC964Address
{
    #[GeneratedValue]
    #[Id]
    #[Column(type: 'integer')]
    private int $id;

    public function __construct(
        #[Column]
        private string|null $zip = null,
        #[Column]
        private string|null $country = null,
        #[Column]
        private string|null $city = null,
        #[Column]
        private string|null $street = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCountry(): string|null
    {
        return $this->country;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getZip(): string|null
    {
        return $this->zip;
    }

    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }

    public function getCity(): string|null
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getStreet(): string|null
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }
}
