<?php

namespace Doctrine\Tests\Models\DDC2504;

/**
 * @Entity
 */
class DDC2504OtherClass
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;

    /**
     * @var Doctrine\Tests\Models\DDC2504\DDC2504ChildClass
     *
     * @OneToMany(targetEntity="DDC2504ChildClass", mappedBy="other", fetch="EXTRA_LAZY")
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
