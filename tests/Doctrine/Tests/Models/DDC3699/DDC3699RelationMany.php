<?php

namespace Doctrine\Tests\Models\DDC3699;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="ddc3699_relation_many")
 */
class DDC3699RelationMany
{
    /** @ORM\Id @ORM\Column(type="integer") */
    public $id;

    /** @ORM\ManyToOne(targetEntity="DDC3699Child", inversedBy="relations") */
    public $child;
}
