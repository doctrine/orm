<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

enum City: string
{
    case Paris    = 'Paris';
    case Cannes   = 'Cannes';
    case StJulien = 'St Julien';
}
