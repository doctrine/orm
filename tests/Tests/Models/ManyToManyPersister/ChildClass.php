<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ManyToManyPersister;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'manytomanypersister_child')]
#[Entity]
class ChildClass
{
    /**
     * @var Collection|ParentClass[]
     * @phpstan-var Collection<ParentClass>
     */
    #[JoinTable(name: 'parent_child')]
    #[JoinColumn(name: 'child_id1', referencedColumnName: 'id1')]
    #[JoinColumn(name: 'child_id2', referencedColumnName: 'other_parent_id')]
    #[InverseJoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: ParentClass::class, inversedBy: 'children')]
    public $parents;

    public function __construct(
        #[Id]
        #[Column(name: 'id1', type: 'integer')]
        public int $id1,
        #[Id]
        #[ManyToOne(targetEntity: OtherParentClass::class, cascade: ['persist'])]
        #[JoinColumn(name: 'other_parent_id', referencedColumnName: 'id')]
        public OtherParentClass $otherParent,
    ) {
        $this->parents = new ArrayCollection();
    }
}
