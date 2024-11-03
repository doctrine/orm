<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ManyToManyPersister;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'manytomanypersister_parent')]
#[Entity]
class ParentClass
{
    /**
     * @var Collection|ChildClass[]
     * @phpstan-var Collection<ChildClass>
     */
    #[ManyToMany(targetEntity: ChildClass::class, mappedBy: 'parents', orphanRemoval: true, cascade: ['persist'])]
    public $children;

    public function __construct(
        #[Id]
        #[Column(name: 'id', type: 'integer')]
        public int $id,
    ) {
        $this->children = new ArrayCollection();
    }
}
