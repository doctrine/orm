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
     * @ORM\ManyToOne
     */
    public User $user;

    /**
     * @ORM\Id
     * @ORM\ManyToOne
     */
    public Group $group;

    /**
     * @ORM\Column
     */
    public string $role;
}
