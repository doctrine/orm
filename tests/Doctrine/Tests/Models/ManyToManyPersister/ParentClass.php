<?php

namespace Doctrine\Tests\Models\ManyToManyPersister;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="manytomanypersister_parent")
 */
class ParentClass
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     *
     * @var integer
     */
    public $id;

    /**
     * @ORM\ManyToMany(targetEntity=ChildClass::class, mappedBy="parents", orphanRemoval=true, cascade={"persist"})
     *
     * @var Collection|ChildClass[]
     */
    public $children;

    public function __construct(int $id)
    {
        $this->id       = $id;
        $this->children = new ArrayCollection();
    }
}
