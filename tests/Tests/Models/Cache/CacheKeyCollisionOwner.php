<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cache_key_collision_owner')]
#[ORM\Cache]
class CacheKeyCollisionOwner
{
    /** @var Collection<int, CacheKeyCollisionChild> */
    #[ORM\OneToMany(targetEntity: CacheKeyCollisionChild::class, mappedBy: 'owner')]
    #[ORM\Cache(region: 'cache_key_collision_children')]
    public Collection $children;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(name: 'first_id')]
        public string $first,
        #[ORM\Id]
        #[ORM\Column(name: 'second_id')]
        public string $second,
        #[ORM\Column]
        public string $label,
    ) {
        $this->children = new ArrayCollection();
    }
}
