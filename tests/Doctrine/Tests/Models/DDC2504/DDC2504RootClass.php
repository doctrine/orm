<?php

namespace Doctrine\Tests\Models\DDC2504;

/**
 * @Entity
 * @InheritanceType("JOINED")
 */
class DDC2504RootClass
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;
    
    /**
     * @var Doctrine\Tests\Models\DDC\DDC2504OtherClass
     *
     * @ManyToOne(targetEntity="DDC2504OtherClass", inversedBy="childClasses")
     */
    public $other;
}