<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ComplexHydration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'serie')]
#[Entity]
class Serie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string|null $libelle;

    /**
     * @var Collection<int, ItemSerie>
     * @OneToMany(targetEntity="ItemSerie", mappedBy="serie")
     */
    #[ORM\OneToMany(mappedBy: 'serie', targetEntity: ItemSerie::class, cascade: ['persist'], orphanRemoval: true)]
    private $itemSeries;

    /**
     * @var Collection<int, SerieImportator>
     * @OneToMany(targetEntity="SerieImportator", mappedBy="serie")
     */
    #[ORM\OneToMany(mappedBy: 'serie', targetEntity: SerieImportator::class, cascade: ['persist'], orphanRemoval: true)]
    private $serieImportators;

    public function __construct()
    {
        $this->itemSeries = new ArrayCollection();
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    /** @return Collection<int, ItemSerie> */
    public function getItemSeries(): Collection
    {
        return $this->itemSeries;
    }
}
