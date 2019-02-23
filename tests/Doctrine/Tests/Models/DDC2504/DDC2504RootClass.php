<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC2504;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *     "root"  = DDC2504RootClass::class,
 *     "child" = DDC2504ChildClass::class
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
     * @ORM\ManyToOne(targetEntity=DDC2504OtherClass::class, inversedBy="childClasses")
     *
     * @var DDC2504OtherClass
     */
    public $other;
}
