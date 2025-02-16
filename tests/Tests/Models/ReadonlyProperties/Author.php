<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ReadonlyProperties;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'author')]
class Author
{
    #[Column]
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private readonly int $id;

    #[Column]
    private readonly string $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
