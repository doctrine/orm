<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH12799;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'gh_12799_single_item')]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'discr', type: 'string')]
#[DiscriminatorMap(['a' => SingleItemA::class, 'b' => SingleItemB::class])]
abstract class SingleItem
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public int $id;
}
