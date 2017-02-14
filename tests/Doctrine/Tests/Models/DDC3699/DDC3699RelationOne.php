<?php

namespace Doctrine\Tests\Models\DDC3699;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="ddc3699_relation_one")
 */
class DDC3699RelationOne
{
    /** @ORM\Id @ORM\Column(type="integer") */
    public $id;

    /** @ORM\OneToOne(targetEntity="DDC3699Child", mappedBy="oneRelation") */
    public $child;
}
