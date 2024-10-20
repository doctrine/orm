<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Issue9300;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;

#[Entity]
class Issue9300Child
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var Collection<int, Issue9300Parent> */
    #[ManyToMany(targetEntity: Issue9300Parent::class)]
    public $parents;

    /** @var string */
    #[Column(type: 'string')]
    public $name;

    public function __construct()
    {
        $this->parents = new ArrayCollection();
    }
}
