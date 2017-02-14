<?php

namespace Doctrine\Tests\Models\DDC2504;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *     "root"  = "DDC2504RootClass",
 *     "child" = "DDC2504ChildClass"
 * })
 */
class DDC2504RootClass
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id @ORM\GeneratedValue
     */
    public $id;

    /**
     * @var \Doctrine\Tests\Models\DDC2504\DDC2504OtherClass
     *
     * @ORM\ManyToOne(targetEntity="DDC2504OtherClass", inversedBy="childClasses")
     */
    public $other;
}
