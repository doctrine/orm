<?php

namespace Doctrine\Tests\Models\DDCxxx;

/**
 * @Entity
 */
class DDCxxxOtherClass
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;

    /**
     * @var Doctrine\Tests\Models\DDC\DDCxxxChildClass
     *
     * @OneToMany(targetEntity="DDCxxxChildClass", mappedBy="other", fetch="EXTRA_LAZY")
     */
    private $childClasses;

    public function __construct()
    {
        $this->childClasses = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function addChildClass($childClass)
    {
        $this->childClasses[] = $childClass;
    }

    public function getChildClasses()
    {
        return $this->childClasses;
    }
}