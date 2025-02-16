<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

enum AccessLevel: int
{
    case Admin  = 1;
    case User   = 2;
    case Guests = 3;
}
