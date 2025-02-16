<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OneToOneInverseSideWithAssociativeIdLoad;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'one_to_one_inverse_side_assoc_id_load_inverse_id_target')]
class InverseSideIdTarget
{
    #[Id]
    #[Column(type: 'string', length: 255)]
    #[GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[OneToOne(targetEntity: InverseSide::class, mappedBy: 'associativeId')]
    public InverseSide $inverseSide;
}
