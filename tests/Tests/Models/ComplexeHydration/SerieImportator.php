<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ComplexeHydration;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'serie_importator')]
#[Entity]
/**
 * @Entity
 * @Table(name="serie_importator")
 */
class SerieImportator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /**
     * @var int
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    private int|null $id = null;

    #[ORM\ManyToOne(inversedBy: 'serieImportators')]
    #[ORM\JoinColumn(nullable: false)]
    /**
     * @var Serie
     * @ManyToOne(targetEntity="Serie", inversedBy="serieImportators")
     * @JoinColumn(nullable= false)
     */
    private Serie|null $serie = null;

    #[ORM\Column(type: 'string', length: 255)]
    /**
     * @var string
     * @Column(length=255)
     */
    private string|null $libelle;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getSerie(): Serie|null
    {
        return $this->serie;
    }

    public function setSerie(Serie|null $serie): static
    {
        $this->serie = $serie;

        return $this;
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
}
