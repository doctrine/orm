<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DataTransferObjects;

use Doctrine\Tests\Models\Enums\Suit;

final class DtoWithEnum
{
    /** @var Suit|null */
    public $suit;

    public function __construct(?Suit $suit)
    {
        $this->suit = $suit;
    }
}
