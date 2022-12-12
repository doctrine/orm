<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class CardWithDefault
{
    #[Id]
    #[Column]
    public string $id;

    #[Column(options: ['default' => Suit::Hearts])]
    public Suit $suit;
}
