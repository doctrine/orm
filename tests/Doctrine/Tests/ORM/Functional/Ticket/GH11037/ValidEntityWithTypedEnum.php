<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11037;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class ValidEntityWithTypedEnum
{
    #[Id]
    #[Column]
    protected int $id;

    #[Column(type: 'string', enumType: StringEntityStatus::class)]
    protected StringEntityStatus $status1;

    #[Column(type: 'smallint', enumType: IntEntityStatus::class)]
    protected IntEntityStatus $status2;

    #[Column(type: 'string', enumType: StringEntityStatus::class)]
    protected EntityStatus $status3;
}
