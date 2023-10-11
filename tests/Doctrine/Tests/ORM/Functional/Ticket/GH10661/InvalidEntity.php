<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH10661;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;

#[Entity]
#[InheritanceType('JOINED')]
#[DiscriminatorColumn(name: 'discr', type: 'string', length: 255)]
#[DiscriminatorMap(['root' => 'InvalidEntity', 'child' => 'InvalidChildEntity'])]
class InvalidEntity
{
    /** @var int */
    #[Id]
    #[Column]
    protected $key;

    #[Column(type: 'decimal')]
    protected float $property1;
}
