<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC5934;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class DDC5934Member
{
    /**
     * @ORM\ManyToMany(targetEntity=DDC5934BaseContract::class, mappedBy="members")
     *
     * @var ArrayCollection
     */
    public $contracts;

    public function __construct()
    {
        $this->contracts = new ArrayCollection();
    }
}
