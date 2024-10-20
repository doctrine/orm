<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH10661;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class InvalidChildEntity extends InvalidEntity
{
    #[Column(type: 'string')]
    protected int $property2;

    #[Column(type: 'boolean')]
    private string $anotherProperty;
}
