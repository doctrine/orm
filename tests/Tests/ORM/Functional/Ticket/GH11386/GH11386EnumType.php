<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11386;

enum GH11386EnumType: int
{
    case MALE   = 1;
    case FEMALE = 2;
}
