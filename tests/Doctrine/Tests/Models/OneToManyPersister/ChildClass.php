<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OneToManyPersister;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="onetomanypersister_child")
 */
class ChildClass
{
    /**
     * @Id
     * @Column(type="integer")
     * @var int
     */
    public $id;

    /**
     * @Id
     * @ManyToOne(targetEntity=ParentClass::class, inversedBy="children", cascade={"persist"})
     * @JoinColumn(name="parent_id", referencedColumnName="id")
     * @var ParentClass
     */
    public $parent;

    public function __construct(int $id, ParentClass $otherParent)
    {
        $this->id     = $id;
        $this->parent = $otherParent;
    }
}
