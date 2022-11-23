<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class AssocToCardReadOnlyCache
{
    #[Id, Column]
    public string $id;

    #[ManyToOne]
    public CardReadOnlyCache $card;
}
