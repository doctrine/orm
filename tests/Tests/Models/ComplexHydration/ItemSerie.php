<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ComplexHydration;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'item_serie')]
#[Entity]
class ItemSerie
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id')]
    private Item $item;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Serie::class)]
    #[ORM\JoinColumn(name: 'serie_id', referencedColumnName: 'id')]
    private Serie $serie;

    #[ORM\Column(type: 'string', length: 50)]
    private string|null $number;

    public function getItem(): item|null
    {
        return $this->item;
    }

    public function setItem(item|null $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function setSerie(serie|null $serie): self
    {
        $this->serie = $serie;

        return $this;
    }

    public function getNumber(): string|null
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }
}
