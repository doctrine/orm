<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11149;

enum LocaleCode: string
{
    case Dutch = 'nl_NL';
    case French = 'fr_FR';
    case German = 'de_DE';
}
