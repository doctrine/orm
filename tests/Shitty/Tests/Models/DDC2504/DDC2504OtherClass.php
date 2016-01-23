<?php

namespace Shitty\Tests\Models\DDC2504;

use Shitty\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class DDC2504OtherClass
{
    const CLASSNAME = __CLASS__;

    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;

    /**
     * @var \Shitty\Tests\Models\DDC2504\DDC2504ChildClass
     *
     * @OneToMany(targetEntity="DDC2504ChildClass", mappedBy="other", fetch="EXTRA_LAZY")
     *
     * @var ArrayCollection|\Shitty\ORM\PersistentCollection
     */
    public $childClasses;

    public function __construct()
    {
        $this->childClasses = new ArrayCollection();
    }
}
