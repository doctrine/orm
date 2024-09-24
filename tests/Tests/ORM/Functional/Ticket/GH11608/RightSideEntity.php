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
class RightSideEntity
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public int $id;

    #[OneToMany(targetEntity: ConnectingEntity::class, mappedBy: 'rightSide', indexBy: 'id')]
    public Collection $leftSideEntities;

    public function __construct()
    {
        $this->leftSideEntities = new ArrayCollection();
    }
}
