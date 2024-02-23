<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Jedi;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'jedi_knights')]
class JediKnight
{
    #[ORM\Column]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\Column(length: 100)]
    public string $name;

    #[ORM\OneToOne(inversedBy: 'padawan')]
    #[ORM\JoinColumn(name: 'master_id')]
    public self|null $master = null;

    #[ORM\OneToOne(mappedBy: 'master')]
    #[ORM\JoinColumn(name: 'padawan_id')]
    public self|null $padawan = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
