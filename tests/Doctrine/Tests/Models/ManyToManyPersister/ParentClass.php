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

/**
 * @Entity
 * @Table(name="manytomanypersister_parent")
 */
class ParentClass
{
    /**
     * @ManyToMany(targetEntity=ChildClass::class, mappedBy="parents", orphanRemoval=true, cascade={"persist"})
     * @var Collection|ChildClass[]
     * @psalm-var Collection<ChildClass>
     */
    public $children;

    public function __construct(
        /**
         * @Id
         * @Column(name="id", type="integer")
         */
        public int $id
    ) {
        $this->children = new ArrayCollection();
    }
}
