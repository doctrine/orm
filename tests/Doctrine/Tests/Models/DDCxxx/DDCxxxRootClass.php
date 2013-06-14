<?php

namespace Doctrine\Tests\Models\DDCxxx;

/**
 * @Entity
 * @InheritanceType("JOINED")
 */
class DDCxxxRootClass
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;
    
    /**
     * @var Doctrine\Tests\Models\DDC\DDCxxxOtherClass
     *
     * @ManyToOne(targetEntity="DDCxxxOtherClass", inversedBy="childClasses")
     */
    public $other;
}