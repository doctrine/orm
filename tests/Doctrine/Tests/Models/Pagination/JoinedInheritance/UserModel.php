<?php

namespace Doctrine\Tests\Models\Pagination\JoinedInheritance;

/**
 * @package Doctrine\Tests\Models\Pagination\JoinedInheritance
 *
 * @MappedSuperclass
 */
abstract class UserModel
{
    /**
     * @Column(type="string")
     */
    public $email;

    /**
     * @Column(type="string")
     */
    public $password;
}
