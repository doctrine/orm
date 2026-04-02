<?php

declare(strict_types=1);

namespace App\Entity;

enum Suit: string
{
    case Hearts   = 'hearts';
    case Diamonds = 'diamonds';
    case Clubs    = 'clubs';
    case Spades   = 'spades';
}
