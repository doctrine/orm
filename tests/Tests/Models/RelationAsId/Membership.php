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
     *
     * @var User
     */
    public $user;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Group::class)
     *
     *  @var Group
     */
    public $group;

    /**
     * @ORM\Column(type="string")
     *
     * @var srtring
     */
    public $role;
}
