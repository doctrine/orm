<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ReadonlyProperties;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'book')]
class Book
{
    #[Column]
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    private readonly int $id;

    #[Column]
    private readonly string $title;

    #[ManyToMany(targetEntity: Author::class)]
    #[JoinTable(name: 'book_author')]
    private readonly Collection $authors;

    public function __construct()
    {
        $this->authors = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /** @return list<Author> */
    public function getAuthors(): array
    {
        return $this->authors->getValues();
    }
}
