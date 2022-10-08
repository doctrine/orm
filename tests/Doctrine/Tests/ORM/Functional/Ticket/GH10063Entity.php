<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/** @Entity */
class GH10063Entity
{
    /**
     * @Column
     * @Id
     * @GeneratedValue
     */
    public int $id;

    /** @Column(type="simple_array", length=255, nullable=true, enumType=GH10063Enum::class) */
    private array $colors = [];

    /**
     * @param array<int, GH10063Enum> $colors
     */
    public function setColors(array $colors): self
    {
        $this->colors = $colors;

        return $this;
    }

    /**
     * @return array<int, GH10063Enum>
     */
    public function getColors(): array
    {
        return $this->colors;
    }
}
