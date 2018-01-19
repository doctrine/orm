<?php

namespace Doctrine\Tests\Models\ManyToManyPersister;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="manytomanypersister_child")
 */
class ChildClass
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id1", type="integer")
     *
     * @var integer
     */
    public $id1;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=OtherParentClass::class, cascade={"persist"})
     * @ORM\JoinColumn(name="other_parent_id", referencedColumnName="id")
     *
     * @var OtherParentClass
     */
    public $otherParent;

    /**
     * @ORM\ManyToMany(targetEntity=ParentClass::class, inversedBy="children")
     * @ORM\JoinTable(
     *     name="parent_child",
     *     joinColumns={
     *         @ORM\JoinColumn(name="child_id1", referencedColumnName="id1"),
     *         @ORM\JoinColumn(name="child_id2", referencedColumnName="other_parent_id")
     *     },
     *     inverseJoinColumns={@ORM\JoinColumn(name="parent_id", referencedColumnName="id")}
     * )
     *
     * @var Collection|ParentClass[]
     */
    public $parents;

    public function __construct(int $id1, OtherParentClass $otherParent)
    {
        $this->id1         = $id1;
        $this->otherParent = $otherParent;
        $this->parents     = new ArrayCollection();
    }
}
