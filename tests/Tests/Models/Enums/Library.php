<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Enums;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
class Library
{
    #[Id]
    #[GeneratedValue]
    #[Column]
    public int $id;

    #[OneToMany(targetEntity: BookWithGenre::class, mappedBy: 'library')]
    public Collection $books;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }
}
