<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\ORM\Mapping\Column;

class FaultySwitch
{
    #[Column(type: 'string')]
    public string $value;

    /**
     * The following line is ignored on psalm and phpstan so that we can test
     * that the mapping is throwing an exception when a non-backed enum is used.
     *
     * @psalm-suppress InvalidArgument
     */
    #[Column(enumType: SwitchStatus::class)]
    public SwitchStatus $status;
}
