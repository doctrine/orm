<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OneToOneInverseSideLoad;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity()
 * @Table(name="o2o_side_owning")
 */
class OwningSide
{
    /**
     * @var string
     * @Id()
     * @Column(type="string", length=255)
     * @GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * Owning side
     *
     * @var InverseSide
     * @OneToOne(targetEntity=InverseSide::class, inversedBy="owning")
     * @JoinColumn(nullable=false, name="inverse")
     */
    public $inverse;
}
