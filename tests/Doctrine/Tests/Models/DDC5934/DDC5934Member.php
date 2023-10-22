<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC5934;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity() */
#[ORM\Entity]
class DDC5934Member
{
    /**
     * @ORM\ManyToMany(targetEntity="DDC5934BaseContract", mappedBy="members")
     *
     * @var ArrayCollection
     */
    #[ORM\ManyToMany(targetEntity: DDC5934BaseContract::class, mappedBy: 'members')]
    public $contracts;

    public function __construct()
    {
        $this->contracts = new ArrayCollection();
    }
}
