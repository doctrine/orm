<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

enum BookGenre: string
{
    case FICTION     = 'fiction';
    case NON_FICTION = 'non fiction';
}
