<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11037;

enum StringEntityStatus: string
{
    case ACTIVE   = 'active';
    case INACTIVE = 'inactive';
}
