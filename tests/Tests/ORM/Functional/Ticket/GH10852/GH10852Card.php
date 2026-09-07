<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH10852;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class GH10852Card
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", enumType=GH10852Suit::class)
     *
     * @var GH10852Suit
     */
    public $suit;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $label;

    public function __construct(GH10852Suit $suit, string $label)
    {
        $this->suit  = $suit;
        $this->label = $label;
    }
}
