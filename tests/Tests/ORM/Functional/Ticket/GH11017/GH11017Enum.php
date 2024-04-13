<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11017;

enum GH11017Enum: string
{
    case FIRST  = 'first';
    case SECOND = 'second';
}
