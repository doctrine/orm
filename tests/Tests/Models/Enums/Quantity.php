<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;

#[Embeddable]
class Quantity
{
    #[Column(type: 'integer')]
    public int $value;

    #[Column(type: 'string', enumType: Unit::class)]
    public Unit $unit;
}
