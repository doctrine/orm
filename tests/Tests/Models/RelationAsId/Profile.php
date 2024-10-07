<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\RelationAsId;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="relation_as_id_profile")
 */
class Profile
{
    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="User")
     */
    public $user;

    /**
     * @ORM\Column(type="string")
     */
    public $url;
}
