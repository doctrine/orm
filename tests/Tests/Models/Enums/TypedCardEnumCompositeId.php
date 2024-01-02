<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;

/** @Entity */
#[Entity]
class TypedCardEnumCompositeId
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="string", enumType=Suit::class)
     */
    #[Id]
    #[Column(type: 'string', enumType: Suit::class)]
    public Suit $suit;

    /**
     * @ORM\Id()
     * @ORM\Column(type="string", enumType=Unit::class)
     */
    #[Id]
    #[Column(type: 'string', enumType: Unit::class)]
    public Unit $unit;
}
