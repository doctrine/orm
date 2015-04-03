<?php

namespace Doctrine\Tests\Models\Pagination;

/**
 * Class User1
 * @package Doctrine\Tests\Models\Pagination
 *
 * @Entity()
 */
class User1 extends User
{
    /**
     * @Column(type="string")
     */
    public $email;
}
