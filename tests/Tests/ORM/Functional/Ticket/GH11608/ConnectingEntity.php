<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11608;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class ConnectingEntity
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public int $id;

    #[ManyToOne(inversedBy: 'validRightSideEntities')]
    #[JoinColumn(nullable: false)]
    public LeftSideEntity $validLeftSideMappedByAssociation;

    #[ManyToOne(inversedBy: 'invalidRightSideEntities')]
    #[JoinColumn(nullable: false)]
    public LeftSideEntity $invalidLeftSideMappedByAssociation;

    #[ManyToOne(inversedBy: 'validConnections')]
    #[JoinColumn(nullable: false)]
    public LeftSideEntity $validLeftSideMappedByColumn;

    #[ManyToOne(inversedBy: 'invalidConnections')]
    #[JoinColumn(nullable: false)]
    public LeftSideEntity $invalidLeftSideMappedByColumn;

    #[ManyToOne(inversedBy: 'leftSideEntities')]
    #[JoinColumn(name: 'right_side_id', nullable: false)]
    public RightSideEntity $rightSide;

    #[Column(type: 'text', nullable: false)]
    public string $arbitraryValue;
}
