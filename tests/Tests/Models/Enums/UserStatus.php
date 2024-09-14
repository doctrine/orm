<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

enum UserStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
}
