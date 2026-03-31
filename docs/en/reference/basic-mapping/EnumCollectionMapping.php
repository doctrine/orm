<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    /** @var list<Suit> */
    #[ORM\Column(type: 'json', enumType: Suit::class)]
    private array $favouriteSuits = [];

    /** @var list<Suit> */
    #[ORM\Column(type: 'simple_array', enumType: Suit::class)]
    private array $allowedSuits = [];
}
