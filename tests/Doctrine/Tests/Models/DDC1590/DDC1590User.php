<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC1590;

/**
 * @Entity
 * @Table(name="users")
 */
class DDC1590User extends DDC1590Entity
{
    /** @Column(type="string", length=255) */
    protected $name;
}
