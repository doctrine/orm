<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC2504;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class DDC2504OtherClass
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity=DDC2504ChildClass::class, mappedBy="other", fetch="EXTRA_LAZY")
     *
     * @var ArrayCollection|\Doctrine\ORM\PersistentCollection
     */
    public $childClasses;

    public function __construct()
    {
        $this->childClasses = new ArrayCollection();
    }
}
