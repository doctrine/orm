<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH8565;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\DbalTypes\GH8565EmployeePayloadType;

#[Table(name: 'gh8565_employees')]
#[Entity]
class GH8565Employee extends GH8565Person
{
    /** @var GH8565EmployeePayloadType */
    #[Column(type: 'GH8565EmployeePayloadType', nullable: false)]
    public $type;
}
