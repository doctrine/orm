<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cache_key_collision_child')]
#[ORM\Cache]
class CacheKeyCollisionChild
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'integer')]
        public int $id,
        #[ORM\ManyToOne(targetEntity: CacheKeyCollisionOwner::class, inversedBy: 'children')]
        #[ORM\JoinColumn(name: 'owner_first', referencedColumnName: 'first_id')]
        #[ORM\JoinColumn(name: 'owner_second', referencedColumnName: 'second_id')]
        #[ORM\Cache]
        public CacheKeyCollisionOwner $owner,
        #[ORM\Column]
        public string $label,
    ) {
    }
}
