<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC5934;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class DDC5934BaseContract
{
    /**
     * @ORM\Id()
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue()
     */
    public $id;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity=DDC5934Member::class, fetch="LAZY", inversedBy="contracts")
     */
    public $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }
}
