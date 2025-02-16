<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CompositeKeyRelations;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class CustomerClass
{
    #[Id]
    #[Column(type: 'string')]
    public string $companyCode;

    #[Id]
    #[Column(type: 'string')]
    public string $code;

    #[Column(type: 'string')]
    public string $name;
}
