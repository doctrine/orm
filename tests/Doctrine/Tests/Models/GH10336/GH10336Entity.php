<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH10336;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="gh10336_entities")
 */
class GH10336Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="GH10336Relation")
     * @ORM\JoinColumn(name="relation_id", referencedColumnName="id", nullable=true)
     */
    public ?GH10336Relation $relation = null;
}
