<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH11524;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="gh11524_entities")
 */
class GH11524Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int|null
     */
    public $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="GH11524Relation")
     * @ORM\JoinColumn(name="relation_id", referencedColumnName="id", nullable=true)
     *
     * @var GH11524Relation|null
     */
    public $relation = null;
}
