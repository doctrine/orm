<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'books')]
class Book
{
    #[Id]
    #[GeneratedValue]
    #[Column]
    public int $id;

    #[ManyToOne(targetEntity: Library::class, inversedBy: 'books')]
    #[JoinColumn(name: 'library_id', referencedColumnName: 'id')]
    public Library $library;

    #[Column(enumType: BookColor::class)]
    public BookColor $bookColor;

    #[ManyToMany(targetEntity: BookCategory::class, mappedBy: 'books')]
    public Collection $categories;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    public function setLibrary(Library $library): void
    {
        $this->library = $library;
    }

    public function addCategory(BookCategory $bookCategory): void
    {
        $this->categories->add($bookCategory);
        $bookCategory->addBook($this);
    }
}
