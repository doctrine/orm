<?php

namespace Doctrine\Tests\Models\DDC1590;

use Doctrine\Tests\Models\DDC1590\DDC1590Entity;

/**
 * @Entity
 * @Table(name="users")
 */
class DDC1590User extends DDC1590Entity
{
    /**
     * @Column(type="string", length=255)
     */
    protected $name;

}
