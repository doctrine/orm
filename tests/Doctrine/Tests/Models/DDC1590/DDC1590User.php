<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC1590;

use Doctrine\Tests\Models\DDC1590\DDC1590Entity;

/**
 * @Entity
 * @Table(name="users")
 */
class DDC1590User extends DDC1590Entity
{
    /**
     * @var string
     * @Column(type="string", length=255)
     */
    protected $name;
}
