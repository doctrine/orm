<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

enum GH10063Enum: string
{
    case Red = 'red';
    case Green = 'green';
    case Blue = 'blue';
}
