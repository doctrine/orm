<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OneToOneInverseSideWithAssociativeIdLoad;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'one_to_one_inverse_side_assoc_id_load_inverse')]
class InverseSide
{
    /** Associative id (owning identifier) */
    #[Id]
    #[OneToOne(targetEntity: InverseSideIdTarget::class, inversedBy: 'inverseSide')]
    #[JoinColumn(nullable: false, name: 'associativeId')]
    public InverseSideIdTarget $associativeId;

    #[OneToOne(targetEntity: OwningSide::class, mappedBy: 'inverse')]
    public OwningSide $owning;
}
