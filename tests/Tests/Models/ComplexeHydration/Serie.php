<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ComplexeHydration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'serie')]
#[Entity]
/**
 * @Entity
 * @Table(name="serie")
 */
class Serie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /**
     * @var int
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    /**
     * @var string
     * @Column(length=255)
     */
    private string|null $libelle;

    #[ORM\OneToMany(mappedBy: 'serie', targetEntity: ItemSerie::class, cascade: ['persist'], orphanRemoval: true)]
    /**
     * @var Collection<int, ItemSerie>
     * @OneToMany(targetEntity="ItemSerie", mappedBy="serie")
     */
    private $itemSeries;

    #[ORM\OneToMany(mappedBy: 'serie', targetEntity: SerieImportator::class, cascade: ['persist'], orphanRemoval: true)]
    /**
     * @var Collection<int, SerieImportator>
     * @OneToMany(targetEntity="SerieImportator", mappedBy="serie")
     */
    private $serieImportators;

    public function __construct()
    {
        $this->itemSeries       = new ArrayCollection();
        $this->serieImportators = new ArrayCollection();
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getLibelle(): string|null
    {
        return $this->libelle;
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

    public function addItemSeries(ItemSerie $itemSeries): self
    {
        if (! $this->itemSeries->contains($itemSeries)) {
            $this->itemSeries[] = $itemSeries;
            $itemSeries->setSerie($this);
        }

        return $this;
    }

    public function removeItemSeries(ItemSerie $itemSeries): self
    {
        if ($this->itemSeries->removeElement($itemSeries)) {
            // set the owning side to null (unless already changed)
            if ($itemSeries->getSerie() === $this) {
                $itemSeries->setSerie(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, SerieImportator> */
    public function getSerieImportators(): Collection
    {
        return $this->serieImportators;
    }

    public function addSerieImportator(SerieImportator $serieImportator): static
    {
        if (! $this->serieImportators->contains($serieImportator)) {
            $this->serieImportators->add($serieImportator);
            $serieImportator->setSerie($this);
        }

        return $this;
    }

    public function removeSerieImportator(SerieImportator $serieImportator): static
    {
        if ($this->serieImportators->removeElement($serieImportator)) {
            // set the owning side to null (unless already changed)
            if ($serieImportator->getSerie() === $this) {
                $serieImportator->setSerie(null);
            }
        }

        return $this;
    }
}
