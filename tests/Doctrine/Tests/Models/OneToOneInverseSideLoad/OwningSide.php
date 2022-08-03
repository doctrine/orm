<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OneToOneInverseSideLoad;

/**
 * @Entity()
 * @Table(name="one_to_one_inverse_side_load_owning")
 */
class OwningSide
{
    /**
     * @var string
     * @Id()
     * @Column(type="string")
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
