<?php

namespace Doctrine\Tests\Models\DDC1590;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\Models\DDC1590\DDC1590Entity;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class DDC1590User extends DDC1590Entity
{
    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

}
