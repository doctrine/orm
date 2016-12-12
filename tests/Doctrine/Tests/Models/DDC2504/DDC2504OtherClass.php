<?php

namespace Doctrine\Tests\Models\DDC2504;

use Doctrine\Common\Collections\ArrayCollection;

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
     * @var \Doctrine\Tests\Models\DDC2504\DDC2504ChildClass
     *
     * @OneToMany(targetEntity="DDC2504ChildClass", mappedBy="other", fetch="EXTRA_LAZY")
     *
     * @var ArrayCollection|\Doctrine\ORM\PersistentCollection
     */
    public $childClasses;

    public function __construct()
    {
        $this->childClasses = new ArrayCollection();
    }
}
