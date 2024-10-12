<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;

#[Entity]
class BookCategory
{
    #[Id]
    #[Column]
    #[GeneratedValue]
    public int $id;

    #[Column]
    public string $name;

    #[ManyToMany(targetEntity: Book::class, inversedBy: 'categories')]
    public Collection $books;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }

    public function addBook(Book $book): void
    {
        $this->books->add($book);
    }

    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function getBooksWithColor(BookColor $bookColor): Collection
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('bookColor', $bookColor));

        return $this->books->matching($criteria);
    }
}
