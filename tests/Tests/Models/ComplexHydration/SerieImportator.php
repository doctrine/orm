<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ComplexHydration;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'serie_importator')]
#[Entity]
class SerieImportator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int|null $id = null;

    #[ORM\ManyToOne(inversedBy: 'serieImportators')]
    #[ORM\JoinColumn(nullable: false)]
    private Serie|null $serie = null;

    /**
     * @var string
     * @Column(length=255)
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string|null $libelle;

    public function setSerie(Serie|null $serie): static
    {
        $this->serie = $serie;

        return $this;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }
}
