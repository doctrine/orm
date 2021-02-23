<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OneToOneInverseSideLoad;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="one_to_one_inverse_side_load_owning")
 */
class OwningSide
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * Owning side
     *
     * @ORM\OneToOne(targetEntity=InverseSide::class, inversedBy="owning")
     * @ORM\JoinColumn(nullable=false, name="inverse")
     */
    public $inverse;
}
