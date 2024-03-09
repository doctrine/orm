<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ComplexeHydration;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'item_serie')]
#[Entity]
/**
 * @Entity
 * @Table(name="item_serie")
 */
class ItemSerie
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'itemSeries')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id')]
    /**
     * @var int
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    private Item $item;


    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Serie::class, inversedBy: 'itemSeries')]
    #[ORM\JoinColumn(name: 'serie_id', referencedColumnName: 'id')]
    /**
     * @var serie
     * @ManyToOne(targetEntity="Serie", inversedBy="itemSeries")
     * @JoinColumn(name="serie_id", referencedColumnName="id")
     */
    private Serie $serie;

    #[ORM\Column(type: 'string', length: 50)]
    /**
     * @var string
     * @Column(length=50)
     */
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

    public function getSerie(): serie|null
    {
        return $this->serie;
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
