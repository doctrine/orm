<?php

namespace Doctrine\Tests\Models\DDC3699;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity @ORM\Table(name="ddc3699_child") */
class DDC3699Child extends DDC3699Parent
{
    /** @ORM\Id @ORM\Column(type="integer") */
    public $id;

    /** @ORM\Column(type="string") */
    public $childField;

    /** @ORM\OneToOne(targetEntity="DDC3699RelationOne", inversedBy="child") */
    public $oneRelation;

    /** @ORM\OneToMany(targetEntity="DDC3699RelationMany", mappedBy="child") */
    public $relations;
}
