<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;

#[Entity]
#[Cache(usage: 'READ_ONLY')]
class CardReadOnlyCache
{
    #[Id, Column]
    public string $id;

    #[Column]
    public Suit $suit;
}
