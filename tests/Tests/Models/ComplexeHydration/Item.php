<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ComplexeHydration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'item')]
#[Entity]
/**
 * @Entity
 * @Table(name="item")
 */
class Item
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
    private int|null $id;

    #[ORM\Column(type: 'string', length: 255)]
    /**
     * @var string
     * @Column(length=255)
     */
    private string|null $libelle;

    #[ORM\OneToMany(targetEntity: ItemSerie::class, mappedBy: 'item', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(name: 'itemSeries', referencedColumnName: 'item_id')]
    /**
     * @var Collection<int, CmsComment>
     * @OneToMany(targetEntity="CmsComment", mappedBy="article")
     * @JoinColumn(name="itemSeries", referencedColumnName="item_id")
     */
    private $itemSeries;

    public function __construct()
    {
        $this->itemSeries = new ArrayCollection();
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
            $itemSeries->setItem($this);
        }

        return $this;
    }

    public function removeItemSeries(ItemSerie $itemSeries): self
    {
        if ($this->itemSeries->removeElement($itemSeries)) {
            // set the owning side to null (unless already changed)
            if ($itemSeries->getItem() === $this) {
                $itemSeries->setItem(null);
            }
        }

        return $this;
    }
}
