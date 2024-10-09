<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OneToOneInverseSideWithAssociativeIdLoad;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'one_to_one_inverse_side_assoc_id_load_owning')]
class OwningSide
{
    #[Id]
    #[Column(type: 'string', length: 255)]
    #[GeneratedValue(strategy: 'NONE')]
    public string $id;

    /** Owning side */
    #[OneToOne(targetEntity: InverseSide::class, inversedBy: 'owning')]
    #[JoinColumn(name: 'inverse', referencedColumnName: 'associativeId')]
    public InverseSide $inverse;
}
