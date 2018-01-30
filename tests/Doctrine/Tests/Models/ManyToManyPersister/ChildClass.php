<?php

namespace Doctrine\Tests\Models\ManyToManyPersister;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="manytomanypersister_child")
 */
class ChildClass
{
    /**
     * @Id
     * @Column(name="id1", type="integer")
     *
     * @var integer
     */
    public $id1;

    /**
     * @Id
     * @ManyToOne(targetEntity=OtherParentClass::class, cascade={"persist"})
     * @JoinColumn(name="other_parent_id", referencedColumnName="id")
     *
     * @var OtherParentClass
     */
    public $otherParent;

    /**
     * @ManyToMany(targetEntity=ParentClass::class, inversedBy="children")
     * @JoinTable(
     *     name="parent_child",
     *     joinColumns={
     *         @JoinColumn(name="child_id1", referencedColumnName="id1"),
     *         @JoinColumn(name="child_id2", referencedColumnName="other_parent_id")
     *     },
     *     inverseJoinColumns={@JoinColumn(name="parent_id", referencedColumnName="id")}
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
