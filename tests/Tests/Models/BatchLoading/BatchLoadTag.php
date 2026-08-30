<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\BatchLoading;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SortDirection;

#[ORM\Entity]
class BatchLoadTag
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\Column]
    public string $name = '';

    /** @var Collection<int, BatchLoadOwner> */
    #[ORM\ManyToMany(targetEntity: BatchLoadOwner::class, mappedBy: 'tags')]
    #[ORM\OrderBy(['id' => SortDirection::Ascending])]
    public Collection $owners;

    public function __construct()
    {
        $this->owners = new ArrayCollection();
    }
}
