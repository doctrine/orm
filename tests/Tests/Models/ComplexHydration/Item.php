<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ComplexHydration;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'item')]
#[Entity]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int|null $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string|null $libelle;

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
