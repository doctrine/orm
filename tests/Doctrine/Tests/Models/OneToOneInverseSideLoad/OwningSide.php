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
     * @Id()
     * @Column(type="string")
     * @GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * Owning side
     *
     * @OneToOne(targetEntity=InverseSide::class, inversedBy="owning")
     * @JoinColumn(nullable=false, name="inverse")
     */
    public $inverse;
}
