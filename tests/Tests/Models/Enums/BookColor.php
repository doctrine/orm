<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

enum BookColor: string
{
    case RED  = 'red';
    case BLUE = 'blue';
}
