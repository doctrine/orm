<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\RelationAsId;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="relation_as_id_membership")
 */
class Membership
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    public $user;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Group::class)
     */
    public $group;

    /**
     * @ORM\Column(type="string")
     */
    public $role;
}
