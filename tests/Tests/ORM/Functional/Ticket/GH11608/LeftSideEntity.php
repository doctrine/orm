<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11608;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
class LeftSideEntity
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public int $id;

    #[OneToMany(targetEntity: ConnectingEntity::class, mappedBy: 'validLeftSideMappedByAssociation', indexBy: 'right_side_id')]
    public Collection $validRightSideEntities;

    #[OneToMany(targetEntity: ConnectingEntity::class, mappedBy: 'invalidLeftSideMappedByAssociation', indexBy: 'right_side_id_that_doesnt_exist')]
    public Collection $invalidRightSideEntities;

    #[OneToMany(targetEntity: ConnectingEntity::class, mappedBy: 'validLeftSideMappedByColumn', indexBy: 'arbitrary_value')]
    public Collection $validConnections;

    #[OneToMany(targetEntity: ConnectingEntity::class, mappedBy: 'invalidLeftSideMappedByColumn', indexBy: 'arbitrary_value_that_doesnt_exist')]
    public Collection $invalidConnections;

    public function __construct()
    {
        $this->validRightSideEntities   = new ArrayCollection();
        $this->invalidRightSideEntities = new ArrayCollection();
    }
}
