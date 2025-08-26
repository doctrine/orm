<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class BookWithGenre
{
    #[Id]
    #[GeneratedValue]
    #[Column]
    public int $id;

    #[ManyToOne(targetEntity: Library::class, inversedBy: 'books')]
    public Library $library;

    #[Column(enumType: BookGenre::class)]
    public BookGenre $genre;

    #[ManyToMany(targetEntity: BookCategory::class, inversedBy: 'books')]
    public Collection $categories;

    public function __construct(BookGenre $genre)
    {
        $this->genre      = $genre;
        $this->categories = new ArrayCollection();
    }
}
