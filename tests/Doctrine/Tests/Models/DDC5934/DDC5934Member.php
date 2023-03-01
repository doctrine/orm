<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC5934;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;

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

    public static function loadMetadata(ClassMetadata $metadata): void
    {
    }
}
