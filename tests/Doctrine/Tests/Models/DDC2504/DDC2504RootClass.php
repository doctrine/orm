<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC2504;

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *     "root"  = "DDC2504RootClass",
 *     "child" = "DDC2504ChildClass"
 * })
 */
class DDC2504RootClass
{
    /**
     * @var int
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;

    /**
     * @var DDC2504OtherClass
     * @ManyToOne(targetEntity="DDC2504OtherClass", inversedBy="childClasses")
     */
    public $other;
}
