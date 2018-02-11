<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OneToOneInverseSideLoad;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="one_to_one_inverse_side_load_inverse")
 */
class InverseSide
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $id;

    /** @ORM\OneToOne(targetEntity=OwningSide::class, mappedBy="inverse") */
    public $owning;
}
