<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

enum Unit: string
{
    case Gram  = 'g';
    case Meter = 'm';
}
