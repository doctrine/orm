<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3699;

use Doctrine\Common\Collections\Collection;

/** @Entity @Table(name="ddc3699_child") */
class DDC3699Child extends DDC3699Parent
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     */
    public $id;

    /**
     * @var string
     * @Column(type="string")
     */
    public $childField;

    /**
     * @var DDC3699RelationOne
     * @OneToOne(targetEntity="DDC3699RelationOne", inversedBy="child")
     */
    public $oneRelation;

    /**
     * @psalm-var Collection<int, DDC3699RelationMany>
     * @OneToMany(targetEntity="DDC3699RelationMany", mappedBy="child")
     */
    public $relations;
}
