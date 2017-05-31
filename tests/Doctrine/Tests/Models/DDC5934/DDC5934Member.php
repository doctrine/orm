<?php

namespace Doctrine\Tests\Models\DDC5934;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class DDC5934Member
{
    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="DDC5934BaseContract", mappedBy="members")
     */
    public $contracts;

    public function __construct()
    {
        $this->contracts = new ArrayCollection();
    }
}
