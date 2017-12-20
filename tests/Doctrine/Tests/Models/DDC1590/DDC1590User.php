<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC1590;

use Doctrine\ORM\Annotation as ORM;

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
