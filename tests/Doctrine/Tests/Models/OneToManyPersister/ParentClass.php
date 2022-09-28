<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OneToManyPersister;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\DbalTypes\CustomIdObject;

/**
 * @Entity
 * @Table(name="onetomanypersister_parent")
 */
class ParentClass
{
    /**
     * @Id
     * @Column(type="CustomIdObject", length=255)
     * @var CustomIdObject
     */
    public $id;

    /**
     * @ManyToMany(targetEntity=ChildClass::class, mappedBy="parent", orphanRemoval=true, cascade={"persist"})
     * @var Collection|ChildClass[]
     * @psalm-var Collection<ChildClass>
     */
    public $children;

    public function __construct(CustomIdObject $id)
    {
        $this->id       = $id;
        $this->children = new ArrayCollection();
    }
}
