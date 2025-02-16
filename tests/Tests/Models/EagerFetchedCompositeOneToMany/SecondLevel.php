<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\EagerFetchedCompositeOneToMany;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'eager_composite_join_second_level')]
#[ORM\Index(name: 'root_other_key_idx', columns: ['root_other_key', 'root_id'])]
class SecondLevel
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', nullable: false)]
    private int|null $upperId;

    #[ORM\Id]
    #[ORM\Column(type: 'string', nullable: false, name: 'other_key')]
    private string $otherKey;

    #[ORM\ManyToOne(targetEntity: RootEntity::class, inversedBy: 'secondLevel')]
    #[ORM\JoinColumn(name: 'root_id', referencedColumnName: 'id')]
    #[ORM\JoinColumn(name: 'root_other_key', referencedColumnName: 'other_key')]
    private RootEntity $root;

    public function __construct(RootEntity $upper)
    {
        $this->upperId  = $upper->getId();
        $this->otherKey = $upper->getOtherKey();
        $this->root     = $upper;
    }

    public function getId(): int|null
    {
        return $this->id;
    }
}
