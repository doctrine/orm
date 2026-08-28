<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH10852;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class GH10852Hand
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=GH10852Card::class)
     * @ORM\JoinColumn(referencedColumnName="suit")
     *
     * @var GH10852Card
     */
    public $card;

    public function __construct(int $id, GH10852Card $card)
    {
        $this->id   = $id;
        $this->card = $card;
    }
}
