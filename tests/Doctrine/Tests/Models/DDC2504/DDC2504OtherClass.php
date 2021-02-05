<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC2504;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;

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
     * @var DDC2504ChildClass
     * @OneToMany(targetEntity="DDC2504ChildClass", mappedBy="other", fetch="EXTRA_LAZY")
     * @var ArrayCollection|PersistentCollection
     */
    public $childClasses;

    public function __construct()
    {
        $this->childClasses = new ArrayCollection();
    }
}
