<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11037;

enum IntEntityStatus: int
{
    case ACTIVE   = 0;
    case INACTIVE = 1;
}
