<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\BatchLoading;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SortDirection;

#[ORM\Entity]
class BatchLoadOwner
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\Column]
    public string $name = '';

    /** @var Collection<int, BatchLoadChild> */
    #[ORM\OneToMany(targetEntity: BatchLoadChild::class, mappedBy: 'owner')]
    #[ORM\OrderBy(['code' => SortDirection::Ascending])]
    public Collection $children;

    /** @var Collection<string, BatchLoadChild> */
    #[ORM\OneToMany(targetEntity: BatchLoadChild::class, mappedBy: 'indexedOwner', indexBy: 'code')]
    #[ORM\OrderBy(['code' => SortDirection::Ascending])]
    public Collection $indexedChildren;

    /** @var Collection<int, BatchLoadTag> */
    #[ORM\ManyToMany(targetEntity: BatchLoadTag::class, inversedBy: 'owners')]
    #[ORM\OrderBy(['name' => SortDirection::Ascending])]
    public Collection $tags;

    #[ORM\OneToOne(targetEntity: BatchLoadProfile::class, mappedBy: 'owner')]
    public BatchLoadProfile|null $profile = null;

    public function __construct()
    {
        $this->children        = new ArrayCollection();
        $this->indexedChildren = new ArrayCollection();
        $this->tags            = new ArrayCollection();
    }
}
