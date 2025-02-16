<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH8565;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\DbalTypes\GH8565ManagerPayloadType;

#[Table(name: 'gh8565_managers')]
#[Entity]
class GH8565Manager extends GH8565Person
{
    /** @var GH8565ManagerPayloadType */
    #[Column(type: 'GH8565ManagerPayloadType', nullable: false)]
    public $type;
}
