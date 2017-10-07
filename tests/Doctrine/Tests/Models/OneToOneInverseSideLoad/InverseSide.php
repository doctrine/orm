<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OneToOneInverseSideLoad;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity()
 * @Table(name="one_to_one_inverse_side_load_inverse")
 */
class InverseSide
{
    /**
     * @Id()
     * @Column(type="string")
     * @GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * @OneToOne(targetEntity=OwningSide::class, mappedBy="inverse")
     */
    public $owning;
}
