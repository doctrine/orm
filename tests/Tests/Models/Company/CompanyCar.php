<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'company_cars')]
#[Entity]
class CompanyCar
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private int $id;

    public function __construct(
        #[Column(type: 'string', length: 50)]
        private string|null $brand = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBrand(): string|null
    {
        return $this->brand;
    }
}
