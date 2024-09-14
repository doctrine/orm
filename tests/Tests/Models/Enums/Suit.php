<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

enum Suit: string
{
    case Hearts   = 'H';
    case Diamonds = 'D';
    case Clubs    = 'C';
    case Spades   = 'S';
}
