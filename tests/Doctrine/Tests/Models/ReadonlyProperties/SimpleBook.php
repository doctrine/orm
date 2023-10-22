<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ReadonlyProperties;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'simple_book')]
class SimpleBook
{
    #[Column]
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private readonly int $id;

    #[Column]
    private readonly string $title;

    #[ManyToOne]
    #[JoinColumn(nullable: false)]
    private readonly Author $author;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }
}
