<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\ORM\Mapping\Column;

class FaultySwitch
{
    #[Column(type: 'string')]
    public string $value;

    #[Column(enumType: SwitchStatus::class)]
    public SwitchStatus $status;
}
