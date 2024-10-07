<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\RelationAsId;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="relation_as_id_group")
 */
class Group
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $name;
}
