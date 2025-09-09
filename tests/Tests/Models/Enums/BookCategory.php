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

#[Entity]
class BookCategory
{
    #[Id]
    #[Column]
    #[GeneratedValue]
    public int $id;

    #[ManyToMany(targetEntity: BookWithGenre::class, mappedBy: 'categories')]
    public Collection $books;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }
}
